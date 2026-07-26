Hãy đọc và chỉnh sửa trực tiếp file workflow n8n JSON tôi đã cung cấp.

Mục tiêu là hoàn thiện xử lý hội thoại, tạo lead, chấm điểm lead, gửi email và kết nối với chức năng kiểm tra khả năng trúng tuyển.

## 1. Quy định bắt buộc

* Không được sửa nghiệp vụ, node, kết nối hoặc cấu hình của luồng 0 `new_article`.
* Giữ nguyên chức năng đăng bài Facebook hiện tại.
* Không tự tạo thêm nghiệp vụ ngoài các yêu cầu bên dưới.
* Không tự đặt công thức chấm điểm lead.
* Không tự đặt ngưỡng Hot Lead, Warm Lead hoặc Cold Lead khi tôi chưa cung cấp tiêu chí.
* Không tự tính khả năng trúng tuyển trong n8n.
* Việc tính điểm và kiểm tra điều kiện trúng tuyển phải gọi sang Laravel.
* Không ghi Access Token, mật khẩu hoặc API key trực tiếp trong Code node.
* Chuyển token sang n8n Credentials hoặc biến môi trường.
* Không sử dụng URL API giả như thể API đó đã tồn tại.
* Nếu Laravel chưa có endpoint cần thiết, sử dụng tên biến hoặc placeholder rõ ràng và liệt kê chính xác API mà Laravel cần bổ sung.

## 2. Luồng 1 – `incoming_message`

Giữ nguyên chức năng reply hiện có, đồng thời rà soát và hoàn thiện xử lý cho:

* Facebook Messenger.
* Facebook Comment.
* Zalo OA.

Chuẩn hóa dữ liệu đầu vào về một cấu trúc thống nhất, gồm tối thiểu:

* `channel`
* `sender_id`
* `conversation_id`
* `message_id`
* `comment_id`
* `post_id`
* `message`
* `media_url`
* `received_at`

Sau khi nhận tin nhắn, hệ thống cần:

1. Làm sạch dữ liệu đầu vào.
2. Xác định kênh gửi đến.
3. Trích xuất các thông tin thí sinh có trong nội dung:

   * Họ tên.
   * Số điện thoại.
   * Email.
   * Địa chỉ hoặc tỉnh/thành phố.
   * Trường THPT.
   * Ngành quan tâm.
   * Phương thức xét tuyển.
   * Tổ hợp xét tuyển.
   * Điểm từng môn.
   * Điểm học bạ hoặc điểm thi.
4. Không được tự suy diễn thông tin không xuất hiện trong hội thoại.
5. Nếu có số điện thoại, email hoặc nhu cầu tư vấn rõ ràng thì gửi dữ liệu sang Laravel để tạo hoặc cập nhật lead.
6. Lưu liên kết giữa lead và nguồn phát sinh:

   * Facebook Messenger.
   * Facebook Comment.
   * Zalo.
   * Form đăng ký.
7. Nếu thí sinh hỏi về điểm hoặc khả năng trúng tuyển:

   * Kiểm tra dữ liệu hiện có đã đủ chưa.
   * Nếu chưa đủ, chỉ hỏi những trường còn thiếu.
   * Nếu đang trao đổi tại Facebook Comment công khai, không yêu cầu thí sinh đăng số điện thoại, email hoặc điểm cá nhân công khai. Hướng dẫn chuyển sang Messenger hoặc mở form đăng ký.
   * Nếu đủ dữ liệu, gọi API Laravel để kiểm tra khả năng trúng tuyển.
   * Dùng kết quả Laravel trả về để tạo câu trả lời.
   * Không để AI tự tính hoặc tự kết luận khả năng trúng tuyển.
8. Những câu hỏi tuyển sinh thông thường tiếp tục gọi node Laravel RAG hiện có.
9. Kết quả cuối cùng phải quay lại đúng kênh đã gửi:

   * Messenger trả về Messenger.
   * Facebook Comment trả lời đúng comment.
   * Zalo trả về đúng người dùng Zalo.

Hoàn thiện node `Zalo OA Reply` với đầy đủ method, header, body và xử lý lỗi. Nếu thiếu Zalo OA Access Token hoặc thông tin API thì không được tự tạo token; phải ghi rõ cấu hình còn thiếu.

Sửa phần phân tích cảm xúc:

* Prompt phải nhận nội dung tin nhắn thực tế.
* Chuẩn hóa kết quả thành các giá trị rõ ràng.
* Khi cần nhân viên can thiệp, vẫn phải lưu hội thoại và tạo/cập nhật lead trước khi kết thúc luồng.
* Không để nhánh cảnh báo làm mất việc reply hoặc ghi nhận lead.

## 3. Kết nối với form đăng ký

n8n không tạo giao diện form.

Form sẽ được xây dựng trong Laravel và cho phép thí sinh nhập thủ công các thông tin cần thiết để kiểm tra khả năng trúng tuyển.

n8n cần nhận sự kiện từ Laravel sau khi thí sinh gửi form, gồm tối thiểu:

