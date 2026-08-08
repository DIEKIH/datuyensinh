# Hướng dẫn cấu hình n8n kiểm duyệt Comment Tiêu Cực (Toxic)

Bạn hoàn toàn có thể tái sử dụng **Luồng (Workflow) Master hiện tại** thay vì tạo luồng mới, điều này giúp hệ thống liên kết và gọn gàng hơn rất nhiều!

Dưới đây là cách thực hiện:

---

## Phần 1: Tự động Ẩn bình luận tiêu cực ngay khi AI phát hiện (Trong Master Workflow)

Trong luồng Master của bạn, ở nhánh xử lý **Facebook Comment**, sau khi chạy qua **Node AI (Gemini/OpenAI)** để phân loại sắc thái, hãy tìm đến Node Switch (rẽ nhánh Tiêu cực / Bình thường).

**Tại nhánh "Tiêu cực (Toxic)":**
1. Trích xuất lấy ID của comment (Thường nằm ở biến `{{ $json.body.entry[0].changes[0].value.comment_id }}`).
2. **THÊM NGAY một Node HTTP Request** (Hoặc dùng Node Facebook Graph API) trước khi gọi Webhook về Laravel.
3. **Cấu hình Node HTTP Request Ẩn Comment (Hide):**
   - **Method:** `POST`
   - **URL:** `https://graph.facebook.com/v18.0/{{biến_comment_id}}`
   - **Query Parameters:** 
     - Tên: `is_hidden` | Giá trị: `true`
     - Tên: `access_token` | Giá trị: `(Mã Page Access Token của bạn)`
4. **Bắn Webhook về Laravel:** Cuối cùng, nối tiếp luồng vào 1 Node HTTP Request để bắn thông tin về Laravel lưu vào CMS:
   - **Method:** `POST`
   - **URL:** `https://[domain-cua-ban]/api/n8n/toxic-comments`
   - **Headers:** `Authorization` hoặc API Key (tùy bạn cấu hình `VerifyN8nWebhook`).
   - **Body JSON:** 
     ```json
     {
       "platform": "facebook",
       "comment_id": "{{biến_comment_id}}",
       "post_id": "{{biến_post_id}}",
       "sender_id": "{{biến_người_dùng}}",
       "sender_name": "{{biến_tên_người_dùng}}",
       "message": "{{nội_dung_chửi}}",
       "sentiment_category": "toxic",
       "ai_reason": "Lý do AI chẩn đoán..."
     }
     ```

*Kết quả:* Vừa có người chửi, comment lập tức "bay màu" với người ngoài, nhưng hệ thống và Admin vẫn lưu lại để xử lý.

---

## Phần 2: Xử lý lệnh Xóa / Bỏ ẩn từ Admin CMS (Tích hợp vào Master Router)

Thay vì tạo luồng mới, Web Laravel sẽ gửi lệnh Xóa/Bỏ Ẩn thẳng vào **Webhook Master** của bạn. Code Laravel mình vừa sửa đã chèn thêm biến `source: "admin_cms"` để n8n phân biệt.

**Cách sửa luồng Master:**
1. **Lấy URL Webhook Master** (Cái mà Facebook, Zalo đang bắn vào).
   Mở file `.env` của Laravel và đổi: 
   `N8N_TOXIC_ACTION_WEBHOOK_URL="[ĐƯỜNG DẪN WEBHOOK MASTER CỦA BẠN]"`

2. **Sửa Node Router (Switch) đầu tiên (sau Webhook)**
   - Bạn thêm 1 điều kiện (Rule) mới vào Router: 
     **Nếu** `{{ $json.body.source }}` **Bằng (Equal)** `admin_cms`
   - Nhánh mới này sẽ là nhánh dành riêng cho các lệnh từ Web gửi sang.

3. **Xử lý nhánh `admin_cms` vừa tạo:**
   - Tại output của nhánh này, thêm 1 Node **Switch** để kiểm tra hành động:
     - Biến cần kiểm tra: `{{ $json.body.action }}`
     
     **Nhánh 0 (Nếu Action = `unhide`):**
     - Thêm Node **HTTP Request**
     - **Method:** `POST`
     - **URL:** `https://graph.facebook.com/v18.0/{{ $json.body.comment_id }}`
     - **Query Parameters:** `is_hidden` = `false`, kèm `access_token`.
     - (Ý nghĩa: Mở khóa hiển thị lại bình luận này ra công khai).
   
     **Nhánh 1 (Nếu Action = `delete`):**
     - Thêm Node **HTTP Request**
     - **Method:** `DELETE`
     - **URL:** `https://graph.facebook.com/v18.0/{{ $json.body.comment_id }}`
     - **Query Parameters:** kèm `access_token`.
     - (Ý nghĩa: Tiêu diệt vĩnh viễn bình luận này khỏi bài post).

4. **Respond to Webhook**
   - Đảm bảo nhánh này cũng nối vào Node Respond to Webhook (trả về 200 OK) để Laravel biết N8N đã xử lý xong.

---
**Tóm tắt quá trình:** Khách gõ chửi -> Master Webhook nhận -> Nhánh FB xử lý AI -> N8N Ẩn comment -> Báo về Web. Admin xem trên Web bấm nút [Xóa] -> Web gọi lại Master Webhook (`source=admin_cms`) -> Master Router chia vào nhánh Xóa -> N8N gọi FB Xóa vĩnh viễn. Cực kỳ liên kết và gọn gàng!
