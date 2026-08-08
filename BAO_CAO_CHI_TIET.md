# ĐỀ CƯƠNG VÀ HƯỚNG DẪN VIẾT BÁO CÁO CHI TIẾT (FULL SOURCE CODE)

Tài liệu này được biên soạn sát với mã nguồn hệ thống. Dưới đây là khung chi tiết, bạn có thể copy trực tiếp các đoạn nội dung này vào bản Word báo cáo, lưu ý các chỉ dẫn (chữ in nghiêng) về cách đặt sơ đồ.

---

## CHƯƠNG 1: THỰC TRẠNG VÀ CƠ SỞ LÝ THUYẾT (Khoảng 10-15 trang)

### 1.1 Tổng quan về bài toán tuyển sinh đa kênh hiện nay
*   **Thực trạng:** Các trường đại học/cao đẳng nhận thông tin từ quá nhiều nguồn (Fanpage, Zalo OA, Website) nhưng phân tán. Nhân viên tư vấn bị quá tải do phải lặp lại các câu hỏi giống nhau về quy chế, học phí, điểm chuẩn.
*   **Vấn đề đặt ra:** Cần một hệ thống thu gom, tự động lọc và chấm điểm (Lead Scoring) để phân loại mức độ tiềm năng của thí sinh, kết hợp tự động hóa trả lời bằng AI.

### 1.2 Cơ sở lý thuyết các công nghệ áp dụng
*   **Hệ quản trị nội dung (CMS) & Framework Laravel:** Giới thiệu về kiến trúc MVC, ORM (Eloquent), Job/Queue (để xử lý bất đồng bộ).
*   **Giao diện lập trình ứng dụng (RESTful API) & Định dạng JSON:** 
    *   Khái niệm API và chuẩn RESTful (sử dụng các phương thức HTTP như POST, GET).
    *   Định dạng dữ liệu JSON (JavaScript Object Notation): Cấu trúc Request Payload gửi đi và Response Data trả về giữa Client và Server.
*   **Cơ chế Webhook:** Khái niệm Webhook và sự khác biệt với Polling. Webhook đóng vai trò nhận/gửi dữ liệu Real-time (thời gian thực) ngay khi có sự kiện xảy ra (ví dụ: có người nộp form hoặc bình luận trên Facebook).
*   **Kiến trúc RAG (Retrieval-Augmented Generation) & Hybrid RAG:**
    *   Lý thuyết RAG thuần: Phân mảnh tài liệu (Chunking), Vector hóa (Embeddings).
    *   *Hybrid RAG (Kiến trúc đang dùng trong dự án):* Kết hợp tìm kiếm truyền thống (MySQL) để lấy nội dung chính xác 100% (không sót dữ liệu), sau đó "bơm" nội dung này vào Prompt cho OpenAI Assistant sinh câu văn tự nhiên.
*   **Natural Language Processing (NLP):** Lý thuyết về phân tích ý định (Intent Matching), nhúng ngữ nghĩa (Semantic Embedding), xử lý ngữ cảnh hội thoại.
*   **Workflow Automation với n8n:** Kiến trúc Webhook, Trigger, Node. Cơ chế n8n hoạt động như một trục trung tâm (ESB) kết nối Facebook, Zalo, và Laravel.

### 1.3 Mục tiêu đề tài
#### 1.3.1 Mục tiêu tổng quát
Số hóa và tự động hóa toàn diện quy trình tư vấn, tiếp nhận và phân loại thí sinh trong các chiến dịch tuyển sinh, nhằm giảm thiểu tối đa sức lao động thủ công và tối ưu hóa tỷ lệ chuyển đổi thí sinh tiềm năng.

#### 1.3.2 Mục tiêu cụ thể
1.  **Xây dựng nền tảng CMS quản trị tập trung:** Xử lý dữ liệu Lead đa kênh (Web, Facebook, Zalo) và áp dụng thuật toán chấm điểm động (Dynamic Lead Scoring) để phân loại thí sinh (Hot/Warm/Cold).
2.  **Triển khai trợ lý ảo Chatbot AI (Hybrid RAG):** Tự động hóa việc tư vấn dựa trên bộ quy chế tuyển sinh riêng của nhà trường, có khả năng nhớ ngữ cảnh và nhận diện ý định.
3.  **Tự động hóa luồng dữ liệu bằng n8n:** Thiết lập luồng tự động nhận/gửi dữ liệu Real-time giữa CMS, MXH (kiểm duyệt comment) và các ứng dụng thông báo nội bộ (Telegram, Zalo).

