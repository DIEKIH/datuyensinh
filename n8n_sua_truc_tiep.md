# Hướng dẫn chi tiết cập nhật N8N để xử lý Like/Share

Dưới đây là các bước chi tiết bạn cần thao tác trực tiếp trên giao diện của N8N để hệ thống bắt được điểm tương tác Like/Share, lấy tên người dùng và bỏ qua Bot chat nếu chỉ là like/share:

## 1. Sửa Node: "Transform FB Message"
Mở Node **Transform FB Message**, thay thế TOÀN BỘ đoạn code Javascript hiện tại bằng đoạn code dưới đây:

```javascript
const body = $input.first().json.body || {};

let text = '';
let senderId = '';
let senderName = '';
let interaction_type = 'chat';
let is_comment = false;
let comment_id = '';
let post_id = '';

if (body.entry && body.entry[0]) {
    let entry = body.entry[0];
    
    // Check for messages (Tin nhắn Messenger)
    if (entry.messaging && entry.messaging[0]) {
        let msg = entry.messaging[0];
        text = msg.message ? msg.message.text : '';
        senderId = msg.sender ? msg.sender.id : '';
        interaction_type = 'chat';
    } 
    // Check for feed (Bình luận, Thích, Chia sẻ)
    else if (entry.changes && entry.changes[0]) {
        let change = entry.changes[0];
        
        // CHỈ XỬ LÝ khi hành động là THÊM (add) (Bỏ qua xóa/edit để tránh lỗi)
        if (change.field === 'feed' && change.value && change.value.verb === 'add') {
            let itemType = change.value.item;
            
            if (itemType === 'comment') {
                interaction_type = 'comment';
                text = change.value.message || '';
                is_comment = true;
            } else if (itemType === 'like' || itemType === 'reaction') {
                interaction_type = 'like';
            } else if (itemType === 'share') {
                interaction_type = 'share';
            }
            
            // Lấy thông tin người gửi
            senderId = change.value.from ? change.value.from.id : '';
            senderName = change.value.from ? change.value.from.name : '';
            comment_id = change.value.comment_id || '';
            post_id = change.value.post_id || '';
        }
    }
}

return [
    {
        json: {
            body: {
                event_type: 'incoming_message',
                data: {
                    message: text,
                    sender_id: senderId,
                    sender_name: senderName,
                    is_comment: is_comment,
                    interaction_type: interaction_type,
                    comment_id: comment_id,
                    post_id: post_id,
                    raw_fb: body
                }
            }
        }
    }
];
```

## 2. Sửa Node: "Gọi Laravel RAG" (Code gọi Webhook /api/n8n/leads)
Mở Node **Gọi Laravel RAG**, cập nhật lại tham số truyền vào API (thêm biến `interaction_type` và `sender_name`). Thay thế toàn bộ JS Code bằng đoạn dưới đây:

```javascript
const items = $input.all();
const token = 'your_secret_token_here';
const transformNode = $('Transform FB Message').first();

for (const item of items) {
  const originalData = transformNode ? transformNode.json.body?.data : {};
  const message = originalData?.message || item.json.message || '';
  const sender_id = originalData?.sender_id || item.json.sender_id || '';
  const sender_name = originalData?.sender_name || '';
  const interaction_type = originalData?.interaction_type || 'chat';
  const is_comment = originalData?.is_comment || false;
  const comment_id = originalData?.comment_id || '';

  // Bỏ qua nếu không có sender_id hợp lệ
  if (!sender_id) continue;

  try {
    const response = await this.helpers.httpRequest({
      method: 'POST',
      url: 'http://tuyensinhtest.test/api/n8n/leads',
      headers: {
        'X-N8N-Token': token,
        'Content-Type': 'application/json'
      },
      body: {
        question: message,
        sender_id: sender_id,
        sender_name: sender_name,
        interaction_type: interaction_type,
        is_comment: is_comment,
        comment_id: comment_id
      },
      json: true
    });
    
    item.json.rag_response = response;
    
    // Chỉ ghi đè message RAG nếu không phải là Like/Share (Vì Like/Share Laravel sẽ bỏ qua RAG)
    if (interaction_type !== 'like' && interaction_type !== 'share') {
      if (response && response.data && response.data.answer) {
        item.json.message = response.data.answer;
      }
    }
  } catch (err) {
    item.json.rag_error = err.message;
    if (interaction_type !== 'like' && interaction_type !== 'share') {
        item.json.message = 'Lỗi khi gọi AI: ' + err.message;
    }
  }
}

return items;
```

---
**LƯU Ý:**
Mã nguồn Laravel bên trong API `/api/n8n/leads` (`AdmissionWebhookController::upsertLead`) mình đã update sẵn rồi! Nó sẽ nhận được cái `interaction_type` này, tự động log vào history (hoạt động), gọi `rescore` tự động chấm điểm, và **nếu là like/share nó sẽ tự ngắt ngang không thèm trả lời Bot AI nữa.** 

Đồng thời nếu khách bấm Like/Share mà chưa hề có trong danh sách Lead, nó sẽ tự bế cái Tên thật trên Facebook (từ `sender_name`) vào CSDL để bạn đọc mượt mà luôn, tránh bị trống Tên khách hàng!
