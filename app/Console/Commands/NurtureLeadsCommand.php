<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NurtureLeadsCommand extends Command
{
    protected $signature = 'leads:nurture';
    protected $description = 'Tim danh sach Lead can cham soc va ban qua Webhook cho n8n xu ly.';

    public function handle()
    {
        $this->info('Dang quet danh sach Lead de cham soc...');

        // Tạm thời sửa lại để lấy các Lead trong vòng 30 ngày (để dễ test)
        $targetDateStart = now()->subDays(30);
        $targetDateEnd = now();

        $leads = DB::table('admission_leads')
            ->whereIn('status', ['new', 'contacted'])
            ->whereNotNull('email')
            ->whereBetween('created_at', [$targetDateStart, $targetDateEnd])
            ->get();

        if ($leads->isEmpty()) {
            $this->info('Khong co Lead nao can cham soc hom nay.');
            return 0;
        }

        // Đường dẫn Webhook của n8n (bạn sẽ tạo trong n8n)
        $n8nWebhookUrl = env('N8N_WEBHOOK_URL_NURTURE', 'http://localhost:5678/webhook/nurture-leads');

        $this->info("Phat hien " . $leads->count() . " leads. Dang ban du lieu sang n8n: $n8nWebhookUrl");

        try {
            $response = Http::post($n8nWebhookUrl, [
                'event' => 'lead_nurturing',
                'timestamp' => now()->toIso8601String(),
                'data' => $leads->toArray()
            ]);

            if ($response->successful()) {
                // Cập nhật trạng thái sang contacted để không gửi lại vào ngày mai
                $leadIds = $leads->pluck('id')->toArray();
                DB::table('admission_leads')->whereIn('id', $leadIds)->update([
                    'status' => 'contacted',
                    'last_interaction_at' => now(),
                    'updated_at' => now()
                ]);

                $this->info("Da gui thanh cong sang n8n!");
            } else {
                $this->error("Loi tu n8n: " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("Loi khi ban Webhook n8n: " . $e->getMessage());
            $this->error("Khong the ket noi voi n8n. Vui long kiem tra URL hoac bat Workflow.");
        }

        return 0;
    }
}