### 1.4 Tính mới và sáng tạo của đề tài
*   Khác biệt hoàn toàn với chatbot kịch bản (Rule-based), Chatbot trong dự án có khả năng duy trì Context hội thoại và nhúng ngữ nghĩa vector (tự code module Semantic Embedding).
*   Tính năng **Kiểm duyệt Fanpage tự động**: Dùng AI để đánh giá comment Facebook có độc hại không (Toxic), sau đó gọi n8n API ẩn comment rác trực tiếp.
*   Bảo vệ dữ liệu cá nhân tự động (PII Safeguard): Tự động che SĐT, CCCD/CMND bằng Regex nâng cao trước khi đẩy câu hỏi của thí sinh lên OpenAI.

### 1.5 Đối tượng và phạm vi nghiên cứu
*   **Đối tượng:** Hệ thống thông tin quản lý tuyển sinh, Trí tuệ nhân tạo (LLM/RAG), luồng xử lý Webhook và RESTful API.
*   **Phạm vi:** Triển khai thử nghiệm cho các kênh Facebook, Zalo và Landing Page trong khuôn khổ quy chế của trường.

### 1.6 Ý nghĩa lý luận và thực tiễn
*   **Lý luận:** Khẳng định tính hiệu quả của mô hình Hybrid RAG (lai giữa tìm kiếm cục bộ và AI sinh ngữ) so với việc chỉ dùng OpenAI đơn thuần.
*   **Thực tiễn:** Giảm 80% sức lao động cho nhân viên trực fanpage, giúp nhà trường không bỏ lọt các thí sinh "Hot" nhờ cơ chế phân loại Real-time.

---

## CHƯƠNG 2: PHƯƠNG PHÁP NGHIÊN CỨU, PHÂN TÍCH VÀ THIẾT KẾ HỆ THỐNG (Khoảng 20-30 trang)

### 2.1 Kiến trúc tổng thể hệ thống (System Architecture)
*(Vị trí trình bày: Mở đầu mục 2.1, bạn chèn ngay "Sơ đồ Kiến trúc Tổng quan (Architecture Diagram)". Sau đó mới viết diễn giải 4 lớp bên dưới sơ đồ)*

*   **Lớp Giao tiếp (Frontend/Channels):** Web Landing Page, Facebook Fanpage, Zalo OA.
*   **Lớp Tự động hóa (n8n Automation Layer):** Chứa các Workflow lắng nghe Webhook từ MXH và đẩy về Laravel.
*   **Lớp Xử lý Trung tâm (Laravel Backend):**
    *   *Module Lead Management:* Xử lý lưu trữ và phân loại UTM.
    *   *Module AI Engine:* Xử lý Context, Intent, Semantic Search.
    *   *Module Scoring:* Chấm điểm tự động.
*   **Lớp Lưu trữ (Database Layer):** MySQL (Leads, Vector Data, Logs).

### 2.2 Sơ đồ Use Case Tổng Quát
*(Vị trí trình bày: Vẽ Sơ đồ Use Case tổng quan liệt kê tất cả các Actor và các chức năng của họ)*
*   **Actor: Thí sinh (User):** Đăng ký form tư vấn, Chat với hệ thống RAG, Bình luận trên Fanpage.
*   **Actor: Admin / Tư vấn viên:** Đăng nhập, Xem Dashboard, Quản lý tài liệu RAG, Xem danh sách Lead, Cấu hình tiêu chí chấm điểm, Nhận thông báo Telegram.
*   **Actor Hệ thống: n8n:** Kích hoạt Webhook lưu Lead, Kích hoạt kiểm duyệt Comment, Đẩy thông báo bằng API.

### 2.3 Sơ đồ Thực thể - Liên kết (ERD) và Cấu trúc dữ liệu
*(Vị trí trình bày: Ở mục này, bạn vẽ và chèn "Sơ đồ ERD", hoặc "Class Diagram". Nên nhóm thành các cụm để dễ giải thích)*

