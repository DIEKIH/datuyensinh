<?php

namespace App\Http\Controllers;

use App\Services\AdmissionLeadScoringService;
use App\Services\AdmissionRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdmissionAdminController extends Controller
{
    private $scoring;
    private $rag;

    public function __construct(AdmissionLeadScoringService $scoring, AdmissionRagService $rag)
    {
        $this->scoring = $scoring;
        $this->rag = $rag;
    }

    public function index()
    {
        return view('admins.pages.admission_cms');
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_leads' => DB::table('admission_leads')->count(),
                'hot_leads' => DB::table('admission_leads')->where('score_grade', 'hot')->count(),
                'new_leads' => DB::table('admission_leads')->where('status', 'new')->count(),
                'rag_documents' => DB::table('admission_rag_documents')->count(),
                'n8n_events' => DB::table('admission_n8n_logs')->whereDate('created_at', now()->toDateString())->count(),
            ],
        ]);
    }

    public function leads(Request $request)
    {
        $query = DB::table('admission_leads');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('intended_major', 'like', '%' . $keyword . '%');
            });
        }

        foreach (['channel', 'status', 'score_grade'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $leads = $query->orderByDesc('score')
            ->orderByDesc('last_interaction_at')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $leads]);
    }

    public function storeLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'channel' => 'required|string|max:80',
            'source_campaign' => 'nullable|string|max:255',
            'intended_major' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? 'new';
        $data['last_interaction_at'] = now();
        $scored = $this->scoring->score($data);
        $data['score'] = $scored['score'];
        $data['score_grade'] = $scored['grade'];
        $data['profile'] = json_encode(['score_reasons' => $scored['reasons']]);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('admission_leads')->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function showLead($id)
    {
        $lead = DB::table('admission_leads')->where('id', $id)->first();

        if (!$lead) {
            return response()->json(['success' => false, 'message' => 'Khong tim thay lead.'], 404);
        }

        $activities = DB::table('admission_lead_activities')
            ->where('lead_id', $id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => compact('lead', 'activities')]);
    }

    public function updateLeadStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|max:40',
            'note' => 'nullable|string|max:5000',
        ]);

        $updated = DB::table('admission_leads')
            ->where('id', $id)
            ->update([
                'status' => $request->status,
                'note' => $request->note,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => (bool) $updated]);
    }

    public function documents()
    {
        $chunkCounts = DB::table('admission_rag_chunks')
            ->select('document_id', DB::raw('COUNT(*) as chunks_count'))
            ->groupBy('document_id');

        $documents = DB::table('admission_rag_documents as d')
            ->leftJoinSub($chunkCounts, 'cc', function ($join) {
                $join->on('cc.document_id', '=', 'd.id');
            })
            ->select('d.*', DB::raw('COALESCE(cc.chunks_count, 0) as chunks_count'))
            ->orderByDesc('d.updated_at')
            ->get();

        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function storeDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'category' => 'required|string|max:80',
            'status' => 'required|in:active,draft,archived',
            'source_url' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'doc_files' => 'nullable|array',
            'doc_files.*' => 'file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $id = (int) ($data['document_id'] ?? 0);
        
        $files = $request->file('doc_files') ?? [];
        
        // Kiểm tra định dạng file thủ công để tránh lỗi nhận dạng MIME của PHP/Laravel
        $allowedExtensions = ['txt', 'docx', 'json', 'pdf'];
        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedExtensions)) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'doc_files' => ["File '{$file->getClientOriginalName()}' không đúng định dạng. Chỉ chấp nhận các file: " . implode(', ', $allowedExtensions)]
                    ]
                ], 422);
            }
        }

        $totalChunks = 0;
        $savedDocumentsCount = 0;

        if (count($files) > 0) {
            foreach ($files as $index => $file) {
                $fileContent = $this->extractTextFromFile($file);
                if (empty($fileContent)) {
                    continue;
                }

                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $title = $fileName;
                if (!empty($data['title'])) {
                    $title = count($files) > 1 ? $data['title'] . ' - ' . $fileName : $data['title'];
                }

                $dbData = [
                    'title' => $title,
                    'category' => $data['category'],
                    'status' => $data['status'],
                    'source_url' => $data['source_url'] ?? null,
                    'content' => $fileContent,
                    'updated_at' => now(),
                ];

                if ($id > 0 && $index === 0) {
                    DB::table('admission_rag_documents')->where('id', $id)->update($dbData);
                    $docId = $id;
                } else {
                    $dbData['created_at'] = now();
                    $docId = DB::table('admission_rag_documents')->insertGetId($dbData);
                }

                // Tải file trực tiếp lên OpenAI Vector Store
                $openAiFileId = $this->rag->uploadFileToOpenAIVectorStore($file->getRealPath(), $file->getClientOriginalName());
                
                // Cập nhật ID file của OpenAI vào DB
                if ($openAiFileId) {
                    DB::table('admission_rag_documents')->where('id', $docId)->update(['source_url' => $openAiFileId]);
                }
                
                $totalChunks += 1;
                $savedDocumentsCount++;
            }

            if ($savedDocumentsCount === 0) {
                return response()->json([
                    'success' => false,
                    'errors' => ['doc_files' => ['Không thể đọc nội dung từ bất kỳ file nào được tải lên.']]
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã tải lên và lưu {$savedDocumentsCount} tài liệu với tổng cộng {$totalChunks} chunks.",
                'chunks' => $totalChunks
            ]);
        } else {
            $content = $data['content'] ?? '';
            if (empty($content)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['content' => ['Nội dung tài liệu hoặc file tải lên không được để trống.']]
                ], 422);
            }

            if (empty($data['title'])) {
                $data['title'] = 'Tài liệu tuyển sinh ' . now()->format('d/m/Y H:i');
            }

            $dbData = [
                'title' => $data['title'],
                'category' => $data['category'],
                'status' => $data['status'],
                'source_url' => $data['source_url'] ?? null,
                'content' => $content,
                'updated_at' => now(),
            ];

            if ($id > 0) {
                DB::table('admission_rag_documents')->where('id', $id)->update($dbData);
                $docId = $id;
            } else {
                $dbData['created_at'] = now();
                $docId = DB::table('admission_rag_documents')->insertGetId($dbData);
            }

            // Tạo file tạm để tải nội dung text lên OpenAI
            $tmpFile = sys_get_temp_dir() . '/' . \Illuminate\Support\Str::slug($data['title']) . '.txt';
            file_put_contents($tmpFile, $content);
            $openAiFileId = $this->rag->uploadFileToOpenAIVectorStore($tmpFile, $data['title'] . '.txt');
            @unlink($tmpFile);

            if ($openAiFileId) {
                DB::table('admission_rag_documents')->where('id', $docId)->update(['source_url' => $openAiFileId]);
            }

            return response()->json([
                'success' => true,
                'id' => $docId,
                'chunks' => 1
            ]);
        }
    }

    private function extractTextFromFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        switch ($extension) {
            case 'txt':
                return file_get_contents($filePath);
            case 'json':
                $jsonContent = file_get_contents($filePath);
                $data = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->formatJsonToText($data);
                }
                return $jsonContent;
            case 'docx':
                return $this->extractTextFromDocx($filePath);
            case 'pdf':
                return $this->extractTextFromPdf($filePath);
            default:
                return '';
        }
    }

    private function formatJsonToText($data)
    {
        if (!is_array($data)) {
            return (string) $data;
        }

        $text = '';
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $item) {
                if (isset($item['question']) || isset($item['answer'])) {
                    $q = $item['question'] ?? '';
                    $a = $item['answer'] ?? '';
                    $text .= "Hỏi: " . $q . "\nĐáp: " . $a . "\n\n";
                } elseif (isset($item['title']) || isset($item['content'])) {
                    $t = $item['title'] ?? '';
                    $c = $item['content'] ?? '';
                    $text .= "Tiêu đề: " . $t . "\nNội dung: " . $c . "\n\n";
                } else {
                    $text .= json_encode($item, JSON_UNESCAPED_UNICODE) . "\n\n";
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $text .= $key . ":\n" . $this->formatJsonToText($value) . "\n\n";
                } else {
                    $text .= $key . ": " . $value . "\n";
                }
            }
        }
        return trim($text);
    }

    private function extractTextFromDocx($filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xmlContent = $zip->getFromIndex($index);
                $zip->close();
                
                $xmlContent = str_replace(['</w:p>', '</w:r>', '<w:tab/>'], ["\n", " ", " "], $xmlContent);
                return strip_tags($xmlContent);
            }
            $zip->close();
        }
        return '';
    }

    private function extractTextFromPdf($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[extractTextFromPdf] Error parsing PDF: ' . $e->getMessage());
            return '';
        }
    }

    public function askRag(Request $request)
    {
        $request->validate(['question' => 'required|string|max:2000']);

        return response()->json([
            'success' => true,
            'data' => $this->rag->answer($request->question),
        ]);
    }



    public function getOpenAiConfig()
    {
        $apiKey = config('services.openai.key');
        $assistantId = env('OPENAI_ASSISTANT_ID');
        if (!$apiKey || !$assistantId) {
            return response()->json(['success' => false, 'message' => 'Chua cau hinh OpenAI']);
        }

        $asstRes = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->get("https://api.openai.com/v1/assistants/{$assistantId}");
            
        $assistant = $asstRes->json();
        $instructions = $assistant['instructions'] ?? '';
        $vsId = $assistant['tool_resources']['file_search']['vector_store_ids'][0] ?? null;

        $files = [];
        if ($vsId) {
            $vsFilesRes = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->get("https://api.openai.com/v1/vector_stores/{$vsId}/files");
            
            $vsFiles = $vsFilesRes->json('data') ?? [];
            
            $allFilesRes = \Illuminate\Support\Facades\Http::withToken($apiKey)->get("https://api.openai.com/v1/files");
            $allFiles = collect($allFilesRes->json('data') ?? [])->keyBy('id');

            foreach ($vsFiles as $vf) {
                $fileInfo = $allFiles->get($vf['id']);
                $files[] = [
                    'id' => $vf['id'],
                    'filename' => $fileInfo ? $fileInfo['filename'] : 'Unknown',
                    'bytes' => $fileInfo ? round($fileInfo['bytes'] / 1024, 2) . ' KB' : '0 KB',
                    'created_at' => $fileInfo ? date('Y-m-d H:i:s', $fileInfo['created_at']) : '',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'instructions' => $instructions,
                'files' => $files
            ]
        ]);
    }

    public function updateOpenAiPrompt(Request $request)
    {
        $apiKey = config('services.openai.key');
        $assistantId = env('OPENAI_ASSISTANT_ID');
        
        \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->post("https://api.openai.com/v1/assistants/{$assistantId}", [
                'instructions' => $request->instructions
            ]);
            
        return response()->json(['success' => true]);
    }

    public function deleteOpenAiFile($fileId)
    {
        $apiKey = config('services.openai.key');
        \Illuminate\Support\Facades\Http::withToken($apiKey)->delete("https://api.openai.com/v1/files/{$fileId}");
        DB::table('admission_rag_documents')->where('source_url', $fileId)->delete();
        return response()->json(['success' => true]);
    }

    public function uploadOpenAiFile(Request $request)
    {
        $request->validate(['file' => 'required|file|max:20480']);
        $file = $request->file('file');
        
        $rag = new \App\Services\AdmissionRagService();
        $fileId = $rag->uploadFileToOpenAIVectorStore($file->getRealPath(), $file->getClientOriginalName());
        
        if ($fileId) {
            DB::table('admission_rag_documents')->insert([
                'title' => $file->getClientOriginalName(),
                'category' => 'openai_direct',
                'status' => 'active',
                'source_url' => $fileId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 500);
    }
}
