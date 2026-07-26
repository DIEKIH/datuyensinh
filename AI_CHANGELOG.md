# Lịch sử thay đổi & Kiến trúc hệ thống AI (RAG, n8n, Chatbot)

Tài liệu này ghi chú lại cấu trúc hệ thống, các file mã nguồn cốt lõi và các cập nhật liên quan đến Chatbot AI, RAG, Webhook n8n và các script thử nghiệm.

## 1. Cơ chế Chatbot và Sử dụng lại lịch sử tin nhắn (Contextual Memory)
Chatbot được trang bị khả năng ghi nhớ cuộc hội thoại để hiểu ngữ cảnh của các câu hỏi nối tiếp.
- **Cơ chế hoạt động**: Sử dụng `Cache` của Laravel để lưu trữ lịch sử tin nhắn dựa trên `session_id`. Hệ thống tự động lấy tối đa 10 tin nhắn gần nhất để nạp vào ngữ cảnh.
- **Tính năng Viết lại câu hỏi (Query Rewriting)**:
  - Nằm tại hàm `rewriteQuery()` trong `AdmissionRagService`. 
  - *Ví dụ*: Người dùng hỏi "Học phí ngành CNTT?", AI trả lời. Sau đó người dùng hỏi tiếp "Có học bổng không?". Dựa vào lịch sử tin nhắn, hệ thống sẽ gọi AI để dịch câu "Có học bổng không?" thành "Học bổng ngành Công nghệ thông tin có không?" trước khi đem đi tìm kiếm trong kho RAG. Điều này giúp lấy tài liệu chuẩn xác 100%.
- **Lệnh Reset**: Hỗ trợ người dùng xóa ngữ cảnh thông qua các từ khóa đặc biệt như `reset`, `clear chat`, `bắt đầu lại`.

## 2. Các file xử lý nghiệp vụ (Services)
Nằm tại `app/Services/`:
- **`AdmissionRagService.php` (Trái tim của AI)**:
  - Xử lý việc chia nhỏ file tài liệu tải lên thành các đoạn (chunk) với cửa sổ trượt (overlap 200 ký tự).
  - Tìm kiếm tài liệu RAG kết hợp giữa *Keyword Search* và *Full-Text Search (FTS)*.
  - **MỚI (15/07/2026)**: Tự động gộp các chunk lại để trả về **toàn bộ nội dung file gốc** (`document_content`) cho AI và n8n đọc, kết hợp loại bỏ trùng lặp (`->unique('document_id')`), giúp AI không bị lặp chữ và hiểu toàn cảnh văn bản.
  - Xây dựng Prompt (lời nhắc) chi tiết hướng dẫn AI tư vấn, bảo vệ thông tin cá nhân và kêu gọi để lại liên hệ (Call-to-Action).
- **`AdmissionLeadScoringService.php` (Trí tuệ nhân tạo tính điểm)**:
  - Phân loại và tự động chấm điểm khách hàng tiềm năng dựa trên số lần tương tác, nguồn đến, và ngành học quan tâm.
  - Gắn nhãn **Hot / Warm / Cold** để gửi cho n8n xử lý các kịch bản nuôi dưỡng lead khác nhau.

## 3. Các Controller tích hợp (Admission & n8n)
Nằm tại `app/Http/Controllers/`:
- **`AdmissionWebhookController.php` (Cổng giao tiếp với n8n)**: 
  - Cung cấp các API (`/api/n8n/leads`, `/api/n8n/rag/answer`, `/api/n8n/notifications`) để hệ thống n8n bên ngoài đẩy dữ liệu chat từ Facebook/Zalo vào CMS, và nhận lại câu trả lời RAG của hệ thống.
  - Đồng thời tự động thu thập thông tin (SĐT, Email, Tên) từ tin nhắn của người dùng trong quá trình chat để tạo Lead.
- **`AdmissionAdminController.php` (Quản trị viên RAG & Leads)**: 
  - Cho phép trường học upload tài liệu, tự động kích hoạt Service băm nhỏ (chunking) tài liệu. Xem thống kê dữ liệu.
- **`AdmissionLeadController.php` (Landing Page)**:
  - Xử lý biểu mẫu đăng ký từ web, bắt các biến UTM để phân tích nguồn quảng cáo.
  - Trigger Job `SendN8NWebhook` để gửi thông báo bất đồng bộ sang hệ thống n8n.
- **`AdminController.php` & `AdviseController.php`**: 
  - Nơi gọi các Webhook bất đồng bộ. Chứa hàm `anonymizeText` để bọc ẩn các số CMND/CCCD/SĐT, bảo vệ tính riêng tư trước khi đưa lên OpenAI. Cụ thể có xử lý xóa tin nhắn mồ côi nếu kết nối OpenAI lỗi.

## 4. Khu vực thử nghiệm và Debug (`/scratch/`)
Thư mục `scratch/` chứa rất nhiều kịch bản (scripts) giả lập dữ liệu, được sử dụng trong suốt quá trình xây dựng AI:
- **Giả lập JSON của n8n**: `test_lead.json`, `test_notification.json`, `test.json` chứa các mẫu dữ liệu mô phỏng request từ n8n để test webhook.
- **Môi trường Debug AI và Search**: Hàng loạt file như `test_keyword_search.php`, `debug_search.php`, `test_boost_search.php`, `test_answer.php` được dùng để chạy test trực tiếp các kịch bản tìm kiếm của hệ thống RAG và mô phỏng OpenAI ngay trên terminal mà không cần khởi động Web server.
- **Tiện ích DB**: `recreate_indexes.php`, `check_db_docs.php`, `show_context.php` dùng để sửa lỗi database và xem trước ngữ cảnh được nạp vào AI.

---
*Ghi chú: Bản ghi này tóm lược chi tiết toàn bộ kiến trúc lõi được xây dựng. Việc tương tác đa nền tảng kết hợp giữa CMS (Laravel) ⇆ Automation (n8n) ⇆ AI (OpenAI + RAG) được thiết kế xoay quanh các file cốt lõi ở trên.*