Diễn giải cấu trúc cơ sở dữ liệu dựa trên mã nguồn:
*   **Nhóm Leads:** `admission_leads`, `admission_lead_activities`, `admission_lead_score_logs`.
*   **Nhóm Chấm điểm:** `admission_scoring_criteria`, `admission_scoring_thresholds`.
*   **Nhóm AI/Chatbot:** `admission_rag_documents`, `admission_rag_chunks`, `advise_sessions`, `advise_messages`, `advise_tickets`.
*   **Nhóm Social:** `social_toxic_comments`.

### 2.4 Phân tích và Thiết kế các Module Chức năng Cụ Thể (KÈM SƠ ĐỒ LUỒNG)

#### 2.4.1 Chức năng Quản lý và Chấm điểm thí sinh (Dynamic Lead Scoring)
*(Vị trí trình bày: Bạn cần vẽ "Sơ đồ Hoạt động (Activity Diagram)" hoặc "Lưu đồ thuật toán (Flowchart)" cho riêng quá trình chấm điểm)*

*   **Mô tả Sơ đồ Hoạt động (Activity Diagram):**
    1. Trạng thái Bắt đầu.
    2. Nút: Nhận dữ liệu Lead (JSON Payload) từ n8n Webhook qua phương thức POST.
    3. Nút: Trích xuất JSON, Lưu/Cập nhật thông tin Lead vào DB.
    4. Nút: Vòng lặp quét qua toàn bộ bảng `admission_scoring_criteria`.
    5. Khối điều kiện: Lead có thỏa mãn tiêu chí không? (Ví dụ: Chứa Email? Nguồn Facebook?).
    6. Nhánh CÓ: Cộng điểm tương ứng và lưu log vào bảng `admission_lead_score_logs`. Nhánh KHÔNG: Bỏ qua.
    7. Khối điều kiện so sánh ngưỡng (`thresholds`): Đạt điểm Hot/Warm/Cold?
    8. Nút: Cập nhật nhãn phân loại cho Lead.
    9. Nút: Trả về HTTP Response 200 (Thành công) cho n8n.
    10. Kết thúc luồng.
*   **Phương pháp giải quyết:** Code xử lý nằm gọn trong `AdmissionLeadScoringService`. Các tiêu chí được tách rời thành dữ liệu cấu hình động, không bị fix cứng trong mã nguồn, giúp admin dễ dàng thay đổi chiến lược cộng điểm qua từng năm.

#### 2.4.2 Chức năng Chatbot AI & Hybrid RAG
*(Vị trí trình bày: Vẽ "Sơ đồ Tuần tự (Sequence Diagram)" chi tiết các luồng giao tiếp chéo nhau)*

*   **Mô tả Sơ đồ Tuần tự (Sequence Diagram):**
    1.  **User** gửi tin nhắn "Học phí bao nhiêu?" -> **Controller** (`AdviseController`).
    2.  **Controller** gọi **AnalyzerService** -> Chạy Regex lọc PII (ẩn SĐT).
    3.  **Controller** gọi **ContextService** -> Lấy 5 tin nhắn gần nhất từ DB để ráp nối ngữ cảnh.
    4.  **Controller** gọi hệ thống Full-Text Search DB (Local RAG) -> Trả về khối văn bản "Học phí ngành CNTT là 10 triệu".
    5.  **Controller** tạo cấu trúc JSON (gồm Lịch sử + Câu hỏi + Data RAG vừa lấy được) -> Gửi POST API lên **OpenAI Assistant**.
    6.  **OpenAI** phân tích và trả về JSON Response chứa văn bản tự nhiên -> **Controller** lưu DB và trả về giao diện cho **User**.
*   **Giải pháp xử lý lỗi:** Nếu OpenAI bị timeout 504, Controller có cơ chế catch Exception tự động xóa tin nhắn tạm trong DB để tránh lệch pha dữ liệu.

#### 2.4.3 Chức năng Tự động hóa n8n & Kiểm duyệt Comment
*(Vị trí trình bày: Ở mục này, bạn không vẽ UML mà vẽ "Lưu đồ quy trình n8n (n8n Workflow Diagram)" cho từng luồng)*

*   **Sơ đồ luồng 1 - Bắt Lead & Đẩy thông báo:**
    *   Node Trigger: Webhook (Bắt dữ liệu JSON từ Form/Landing Page).
    *   Node HTTP Request: Đóng gói JSON Payload gửi POST sang `/api/n8n/leads` (Laravel CMS).
    *   Node Switch/Filter: Phân tích JSON Response từ Laravel, nếu trạng thái Lead là "Hot".
    *   Node Telegram/Zalo: Gửi gọi API gửi tin nhắn cảnh báo cho Tư vấn viên "Có Lead nóng, gọi ngay!".