* Thông tin liên hệ.
* Ngành đăng ký.
* Phương thức xét tuyển.
* Tổ hợp.
* Điểm.
* Kết quả kiểm tra từ Laravel.
* Mã lead.
* Mã lần kiểm tra.
* Nguồn đăng ký.

Lead được tạo từ form phải có thể liên kết với lead đã tồn tại từ Messenger, Facebook Comment hoặc Zalo.

## 4. Luồng 2 – `new_lead`

Rà soát và hoàn thiện các node hiện có của luồng `new_lead`.

### 4.1. Lọc trùng

Ưu tiên xác định lead trùng theo thứ tự:

1. Số điện thoại đã chuẩn hóa.
2. Email đã chuẩn hóa.
3. Kết hợp `channel` và `sender_id`.

Không xóa lead cũ. Nếu đã tồn tại thì cập nhật thông tin mới, nguồn mới và lịch sử tương tác.

### 4.2. Chuẩn hóa lead

Hoàn thiện node `Format Lead Data` để tạo cấu trúc thống nhất, gồm:

* Thông tin liên hệ.
* Nguồn phát sinh.
* Ngành quan tâm.
* Dữ liệu xét tuyển đã cung cấp.
* Kết quả kiểm tra khả năng trúng tuyển.
* ID hội thoại hoặc comment.
* Thời gian tương tác.
* Trạng thái chăm sóc.
* Điểm lead nếu Laravel đã trả về.

### 4.3. Chấm điểm lead

Không tự tạo công thức chấm điểm.

Node `Check Lead Score` phải sử dụng điểm do Laravel trả về hoặc sử dụng các biến cấu hình:

* `<HOT_LEAD_THRESHOLD>`
* `<WARM_LEAD_THRESHOLD>`

Nếu chưa có công thức hoặc endpoint chấm điểm lead thì giữ node ở trạng thái chưa kích hoạt và ghi rõ dữ liệu còn thiếu.

Phân biệt rõ:

* `lead_score`: mức độ tiềm năng cần ưu tiên tư vấn.
* `admission_result`: kết quả kiểm tra khả năng trúng tuyển.

Không dùng điểm xét tuyển làm trực tiếp điểm lead.

### 4.4. Xử lý sau khi tạo lead

* Lưu lead vào Laravel làm nguồn dữ liệu chính.
* Nếu là Hot Lead, gửi cảnh báo cho bộ phận tư vấn.
* Nếu có email hợp lệ, mới thực hiện gửi email.
* Email phải có người nhận, nội dung và dữ liệu cá nhân hóa đầy đủ.
* Sau 24 giờ, gọi API Laravel kiểm tra lead đã được liên hệ chưa.
* Nếu chưa được liên hệ, mới gửi email chăm sóc theo cấu hình.
* Không tự bật Google Sheets nếu chưa được yêu cầu.
* Không gửi email khi thiếu địa chỉ email hoặc chưa có cấu hình SMTP.
### 4.4. Giao diện quản lý quy tắc chấm điểm lead

Xây dựng trong Laravel một giao diện dành cho quản trị viên để cấu hình quy tắc chấm điểm lead. Không xây dựng giao diện này trực tiếp trong n8n.

Giao diện phải cho phép quản trị viên:

* Xem danh sách toàn bộ tiêu chí chấm điểm.
* Thêm tiêu chí mới.
* Chỉnh sửa tiêu chí.
* Xóa hoặc vô hiệu hóa tiêu chí.
* Bật hoặc tắt từng tiêu chí.
* Sắp xếp thứ tự ưu tiên áp dụng.
* Xem điểm được cộng hoặc trừ của từng tiêu chí.
* Cấu hình ngưỡng phân loại Hot Lead, Warm Lead và Cold Lead.
* Kiểm tra thử kết quả chấm điểm trước khi áp dụng chính thức.

Mỗi tiêu chí chấm điểm cần có tối thiểu các trường:

* `criterion_code`: mã tiêu chí duy nhất.
* `criterion_name`: tên tiêu chí hiển thị.
* `description`: mô tả ý nghĩa.
* `data_field`: trường dữ liệu lead được kiểm tra.
* `operator`: phép so sánh.
* `comparison_value`: giá trị dùng để so sánh.
* `score`: số điểm được cộng hoặc trừ.
* `priority`: thứ tự áp dụng.
* `is_active`: trạng thái đang sử dụng.
* `effective_from`: thời điểm bắt đầu áp dụng, nếu có.
* `effective_to`: thời điểm kết thúc áp dụng, nếu có.

Các phép so sánh phải được chọn từ danh sách được Laravel định nghĩa sẵn, ví dụ:

* Bằng.
* Khác.
* Lớn hơn.
* Lớn hơn hoặc bằng.
* Nhỏ hơn.
* Nhỏ hơn hoặc bằng.
* Có giá trị.
* Không có giá trị.
* Chứa một giá trị.
* Thuộc một danh sách giá trị.

Không cho quản trị viên nhập trực tiếp mã PHP, JavaScript, SQL hoặc biểu thức có thể thực thi trên hệ thống.

Giao diện cấu hình ngưỡng phân loại cần cho phép nhập:

