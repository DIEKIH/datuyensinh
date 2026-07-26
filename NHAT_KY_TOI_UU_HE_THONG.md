# Nhật ký Tối ưu hóa Hệ thống Tuyển sinh Đa kênh (Optimization Log)

Tài liệu này ghi chép chi tiết toàn bộ các thay đổi mã nguồn, cấu trúc RAG, chatbot AI và hiệu năng hệ thống qua 5 hạng mục tối ưu hóa chính.

---

## 🚀 1. Chi tiết các hạng mục đã thực hiện (Đã hoàn thành)

### 🔹 Mục 1: Tối ưu thuật toán RAG & Tìm kiếm tương đồng (RAG Search)
*   **Vấn đề cũ:** Trong [AdmissionRagService.php](file:///C:/laragon/www/tuyensinhtest/app/Services/AdmissionRagService.php), việc tìm kiếm các chunk quy định liên quan sử dụng truy vấn `LIKE '%keyword%'` thô sơ và sắp xếp theo ngày cập nhật, gây ra hiện tượng *Full Table Scan* và cho kết quả không chính xác.
*   **Thay đổi code:**
    *   **Trước (Code cũ):**
        ```php
        $query = DB::table('admission_rag_chunks as c')
            ->join('admission_rag_documents as d', 'd.id', '=', 'c.document_id')
            ->where('c.content', 'like', '%' . $question . '%')
            ->orderByDesc('c.updated_at')
            ->limit($limit)
            ->get();
        ```
    *   **Sau (Code mới):** Nâng cấp lên **MySQL Full-Text Search (Match Against)** kết hợp sắp xếp theo độ trùng khớp thực tế (Relevance Score) và cơ chế tự động Fallback về LIKE nếu FTS gặp lỗi.
        *   Migration tạo chỉ mục Full-Text: [2026_07_13_224317_add_fulltext_indexes_to_admission_tables.php](file:///C:/laragon/www/tuyensinhtest/database/migrations/2026_07_13_224317_add_fulltext_indexes_to_admission_tables.php).
        *   Cập nhật logic tại [AdmissionRagService.php:L56-L112](file:///C:/laragon/www/tuyensinhtest/app/Services/AdmissionRagService.php#L56-L112).

---

### 🔹 Mục 2: Tối ưu hóa hiệu năng & Tránh Timeout (Xử lý Webhook bất đồng bộ qua Queue)
*   **Vấn đề cũ:** Gọi HTTP Webhook sang n8n một cách đồng bộ khi thí sinh đăng ký tư vấn, admin trả lời ticket hoặc chatbot tạo ticket làm nghẽn request của người dùng (trễ 10-30s), gây lỗi Timeout 504.
*   **Thay đổi code:** Thiết lập Job hàng đợi [SendN8NWebhook.php](file:///C:/laragon/www/tuyensinhtest/app/Jobs/SendN8NWebhook.php) (tự động thử lại 3 lần) và thay đổi các webhook đồng bộ sang bất đồng bộ qua Queue.
    *   **1. Tạo lead tại Landing Page:**
        *   *Trước (Code cũ):* Gọi HTTP đồng bộ trực tiếp sang n8n.
        *   *Sau (Code mới tại [AdmissionLeadController.php:L121-L143](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdmissionLeadController.php#L121-L143)):*
            ```php
            \App\Jobs\SendN8NWebhook::dispatch($n8nWebhookUrl, [
                'event' => 'lead.created_or_updated',
                ...
            ]);
            ```
    *   **2. Khi Admin phản hồi Ticket:**
        *   *Trước (Code cũ):* Gọi `Http::timeout(10)->post($webhookUrl, [...])` đồng bộ.
        *   *Sau (Code mới tại [AdminController.php:L1648-L1660](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdminController.php#L1648-L1660)):*
            ```php
            \App\Jobs\SendN8NWebhook::dispatch($webhookUrl, [
                'event' => 'ticket.answered',
                ...
            ]);
            ```
    *   **3. Khi Chatbot tạo Ticket hỗ trợ:**
        *   *Trước (Code cũ tại [AdviseController.php](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdviseController.php)):* Gọi HTTP đồng bộ bên trong hàm `notifyN8N`.
        *   *Sau (Code mới tại [AdviseController.php:L895-L904](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdviseController.php#L895-L904)):*
            ```php
            private function notifyN8N(array $data) {
                ...
                \App\Jobs\SendN8NWebhook::dispatch($webhookUrl, $data);
            }
            ```

---

### 🔹 Mục 3: Cải tiến cơ chế cắt nhỏ tài liệu RAG (Text Splitting & Overlap)
*   **Vấn đề cũ:** Cắt văn bản thô theo dấu xuống dòng kép `\n\n` hoặc cắt cứng ở giới hạn 1200 ký tự mà không có phần gối đầu (overlap), dễ làm đứt gãy ngữ cảnh của các điều khoản quy chế tuyển sinh nằm ở ranh giới cắt.
*   **Thay đổi code:**
    *   **Trước (Code cũ):** Cắt cứng văn bản thô theo ký tự.
    *   **Sau (Code mới tại [AdmissionRagService.php:L11-L97](file:///C:/laragon/www/tuyensinhtest/app/Services/AdmissionRagService.php#L11-L97)):** Hỗ trợ cấu hình `$chunkSize = 1000` và `$chunkOverlap = 200`. Tách dòng và tách câu thông minh bằng Regex (`.`, `?`, `!`) rồi gom cụm theo cơ chế cửa sổ trượt (sliding window) giữ lại 200 ký tự cuối làm phần mở đầu của chunk tiếp theo.

---

### 🔹 Mục 4: Đảm bảo tính nhất quán dữ liệu Chatbot (Data Consistency)
*   **Vấn đề cũ:** Tin nhắn của người dùng được lưu vào database trước khi gọi sang API OpenAI Assistant. Nếu OpenAI gặp lỗi (timeout, hết hạn mức...), hệ thống trả về lỗi 502 nhưng tin nhắn local vẫn được lưu, dẫn đến lệch pha lịch sử chat.
*   **Thay đổi code:**
    *   **Trước (Code cũ):** Lưu tin nhắn vào CSDL, gọi OpenAI. Nếu thất bại, ghi log lỗi và trả về JSON 502 mà không dọn dẹp tin nhắn mồ côi local.
    *   **Sau (Code mới tại [AdviseController.php:L57-L140](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdviseController.php#L57-L140)):** Nếu gọi API OpenAI không thành công (`!$res->successful()`) hoặc ném ra Exception trong khối `catch` (do mất mạng, timeout...), hệ thống sẽ tự động gọi `$userMessage->delete()` để xóa tin nhắn local. Tránh dùng DB Transaction bao bọc cuộc gọi HTTP để phòng nghẽn connection pool.

---

### 🔹 Mục 5: Nâng cấp bộ lọc che thông tin cá nhân (PII Safeguard)
*   **Vấn đề cũ:** Hàm `anonymizeText` chỉ che SĐT và Email đơn giản, dễ bị sót định dạng số điện thoại khác và chưa che số căn cước công dân (CCCD/CMND). Đồng thời OpenAI Assistant chưa có chỉ thị tự động che thông tin nhạy cảm.
*   **Thay đổi code:**
    *   **Trước (Code cũ):**
        ```php
        private function anonymizeText($text) {
            $text = preg_replace('/(0[3|5|7|8|9]+([0-9\s\.]{7,9}))\b/', '[Số điện thoại]', $text);
            $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[Email]', $text);
            return $text;
        }
        ```
    *   **Sau (Code mới tại [AdviseController.php:L766-L782](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdviseController.php#L766-L782)):**
        *   **Cải tiến Regex:** Hỗ trợ che SĐT định dạng rộng hơn (bao gồm mã quốc gia `+84`, phân tách bởi khoảng trắng, chấm, gạch ngang) và che số CCCD (12 chữ số) và CMND cũ (9 chữ số).
            ```php
            private function anonymizeText($text) {
                $text = (string) $text;
                $text = preg_replace('/(?:\+84|0[235789])(?:[\s.-]?\d){8,9}\b/', '[Số điện thoại]', $text);
                $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[Email]', $text);
                $text = preg_replace('/\b(?:\d{12}|\d{9})\b/', '[Số CCCD/CMND]', $text);
                return $text;
            }
            ```
        *   **Cập nhật Prompt hệ thống:** Thêm chỉ thị PII Safeguard vào `$additionalInstructions` tại [AdviseController.php:L177](file:///C:/laragon/www/tuyensinhtest/app/Http/Controllers/AdviseController.php#L177) để ép OpenAI Assistant tự động ẩn thông tin cá nhân nhạy cảm trong phản hồi.
