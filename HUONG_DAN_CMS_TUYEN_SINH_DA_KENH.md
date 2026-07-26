# Huong dan CMS tuyen sinh da kenh: Facebook, Zalo, n8n va RAG

Tai lieu nay mo ta nhung phan da trien khai trong du an va cach van hanh luong dang bai da nen tang.

## 1. Muc tieu he thong

- CMS quan tri tap trung de tiep nhan, phan loai va cham diem thi sinh tiem nang.
- Landing page nhan lead tu bai dang Facebook, Zalo, TikTok, website bang UTM.
- Tro ly ao RAG tra loi dua tren kho quy che tuyen sinh rieng.
- n8n dong bo du lieu giua mang xa hoi, CMS va kenh thong bao noi bo.

## 2. Cac phan da trien khai

### Backend

- `app/Http/Controllers/AdmissionAdminController.php`
  - Quan ly man hinh admin CMS.
  - API noi bo cho danh sach lead, thong ke, tai lieu RAG, hoi thu RAG va log n8n.
- `app/Http/Controllers/AdmissionLeadController.php`
  - Trang public `/dang-ky-tu-van`.
  - Luu lead tu form landing page.
  - Doc UTM de xac dinh nguon Facebook, Zalo, TikTok, website.
- `app/Http/Controllers/AdmissionWebhookController.php`
  - API cho n8n:
    - `POST /api/n8n/leads`
    - `POST /api/n8n/rag/answer`
    - `POST /api/n8n/notifications`
- `app/Services/AdmissionLeadScoringService.php`
  - Cham diem lead tu thong tin lien he, nganh quan tam, kenh nguon va hanh vi.
- `app/Services/AdmissionRagService.php`
  - Tach chunk tai lieu quy che.
  - Tra cuu noi dung lien quan.
  - Neu co OpenAI key thi tao cau tra loi sinh tu ngu canh; neu khong co key thi tra loi dang trich xuat.

### Frontend

- `resources/views/admins/pages/admission_cms.blade.php`
  - Dashboard CMS tuyen sinh.
  - Tab lead scoring.
  - Tab kho tri thuc RAG.
  - Tab log n8n.
- `resources/views/users/pages/dang_ky_tu_van.blade.php`
  - Landing page de gan link vao bai dang Facebook/Zalo.
  - Form dang ky tu van.
  - Khung hoi nhanh tro ly RAG.
- `public/js/admins/admission_cms.js`
  - Xu ly AJAX cho admin CMS.
- `public/js/admission-lead-form.js`
  - Submit form landing page.
  - Goi endpoint RAG tu landing page.

### Database

Migration: `database/migrations/2026_07_12_000001_create_admission_cms_tables.php`

Cac bang duoc tao:

- `admission_leads`: thong tin thi sinh tiem nang, nguon kenh, diem, trang thai.
- `admission_lead_activities`: lich su tuong tac cua lead.
- `admission_rag_documents`: tai lieu quy che/tuyen sinh.
- `admission_rag_chunks`: cac doan noi dung phuc vu truy xuat RAG.
- `admission_n8n_logs`: log su kien tu n8n.

## 3. Viec can chay sau khi bat database

Bat MySQL trong Laragon, sau do chay:

```bash
php artisan migrate --force
```

Neu chua chay migration, cac form/API se loi vi chua co bang `admission_*`.

## 4. Cach tao link dang bai Facebook va Zalo

Landing page nhan lead:

```text
https://domain-cua-ban.vn/dang-ky-tu-van
```

Gan UTM de CMS biet lead den tu dau.

### Link Facebook

```text
https://domain-cua-ban.vn/dang-ky-tu-van?utm_source=facebook&utm_medium=social&utm_campaign=tuyen_sinh_2026&utm_content=bai_dang_nganh_cntt&major=Cong%20nghe%20thong%20tin
```

### Link Zalo OA

```text
https://domain-cua-ban.vn/dang-ky-tu-van?utm_source=zalo&utm_medium=oa&utm_campaign=tuyen_sinh_2026&utm_content=broadcast_thang_7&major=Quan%20tri%20kinh%20doanh
```

### Link TikTok

```text
https://domain-cua-ban.vn/dang-ky-tu-van?utm_source=tiktok&utm_medium=social&utm_campaign=tuyen_sinh_2026&utm_content=video_review_truong
```

Quy uoc UTM:

