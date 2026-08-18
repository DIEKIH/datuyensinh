<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class FacebookAuthController extends Controller
{
    public function login()
    {
        $url = 'https://www.facebook.com/v26.0/dialog/oauth?' . http_build_query([
            'client_id'    => env('FB_APP_ID'),
            'redirect_uri' => route('facebook.callback'),
            'scope' => implode(',', [
                'pages_show_list',
                'pages_manage_posts',
                'pages_read_engagement',
                'pages_messaging',
            ]),
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return response()->json(['error' => 'No code provided'], 400);
        }

        // Đổi code lấy User Access Token
        $response = Http::get('https://graph.facebook.com/v26.0/oauth/access_token', [
            'client_id'     => env('FB_APP_ID'),
            'client_secret' => env('FB_APP_SECRET'),
            'redirect_uri'  => route('facebook.callback'),
            'code'          => $code,
        ]);

        $userAccessToken = $response->json('access_token');

        if (!$userAccessToken) {
            return response()->json(['error' => 'Could not get User Access Token', 'details' => $response->json()], 400);
        }

        // Dùng User Access Token để lấy Page Access Token
        $pagesResponse = Http::get('https://graph.facebook.com/v26.0/me/accounts', [
            'access_token' => $userAccessToken,
        ]);

        $pages = $pagesResponse->json('data');

        if (empty($pages)) {
            return response()->json(['error' => 'No pages found for this user'], 404);
        }

        // Lấy Page đầu tiên (hoặc xử lý chọn Page tuỳ logic)
        $pageAccessToken = $pages[0]['access_token'];
        $pageId = $pages[0]['id'];
        $pageName = $pages[0]['name'];

        // Lưu thông tin Page vào DB của user hiện tại
        // if (Auth::check()) {
        //     Auth::user()->update([
        //         'fb_page_id' => $pageId,
        //         'fb_page_access_token' => $pageAccessToken,
        //     ]);
        // }

        // Lưu thông tin Page vào DB của user hiện tại
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $user->update([
                'fb_page_id' => $pageId,
                'fb_page_access_token' => $pageAccessToken,
            ]);
        }

        return response()->json([
            'message' => 'Connected successfully to Facebook Page!',
            'page_id' => $pageId,
            'page_name' => $pageName
        ]);
    }

    public function postToFacebook(Request $request)
    {
        // Require user auth
        $user = Auth::user();
        if (!$user || !$user->fb_page_id || !$user->fb_page_access_token) {
            return response()->json(['error' => 'Not connected to Facebook Page. Please login via Facebook first.'], 403);
        }

        $content = $request->input('content', 'Bài đăng test từ mã nguồn Laravel!');
        $imageUrl = $request->input('image_url');
        
        if ($imageUrl || $request->hasFile('image')) {
            $endpoint = "https://graph.facebook.com/v26.0/{$user->fb_page_id}/photos";
            
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $response = Http::attach(
                    'source', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
                )->post($endpoint, [
                    'message'      => $content,
                    'access_token' => $user->fb_page_access_token,
                ]);
            } else {
                // If the URL is relative, prepend app url
                if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = url($imageUrl);
                }
                
                $response = Http::post($endpoint, [
                    'message'      => $content,
                    'url'          => $imageUrl,
                    'access_token' => $user->fb_page_access_token,
                ]);
            }
        } else {
            $response = Http::post("https://graph.facebook.com/v26.0/{$user->fb_page_id}/feed", [
                'message'      => $content,
                'access_token' => $user->fb_page_access_token,
            ]);
        }

        return response()->json($response->json());
    }

    public function deauthorize(Request $request)
    {
        $signedRequest = $request->get('signed_request');
        $data = $this->parseSignedRequest($signedRequest);
        
        // xóa token đã lưu theo $data['user_id']
        // User::where('fb_user_id', $data['user_id'])->update(['fb_access_token' => null]);

        return response()->json(['success' => true]);
    }

    public function dataDeletion(Request $request)
    {
        $signedRequest = $request->get('signed_request');
        $data = $this->parseSignedRequest($signedRequest);
        $confirmationCode = uniqid();

        // xóa data user theo $data['user_id']

        return response()->json([
            'url' => route('facebook.deletion.status', ['id' => $confirmationCode]),
            'confirmation_code' => $confirmationCode,
        ]);
    }

    private function parseSignedRequest($signedRequest)
    {
        if (!$signedRequest) {
            return [];
        }
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) !== 2) {
            return [];
        }
        [$encodedSig, $payload] = $parts;
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        return $data;
    }
}
