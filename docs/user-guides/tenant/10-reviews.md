# TẠO VÀ QUẢN LÝ ĐÁNH GIÁ - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê tạo và quản lý đánh giá về phòng sau khi thuê.

## Quyền truy cập

- **Khách thuê**: Có quyền tạo và quản lý reviews của chính mình

**Route**: `/tenant/reviews`

## Các bước thực hiện

### 1. Xem danh sách Reviews

1. Truy cập **Reviews** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả reviews của Khách thuê
3. Có thể lọc theo:
   - Phòng (nếu có nhiều reviews)
   - Hợp đồng thuê (nếu có nhiều hợp đồng)
   - Rating (1-5 stars)
   - Sắp xếp theo created_at, rating

### 2. Xem chi tiết Review

1. Click vào review trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Review ID: Mã review
     - Phòng: Phòng/Căn được đánh giá
     - Hợp đồng thuê: Hợp đồng liên quan
     - Overall Rating: Đánh giá tổng thể (1-5 stars)
     - Location Rating: Đánh giá vị trí (1-5 stars)
     - Quality Rating: Đánh giá chất lượng (1-5 stars)
     - Service Rating: Đánh giá dịch vụ (1-5 stars)
     - Price Rating: Đánh giá giá cả (1-5 stars)
     - Title: Tiêu đề review
     - Content: Nội dung review
     - Highlights: Các điểm nổi bật (nếu có)
     - Recommend: Có khuyên bạn thuê không (có, maybe, không)
   - **Thông tin khác:**
     - Images: Hình ảnh (nếu có)
     - Created At: Ngày tạo review
     - Updated At: Ngày cập nhật review
   - **Replies:**
     - Danh sách replies từ Quản lý/Agent
     - Mỗi reply chứa: Content, Created At, Created By

### 3. Tạo Review mới

1. Click **Tạo Review** hoặc **+ New**
2. Chọn **Hợp đồng thuê** (chỉ hiển thị hợp đồng thuê có thể đánh giá):
   - Chỉ hiển thị hợp đồng thuê đã kết thúc hoặc đang hoạt động
   - Không hiển thị hợp đồng thuê đã có review
3. Hệ thống tự động điền:
   - Phòng: Từ hợp đồng thuê được chọn
   - Bất động sản: Từ hợp đồng thuê được chọn
4. Điền thông tin:
   - **Overall Rating** (bắt buộc, 1-5): Đánh giá tổng thể
   - **Location Rating** (bắt buộc, 1-5): Đánh giá vị trí
   - **Quality Rating** (bắt buộc, 1-5): Đánh giá chất lượng
   - **Service Rating** (bắt buộc, 1-5): Đánh giá dịch vụ
   - **Price Rating** (bắt buộc, 1-5): Đánh giá giá cả
   - **Title** (bắt buộc): Tiêu đề review
   - **Content** (bắt buộc): Nội dung review
   - **Highlights** (tùy chọn): Các điểm nổi bật (JSON array)
   - **Recommend** (bắt buộc): Có khuyên bạn thuê không (có, maybe, không)
   - **Images** (tùy chọn): Upload hình ảnh (nếu có)
5. Click **Lưu**
6. Hệ thống tạo review và hiển thị thông báo thành công
7. Hệ thống gửi thông báo cho Quản lý và Môi giới

**Lưu ý**: 
- Mỗi hợp đồng thuê chỉ có thể có 1 review
- Chỉ có thể tạo review cho hợp đồng thuê đã kết thúc hoặc đang hoạt động

### 4. Cập nhật Review

1. Truy cập chi tiết review cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin:
   - Ratings (Overall, Location, Quality, Service, Price)
   - Title
   - Content
   - Highlights
   - Recommend
   - Images (thêm hoặc xóa)
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật review của chính mình
- Có thể cập nhật bất cứ lúc nào

### 5. Xóa Review

1. Truy cập chi tiết review cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa review

**Lưu ý**: 
- Có thể xóa review của chính mình
- Review được soft xóa, có thể restore sau nếu cần

### 6. Trả lời Reply

1. Truy cập chi tiết review
2. Scroll đến phần **Replies**
3. Nếu có reply từ Quản lý/Agent, có thể:
   - Xem reply
   - (Không thể reply lại reply - chỉ Quản lý/Agent mới có thể reply)

**Lưu ý**: 
- Khách thuê không thể reply review
- Chỉ Quản lý/Agent mới có thể reply review

## Ràng buộc và điều kiện

### Validation Rules

- **Hợp đồng thuê**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về Khách thuê
  - Phải có thể đánh giá (đã kết thúc hoặc đang hoạt động)
  - Không được có review cho hợp đồng thuê này
- **Phòng**: 
  - Tự động từ hợp đồng thuê
  - Phải tồn tại
- **Overall Rating**: 
  - Bắt buộc
  - Phải từ 1 đến 5
- **Location Rating**: 
  - Bắt buộc
  - Phải từ 1 đến 5
- **Quality Rating**: 
  - Bắt buộc
  - Phải từ 1 đến 5
- **Service Rating**: 
  - Bắt buộc
  - Phải từ 1 đến 5
- **Price Rating**: 
  - Bắt buộc
  - Phải từ 1 đến 5
- **Title**: 
  - Bắt buộc
  - Không được để trống
  - Max 255 ký tự