* Điểm tối thiểu của Hot Lead.
* Điểm tối thiểu của Warm Lead.
* Các lead còn lại được phân loại là Cold Lead.
* Trạng thái áp dụng của bộ quy tắc.

Laravel phải kiểm tra để các ngưỡng không bị chồng chéo hoặc thiết lập sai thứ tự.

Giao diện cần có chức năng kiểm tra thử. Quản trị viên có thể nhập hoặc chọn một lead mẫu để xem:

* Tổng điểm lead.
* Mức phân loại.
* Những tiêu chí đã thỏa mãn.
* Điểm cộng hoặc trừ của từng tiêu chí.
* Những tiêu chí không được áp dụng.
* Kết quả kiểm tra khả năng trúng tuyển liên quan, nếu lead đã có kết quả.

Phải phân biệt rõ:

* `lead_score`: tổng điểm đánh giá mức độ tiềm năng của lead.
* `lead_level`: Hot, Warm hoặc Cold.
* `admission_result`: kết quả kiểm tra khả năng trúng tuyển.

Không dùng trực tiếp điểm thi, điểm học bạ hoặc điểm xét tuyển làm `lead_score` nếu chưa có tiêu chí do quản trị viên cấu hình.

Quy tắc chấm điểm phải được lưu trong database Laravel. Không hard-code tiêu chí, số điểm hoặc ngưỡng phân loại trong workflow n8n.

Khi cần chấm điểm, n8n gửi dữ liệu lead sang API Laravel. Laravel có trách nhiệm:

1. Lấy bộ quy tắc đang được kích hoạt.
2. Kiểm tra lần lượt các tiêu chí.
3. Tính tổng điểm.
4. Xác định mức Hot, Warm hoặc Cold.
5. Lưu kết quả chấm điểm.
6. Trả kết quả về n8n.

Kết quả API chấm điểm cần trả về tối thiểu:

* `lead_id`
* `lead_score`
* `lead_level`
* `matched_criteria`
* `unmatched_criteria`
* `scored_at`
* `rule_version`

Trong đó `matched_criteria` phải cho biết:

* Mã tiêu chí.
* Tên tiêu chí.
* Giá trị thực tế của lead.
* Phép so sánh đã sử dụng.
* Giá trị so sánh.
* Số điểm được cộng hoặc trừ.

Khi quản trị viên thay đổi bộ quy tắc:

* Các lần chấm điểm mới sử dụng cấu hình mới.
* Kết quả chấm điểm cũ không được tự động thay đổi.
* Mỗi kết quả phải lưu phiên bản bộ quy tắc đã sử dụng.
* Không tự động chấm lại toàn bộ lead cũ nếu quản trị viên chưa thực hiện yêu cầu chấm lại.

Cần bổ sung API Laravel cho các chức năng:

* Lấy danh sách tiêu chí.
* Tạo tiêu chí.
* Cập nhật tiêu chí.
* Xóa hoặc vô hiệu hóa tiêu chí.
* Cập nhật ngưỡng phân loại.
* Kiểm tra thử bộ quy tắc.
* Chấm điểm một lead.
* Chấm lại một lead theo yêu cầu của quản trị viên.

Tên endpoint cụ thể phải tuân theo cấu trúc API hiện tại của dự án. Không tự giả định rằng các endpoint này đã tồn tại.

Node `Check Lead Score` trong n8n không tự tính điểm. Node này chỉ sử dụng kết quả `lead_score` và `lead_level` do API Laravel trả về để phân nhánh xử lý tiếp theo.


## 5. Xử lý lỗi và bảo mật

* Thêm kiểm tra thiếu `sender_id`, `comment_id`, email, số điện thoại và dữ liệu xét tuyển.
* Không để lỗi một kênh làm dừng toàn bộ workflow.
* Không ghi thông tin nhạy cảm hoặc token vào log.
* Không trả lại số điện thoại, email hoặc dữ liệu cá nhân trong Facebook Comment công khai.
* Các request sang Laravel phải sử dụng token từ Credentials hoặc biến môi trường.
* Không để chuỗi `your_secret_token_here` trong bản hoàn chỉnh.

## 6. Kết quả cần trả về

Sau khi sửa, hãy cung cấp:

1. File JSON n8n hoàn chỉnh, có thể import lại.
2. Danh sách chính xác các node đã sửa.
3. Danh sách node mới được thêm, nếu thực sự cần thiết.
4. Mô tả kết nối giữa luồng 1 và luồng 2.
5. Cấu trúc payload gửi sang Laravel cho:

   * Tạo hoặc cập nhật lead.
   * Chấm điểm lead.
   * Kiểm tra khả năng trúng tuyển.
   * Kiểm tra trạng thái đã liên hệ.
6. Danh sách API, credentials và biến môi trường còn thiếu.
7. Danh sách chức năng đã hoạt động và chức năng chưa thể hoạt động do thiếu backend hoặc credentials.
8. Không tuyên bố node đã hoạt động nếu node vẫn chỉ là placeholder.
9. Giữ nguyên ID các node hiện tại nếu không bắt buộc phải thay đổi.
10. Ghi riêng phần “Nội dung đã sửa” để tôi dễ kiểm tra.