- `utm_source`: kenh nguon, vi du `facebook`, `zalo`, `tiktok`, `website`.
- `utm_medium`: loai kenh, vi du `social`, `oa`, `ads`, `organic`.
- `utm_campaign`: chien dich, vi du `tuyen_sinh_2026`.
- `utm_content`: noi dung bai dang de tach hieu qua tung bai.
- `major`: nganh quan tam neu bai dang tap trung vao mot nganh.

## 5. Mau bai dang Facebook

```text
Tuyen sinh 2026 - Dang ky nhan tu van nganh Cong nghe thong tin

Ban dang quan tam den nganh Cong nghe thong tin va muon biet phuong thuc xet tuyen, to hop mon, hoc phi, co hoi viec lam?

De lai thong tin tai day de duoc tu van:
https://domain-cua-ban.vn/dang-ky-tu-van?utm_source=facebook&utm_medium=social&utm_campaign=tuyen_sinh_2026&utm_content=bai_dang_nganh_cntt&major=Cong%20nghe%20thong%20tin

Sau khi dang ky, he thong se ghi nhan yeu cau va bo phan tu van se lien he lai.
```

## 6. Mau bai dang Zalo OA

```text
Dang ky tu van tuyen sinh 2026

Nha truong ho tro giai dap thong tin ve phuong thuc xet tuyen, nganh dao tao va quy che tuyen sinh.

Bam link de de lai thong tin:
https://domain-cua-ban.vn/dang-ky-tu-van?utm_source=zalo&utm_medium=oa&utm_campaign=tuyen_sinh_2026&utm_content=broadcast_tu_van_chung
```

## 7. Cach cau hinh n8n

### Luong 1: Facebook/Zalo form hoac webhook sang CMS

Node goi HTTP Request:

- Method: `POST`
- URL: `https://domain-cua-ban.vn/api/n8n/leads`
- Body type: JSON

Payload mau:

```json
{
  "full_name": "Nguyen Van A",
  "phone": "0901234567",
  "email": "a@example.com",
  "channel": "facebook",
  "source_campaign": "tuyen_sinh_2026",
  "intended_major": "Cong nghe thong tin",
  "province": "Can Tho",
  "activity_type": "form_submit",
  "activity_content": "Dang ky tu van tu bai dang fanpage",
  "payload": {
    "post_id": "facebook_post_id",
    "utm_content": "bai_dang_nganh_cntt"
  }
}
```

Ket qua tra ve:

```json
{
  "success": true,
  "lead_id": 1,
  "score": 80,
  "score_grade": "hot"
}
```

### Luong 2: Hoi RAG tu n8n

- Method: `POST`
- URL: `https://domain-cua-ban.vn/api/n8n/rag/answer`

Payload:

```json
{
  "question": "Dieu kien xet tuyen hoc ba nhu the nao?",
  "channel": "zalo"
}
```

### Luong 3: Thong bao noi bo

Khi co lead hot, n8n co the gui thong bao den Telegram, email, Google Sheet hoac Zalo OA noi bo.

Dieu kien goi y:

- Neu `score_grade = hot`: thong bao ngay cho tu van vien.
- Neu `score_grade = warm`: dua vao danh sach cham soc trong ngay.
- Neu `score_grade = cold`: dua vao chien dich remarketing.

## 8. Cach nhap du lieu RAG

Vao admin:

```text
/admin/admission-cms
```

Mo tab `RAG quy che`, them tai lieu:

- Tieu de: vi du `Quy che tuyen sinh 2026`
- Loai: `quy_che`, `hoc_phi`, `nganh_hoc`, `hoc_bong`
- Trang thai: `active`
- Noi dung: dan quy che, thong tin xet tuyen, cau hoi thuong gap

Sau khi luu, he thong tu tao chunks trong bang `admission_rag_chunks`.

## 9. Cach xem va xu ly lead

Vao admin:

```text
/admin/admission-cms
```

Tab `Leads` hien:

- Ho ten, so dien thoai, email.
- Kenh nguon: `facebook`, `zalo`, `tiktok`, `website`.
- Nganh quan tam.
- Diem lead scoring.
- Nhom diem: `hot`, `warm`, `cold`.
- Trang thai xu ly: `new`, `contacted`, `qualified`, `enrolled`, `lost`.

## 10. Ghi chu van hanh

- Moi bai dang nen co `utm_content` rieng de do hieu qua tung bai.
- Moi chien dich nen co `utm_campaign` thong nhat.
- Neu chay Facebook Ads hoac Zalo Ads, van dung cung landing page va them UTM.
- Nen nhap quy che vao RAG truoc khi mo chatbot cho thi sinh hoi.
- Can bat MySQL va chay migration truoc khi test form/API.
