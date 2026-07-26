# Kế Hoạch Triển Khai Kiến Trúc Hybrid RAG Cho Chatbot

Tài liệu này ghi chú lại ý tưởng nâng cấp kiến trúc xử lý của Chatbot tuyển sinh (chuyển từ Assistant thuần sang Hybrid RAG) để chuẩn bị cho lần lập trình tiếp theo.

## 1. Vấn Đề Hiện Tại
Hệ thống đang sử dụng công nghệ OpenAI Assistant API (`file_search`) để tự động đọc tài liệu (RAG) và trả lời sinh viên. 
Tuy nhiên, công nghệ này gặp một số hạn chế:
- **Ưu điểm:** Khả năng ghi nhớ tốt, đọc được lượng tài liệu lớn mà không bị tràn bộ nhớ (Context Limit).
- **Nhược điểm:** Tìm kiếm theo dạng "Hộp đen" (Black-box). Khi dữ liệu bị băm nhỏ (chunking), Assistant thường tìm kiếm bị thiếu sót (Ví dụ: Hỏi liệt kê 22 ngành thì nó chỉ lấy được 1 chunk chứa 21 ngành rồi trả lời luôn).

Trước đây, hệ thống có dùng **RAG Thuần (Local RAG)** cho luồng Facebook: tự cắt file, tự lưu DB và nhét trực tiếp vào prompt. Cách đó trả lời rất chính xác (không thiếu data) vì ta tự kiểm soát được nội dung mớm cho AI, nhưng lại hay bị lỗi không đọc được toàn bộ do giới hạn độ dài của API thông thường.

## 2. Giải Pháp: Kiến Trúc Hybrid RAG (Lai)
Giải pháp là **kết hợp sức mạnh của cả hai**: Sử dụng Local RAG để "bơm" dữ liệu siêu chính xác, và dùng OpenAI Assistant để xử lý ngôn ngữ và bọc lót phần tài liệu lớn.

### Luồng Hoạt Động Mới (Sẽ code):

1. **Người dùng đặt câu hỏi:** *"Hãy liệt kê tất cả các ngành đào tạo."*
2. **Xử lý Local RAG (Nội bộ):** 
   - Hệ thống Laravel tự động tìm kiếm câu hỏi này trong CSDL RAG cục bộ (Local Vector DB hoặc Full-text search do ta tự code).
   - Nhờ thuật toán tìm kiếm chủ động, hệ thống dễ dàng bốc ra được một khối văn bản chứa ĐẦY ĐỦ 22 ngành.
3. **Bơm ngữ cảnh (Inject Context):** 
   - Hệ thống nhét toàn bộ khối văn bản vừa lấy được từ bước 2 vào biến `$additionalInstructions` (Gợi ý thêm).
   - Nội dung mớm: *"[DỮ LIỆU CỤC BỘ]: Dưới đây là thông tin lấy từ hệ thống nhà trường, hãy ưu tiên dùng dữ liệu này để trả lời: {danh_sách_22_ngành}"*
4. **Gọi OpenAI Assistant API:**
   - Đẩy câu hỏi + `$additionalInstructions` lên cho Assistant.
   - Con AI đọc được dữ liệu mớm cực chuẩn, kết hợp với các file tài liệu nó tự load.
   - Kết quả xuất ra sẽ hoàn hảo: Không bao giờ thiếu ngành (vì đã mớm đủ), văn phong tự nhiên, và không sợ tràn bộ nhớ.

## 3. Các Bước Cần Thực Hiện Lần Tới
Khi bắt tay vào code, yêu cầu AI thực hiện các công việc sau:

- [ ] **Bước 1:** Khôi phục/Tích hợp lại module Local RAG (trước đây đã làm cho luồng FB) vào hệ thống hiện tại. Xây dựng logic đọc file txt cục bộ hoặc query Database để lấy ra chunk phù hợp.
- [ ] **Bước 2:** Chỉnh sửa file `app/Http/Controllers/AdviseController.php` (hàm `stream`).
- [ ] **Bước 3:** Chèn kết quả của hàm tìm kiếm Local RAG vào thẳng biến `$additionalInstructions` trước khi khởi tạo `Run` trên OpenAI Assistant.
- [ ] **Bước 4:** Viết lại Prompt một chút để dặn Assistant ưu tiên tuyệt đối nội dung trong phần DỮ LIỆU CỤC BỘ.

Kiến trúc này đảm bảo giải quyết 100% tình trạng "AI lâu lâu đếm thiếu ngành", đồng thời giúp nhà trường không cần phải thêm các từ khóa (neo) rườm rà vào file tài liệu gốc.