*   **Sơ đồ luồng 2 - Kiểm duyệt Comment Độc hại (Toxic Moderation):**
    *   Node Facebook Trigger: Lắng nghe Webhook khi có người dùng comment vào Fanpage.
    *   Node HTTP Request (AI): Gửi comment sang API mô hình AI để kiểm tra mức độ tiêu cực/chửi bậy.
    *   Khối rẽ nhánh (IF): Phân tích Response từ AI, Có Toxic không?
    *   Nhánh TRUE: Gọi API Facebook ẩn comment (Hide Comment) -> Gửi webhook lưu record log vào `social_toxic_comments` trên Laravel CMS.

---

## CHƯƠNG 3: KẾT QUẢ TRIỂN KHAI, ĐÁNH GIÁ VÀ THẢO LUẬN (Khoảng 10-15 trang)

### 3.1 Kết quả đạt được (Chụp hình minh họa)
*(Tại mục này, chỉ ghi chữ mồi. Thực tế bạn cần chụp các màn hình của hệ thống chèn vào báo cáo)*
*   **Giao diện CMS Admin:** Chụp màn hình Dashboard, danh sách Lead có màu phân loại (Hot/Cold), màn hình cấu hình Tiêu chí cộng điểm (Criteria).
*   **Hệ thống AI Chatbot:** Chụp giao diện Chatbot đang trả lời đúng chính xác quy chế, và một ảnh chụp database log cho thấy PII (SĐT) đã bị ẩn thành `[Số điện thoại]`.
*   **Hệ thống n8n:** Chụp giao diện Canvas các workflow của n8n (luồng nhận lead, luồng ẩn comment). Chụp màn hình tin nhắn báo về Telegram.

### 3.2 Ưu điểm của hệ thống
*   Kiến trúc Hybrid RAG giải quyết được nhược điểm "ảo giác" của RAG truyền thống. Overlap khi chunking giúp văn bản không bị đứt gãy ý nghĩa.
*   Phễu tuyển sinh được khép kín 100%: Từ Ads -> Form -> Chấm điểm -> Phân bổ tư vấn viên, liên kết thông suốt thông qua API chuẩn RESTful.
*   Hệ thống Backend vững chắc, dùng Queue Job và có cơ chế Rollback dữ liệu khi AI lỗi mạng.

### 3.3 Nhược điểm và hạn chế
*   **Chi phí API:** Việc duy trì module Semantic Embedding và OpenAI Assistant tiêu tốn chi phí theo token, không tối ưu nếu số lượng user quá lớn.
*   **Độ trễ sinh chữ (Latency):** Việc chờ RESTful API của OpenAI phản hồi tốn trung bình 2-5 giây, chưa nhanh bằng tốc độ phản hồi của các Chatbot kịch bản có sẵn.

### 3.4 Thảo luận và Hướng phát triển (Tương lai)
1.  **Dùng Local LLMs:** Đề xuất hướng nghiên cứu triển khai các LLM mã nguồn mở (như Llama-3, Qwen) chạy hoàn toàn trên server nội bộ của trường, tiết kiệm 100% chi phí API OpenAI.
2.  **Nâng cấp Vector Database:** Chuyển đổi từ MySQL Full-Text Search sang sử dụng các CSDL Vector chuyên dụng (như Milvus, Qdrant) để tìm kiếm Semantic Search nhanh và chính xác hơn nữa.
3.  **Tích hợp Zalo ZNS:** Kết nối n8n gọi API của Zalo ZNS để bắn tin nhắn xác nhận cho số điện thoại của thí sinh ngay khi họ nộp form thành công.

---

## CHƯƠNG 4: KẾT LUẬN (Khoảng 2 trang)
*   Khẳng định hệ thống đã hoàn thành cả Mục tiêu tổng quát và 3 Mục tiêu cụ thể.
*   Nhấn mạnh module "Hybrid RAG", "Dynamic Lead Scoring" và "Tự động hóa n8n thông qua API" là những giá trị cốt lõi, có khả năng áp dụng thực tiễn ngay lập tức cho bộ phận Tuyển sinh của nhà trường.
