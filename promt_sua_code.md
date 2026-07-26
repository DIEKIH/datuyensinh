Hãy kiểm tra và sửa workflow n8n tích hợp Facebook Messenger Webhook của tôi.

Bối cảnh hiện tại:

- n8n đang chạy tại:

http://localhost:5678

- Tôi đang dùng Cloudflare Quick Tunnel:

https://coordinate-way-bend-ivory.trycloudflare.com

- Tunnel được chạy bằng lệnh:

cloudflared tunnel --url http://localhost:5678

- URL Webhook khai báo trong Meta:

https://coordinate-way-bend-ivory.trycloudflare.com/webhook/facebook-messenger

- Verify Token:

ctut

Khi kiểm tra bằng lệnh:

curl.exe -i "https://coordinate-way-bend-ivory.trycloudflare.com/webhook/facebook-messenger?hub.mode=subscribe&hub.verify_token=ctut&hub.challenge=123456"

n8n trả về:

HTTP/1.1 404 Not Found

{
  "code": 404,
  "message": "The requested webhook \"GET facebook-messenger\" is not registered.",
  "hint": "The workflow must be active for a production URL to run successfully."
}

Điều này cho thấy Cloudflare Tunnel đã chuyển request đúng đến n8n, nhưng Production Webhook GET chưa được đăng ký.

Yêu cầu sửa chính xác như sau.

1. Tạo hoặc sửa node xác minh Facebook Webhook

Node phải có cấu hình:

- Node type: Webhook
- Name: Facebook Messenger Verify
- HTTP Method: GET
- Path: facebook-messenger
- Authentication: None
- Respond: Using Respond to Webhook Node

Không nhập `/webhook/facebook-messenger` trong ô Path.

Ô Path chỉ được nhập:

facebook-messenger

Production URL phải tương ứng với:

https://coordinate-way-bend-ivory.trycloudflare.com/webhook/facebook-messenger

2. Thêm node kiểm tra Verify Token

Sau Webhook GET, thêm Code node tên:

Verify Facebook Token

Sử dụng code:

const query = $json.query ?? {};

const mode = query['hub.mode'];
const verifyToken = query['hub.verify_token'];
const challenge = query['hub.challenge'];

if (
    mode === 'subscribe'
    && verifyToken === 'ctut'
    && challenge
) {
    return [
        {
            json: {
                statusCode: 200,
                responseBody: String(challenge),
            },
        },
    ];
}

return [
    {
        json: {
            statusCode: 403,
            responseBody: 'Invalid verify token',
        },
    },
];

Không đổi Verify Token `ctut` thành giá trị khác.

3. Thêm node Respond to Webhook

Sau Code node, thêm node:

Respond to Webhook

Cấu hình:

- Respond With: Text
- Response Code:

={{ $json.statusCode }}

- Response Body:

={{ $json.responseBody }}

Khi xác minh hợp lệ, response bắt buộc phải là text thuần:

123456

Không được trả JSON như:

{
  "challenge": "123456"
}

Không được thêm chữ như:

Xác minh thành công

4. Luồng xác minh phải đúng

Luồng GET bắt buộc là:

Facebook Messenger Verify
→ Verify Facebook Token
→ Respond to Webhook

5. Workflow phải được Active hoặc Published

Sau khi sửa:

- Save workflow.
- Publish hoặc Activate workflow.
- Nếu workflow đang Active thì tắt rồi bật lại để n8n đăng ký lại Production Webhook.
- Không chỉ dùng Test Workflow.
- Không dùng URL `/webhook-test/facebook-messenger` trong Meta.

Meta đang sử dụng Production URL:

/webhook/facebook-messenger

Vì vậy workflow bắt buộc phải Active.

6. Tạo Webhook riêng để nhận sự kiện Messenger

Sau khi xác minh GET hoạt động, cần một Webhook node riêng cho sự kiện Facebook gửi đến.

Cấu hình:

- Node type: Webhook
- Name: Facebook Messenger Events
- HTTP Method: POST
- Path: facebook-messenger
- Authentication: None
- Respond: Immediately
- Response Code: 200

Hai Webhook được phép cùng Path nhưng khác HTTP Method:

GET /webhook/facebook-messenger
POST /webhook/facebook-messenger

Ý nghĩa:

- GET dùng để Meta xác minh Webhook.
- POST dùng để nhận tin nhắn và sự kiện Messenger.

Không gộp logic xác minh GET và xử lý POST vào cùng một node Webhook nếu n8n yêu cầu mỗi node chỉ có một HTTP Method.

7. Kiểm tra dữ liệu POST trước khi xây luồng xử lý tin nhắn

Ở node nhận POST, tạm thời chỉ:

- Nhận payload.
- Trả HTTP 200 ngay.
- Ghi lại payload để kiểm tra cấu trúc.
- Không gửi tin nhắn trả lời Facebook cho đến khi xác nhận đúng cấu trúc payload thực tế.

Không tự vẽ thêm luồng xử lý nghiệp vụ ngoài yêu cầu này.

8. Kiểm thử bắt buộc

Sau khi workflow đã Active, chạy:

curl.exe -i "https://coordinate-way-bend-ivory.trycloudflare.com/webhook/facebook-messenger?hub.mode=subscribe&hub.verify_token=ctut&hub.challenge=123456"

Kết quả đúng phải là:

HTTP/1.1 200 OK

123456

Các trường hợp lỗi cần xử lý:

- Nếu 404:
  Production Webhook GET chưa được đăng ký, workflow chưa Active, sai Method hoặc sai Path.

- Nếu 403:
  Verify Token trong n8n không trùng với `ctut`.

- Nếu HTTP 200 nhưng trả JSON:
  Node Respond to Webhook đang trả sai kiểu dữ liệu.

- Nếu timeout hoặc 502:
  Kiểm tra Cloudflare Tunnel và tiến trình n8n.

9. Sau khi kiểm thử curl thành công

Sử dụng trong Meta:

URL gọi lại:

https://coordinate-way-bend-ivory.trycloudflare.com/webhook/facebook-messenger

Xác minh mã:

ctut

Sau đó bấm:

Xác minh và lưu

10. Yêu cầu đầu ra

Sau khi sửa, hãy cung cấp:

- Danh sách node đã tạo hoặc đã sửa.
- Cấu hình đầy đủ của từng node.
- Code đầy đủ trong Code node.
- Sơ đồ kết nối giữa các node.
- File workflow n8n JSON hoàn chỉnh có thể import.
- Xác nhận workflow dùng Production Webhook.
- Không thay đổi các nhánh workflow khác không liên quan.
- Không hard-code Facebook Access Token trong URL hoặc Code node.
- Không thêm nghiệp vụ mới ngoài xác minh GET và nhận POST.
- Kiểm tra JSON workflow hợp lệ trước khi kết luận hoàn thành.