- **Content**: 
  - Bắt buộc
  - Không được để trống
- **Recommend**: 
  - Bắt buộc
  - Phải là một trong: có, maybe, không
- **Highlights**: 
  - Tùy chọn
  - Phải là JSON array (nếu có)
- **Images**: 
  - Tùy chọn
  - Phải là file ảnh hợp lệ
  - Max size: 5MB mỗi file (tùy cấu hình)

### Business Rules

1. **Mỗi hợp đồng thuê chỉ có thể có 1 review**
   - Không thể tạo nhiều reviews cho cùng một hợp đồng thuê
   - Có thể cập nhật review nếu cần

2. **Chỉ có thể tạo review cho hợp đồng thuê của mình**
   - Hợp đồng thuê phải thuộc về Khách thuê
   - Không thể tạo review cho hợp đồng thuê của Khách thuê khác

3. **Reviewable Hợp đồng thuê**
   - Chỉ hiển thị hợp đồng thuê có thể đánh giá:
     - Hợp đồng thuê đã kết thúc
     - Hợp đồng thuê đang hoạt động
   - Không hiển thị hợp đồng thuê đã có review

## Trạng thái và Workflow

### Workflow Tạo Review

1. Khách thuê truy cập Reviews
2. Khách thuê click **Tạo Review**
3. Khách thuê chọn Hợp đồng thuê (chỉ hiển thị hợp đồng thuê có thể đánh giá)
4. Hệ thống tự động điền Phòng và Bất động sản từ hợp đồng thuê
5. Khách thuê điền ratings, title, content, recommend, images
6. Khách thuê click **Lưu**
7. Hệ thống tạo review
8. Hệ thống gửi thông báo cho Quản lý và Môi giới
9. Quản lý/Agent có thể reply review

## Ví dụ

### Ví dụ 1: Tạo Review mới

**Kịch bản:** Khách thuê muốn đánh giá phòng sau khi thuê

**Hợp đồng thuê:**
- Hợp đồng thuê: `HD-202501-0001`
- Phòng: `Unit 101`, Bất động sản `ABC`

**Các bước:**
1. Truy cập Reviews
2. Click **Tạo Review**
3. Chọn Hợp đồng thuê: `HD-202501-0001`
4. Hệ thống tự động điền Phòng: `Unit 101`, Bất động sản: `ABC`
5. Điền thông tin:
   - Overall Rating: `4`
   - Location Rating: `5`
   - Quality Rating: `4`
   - Service Rating: `4`
   - Price Rating: `3`
   - Title: `Phòng đẹp, vị trí tốt`
   - Content: `Phòng rất đẹp và sạch sẽ. Vị trí thuận tiện, gần trung tâm. Dịch vụ tốt, Agent hỗ trợ nhiệt tình. Giá cả hợp lý.`
   - Highlights: `["Vị trí đẹp", "Phòng sạch sẽ", "Dịch vụ tốt"]`
   - Recommend: `yes`
   - Images: Upload ảnh phòng
6. Click **Lưu**
7. Hệ thống tạo review và hiển thị thông báo thành công

### Ví dụ 2: Cập nhật Review

**Kịch bản:** Khách thuê muốn cập nhật review sau một thời gian

**Các bước:**
1. Truy cập chi tiết review
2. Click **Chỉnh sửa**
3. Cập nhật Content: Thêm thông tin "Sau 3 tháng thuê, phòng vẫn rất tốt"
4. Cập nhật Ratings nếu cần
5. Click **Lưu**
6. Hệ thống cập nhật review

## Lưu ý

1. **Đánh giá chính xác**
   - Đánh giá dựa trên trải nghiệm thực tế
   - Giúp người khác có thông tin chính xác

2. **Mỗi hợp đồng thuê chỉ có 1 review**
   - Tạo review cẩn thận vì chỉ có thể tạo 1 lần
   - Có thể cập nhật review nếu cần

3. **Reviewable Hợp đồng thuê**
   - Chỉ hiển thị hợp đồng thuê có thể đánh giá
   - Không hiển thị hợp đồng thuê đã có review

4. **Replies**
   - Quản lý/Agent có thể reply review
   - Khách thuê không thể reply review

## Troubleshooting

### Không thể tạo review

1. Kiểm tra hợp đồng thuê có thể đánh giá không
2. Kiểm tra hợp đồng thuê đã có review chưa
3. Kiểm tra tất cả các trường bắt buộc đã điền chưa
4. Kiểm tra ratings có từ 1 đến 5 không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thấy hợp đồng thuê trong danh sách

1. Kiểm tra hợp đồng thuê có thể đánh giá không
2. Kiểm tra hợp đồng thuê đã có review chưa
3. Kiểm tra hợp đồng thuê có thuộc về Khách thuê không
4. Liên hệ hỗ trợ nếu vẫn không thấy

### Không thể cập nhật review

1. Kiểm tra review có thuộc về Khách thuê không
2. Kiểm tra tất cả các trường bắt buộc đã điền chưa
3. Kiểm tra ratings có từ 1 đến 5 không
4. Liên hệ hỗ trợ nếu vẫn không thể cập nhật

---

**Lưu ý**: Tạo review giúp người khác có thông tin chính xác về phòng và giúp cải thiện dịch vụ.

**Cập nhật**: 2025-11-02

