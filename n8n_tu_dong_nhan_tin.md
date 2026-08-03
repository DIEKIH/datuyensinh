# Hướng dẫn tạo luồng N8N tự động gửi tin nhắn khi đạt điểm "Hot"

Hệ thống mã nguồn Laravel đã được mình nâng cấp. Bây giờ mỗi khi một Lead (khách hàng) vừa tương tác (Like, Share, Comment, Chat) và làm cho điểm (Lead Score) của họ tăng lên mức **Hot** (Ví dụ >= 50 điểm), hệ thống sẽ **Tự động gửi một tín hiệu (Webhook) sang N8N**.

Nhiệm vụ của bạn là tạo một luồng N8N mới để hứng tín hiệu này và "nhờ" Facebook gửi tin nhắn cho khách hàng đó!

Dưới đây là các bước thao tác trên giao diện n8n:

## Bước 1: Tạo Workflow mới trên n8n
1. Mở giao diện n8n, tạo một **Workflow** hoàn toàn mới.
2. Thêm Node đầu tiên: **Webhook**.
3. Cấu hình Node Webhook:
   - **Method:** POST
   - **Path:** `master-receiver`
   - Bấm đúp vào URL, copy **Test URL** hoặc **Production URL**. 
   *(Lưu ý: URL cuối cùng phải là `http://localhost:5678/webhook/master-receiver` vì biến môi trường `.env` trong Laravel của bạn đang cấu hình URL này tại `N8N_MASTER_WEBHOOK_URL`).*

## Bước 2: Thêm Node "IF" để lọc sự kiện
Tín hiệu Laravel gửi qua có cấu trúc JSON như sau:
```json
{
  "event_type": "lead_became_hot",
  "channel": "facebook",
  "sender_id": "123456789...",
  "lead_id": 99,
  "score": 55,
  "message": "Chúc mừng bạn! Bạn đã tương tác rất tích cực và trở thành Ứng viên tiềm năng. Chúng tôi tặng bạn 1 Voucher miễn phí xét tuyển trị giá 500k!"
}
```
1. Thêm Node **IF** ngay sau Webhook.
2. Thiết lập điều kiện:
   - `String`: `={{ $json.body.event_type }}`
   - `Equal to`: `lead_became_hot`
   - *Mục đích: Đảm bảo luồng này chỉ chạy khi có sự kiện khách hàng đạt mức Hot.*

## Bước 3: Thêm Node gửi tin nhắn qua Facebook (HTTP Request)
1. Ở nhánh `true` của Node IF, bạn nối vào một Node **HTTP Request**.
2. Đặt tên Node là: **"Send FB Message"**
3. Cấu hình Node HTTP Request:
   - **Method:** POST
   - **URL:** `https://graph.facebook.com/v18.0/me/messages`
   - **Send Query Parameters:** Bật ON
     - Name: `access_token`
     - Value: Điền mã Token Fanpage Facebook của bạn (Có thể lấy biến môi trường `={{ $env.FB_MESSENGER_TOKEN }}`).
   - **Send Body:** Bật ON
   - **Body Content Type:** JSON
   - **Body Parameters:** Tạo 2 trường dữ liệu như sau:
     - Name: `recipient`, Value: `={ "id": "{{ $json.body.sender_id }}" }` (Lưu ý chọn loại JSON/Object tùy giao diện)
     - Name: `message`, Value: `={ "text": "{{ $json.body.message }}" }`
     
*(Nếu bạn thích dùng kiểu Raw JSON trong HTTP Request thì gõ vào ô JSON Body đoạn sau)*:
```json
{
  "recipient": {
    "id": "{{ $json.body.sender_id }}"
  },
  "message": {
    "text": "{{ $json.body.message }}"
  }
}
```

## Bước 4: Lưu và Test
1. Bấm nút **Save** và **Activate** luồng này.
2. Thử lấy tài khoản của bạn, tương tác trên Fanpage (Like, Comment) vài lần để đẩy điểm Lead Scoring vượt mức 50 điểm (hoặc mốc cấu hình Hot của bạn).
3. Bùm! Ngay khi đạt điểm, N8N sẽ tự động chạy luồng này và Facebook sẽ inbox thẳng cái Voucher vào hộp thư của bạn!
