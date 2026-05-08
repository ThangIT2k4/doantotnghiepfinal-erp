# QUẢN LÝ ĐÁNH GIÁ - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý đánh giá (reviews) trong tổ chức, bao gồm xem, reply, thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ xem và reply tất cả reviews trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `crm.access`
  - Xem reviews: Cần capability `crm.review.view` (mặc định có thể xem)
  - Reply reviews: Cần capability `crm.review.reply`

**Route**: `/staff/reviews`

## Các bước thực hiện

### 1. Xem danh sách Reviews

1. Truy cập **Reviews** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả reviews trong tổ chức
3. Có thể lọc theo:
   - Phòng (nếu có nhiều phòng)
   - Khách thuê (nếu có nhiều khách thuê)
   - Rating (1-5 stars)
   - Hợp đồng thuê (nếu có nhiều hợp đồng thuê)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, rating

### 2. Xem chi tiết Review

1. Click vào review trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Review ID: Mã review
     - Phòng: Phòng/Căn được đánh giá
     - Hợp đồng thuê: Hợp đồng liên quan
     - Khách thuê: Người thuê đánh giá
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

### 3. Reply Review (Trả lời Đánh giá)

1. Truy cập chi tiết review cần reply
2. Scroll đến phần **Replies**
3. Click **Reply** hoặc **Trả lời**
4. Điền thông tin:
   - **Content** (bắt buộc): Nội dung reply
   - **Note** (tùy chọn): Ghi chú nội bộ (không hiển thị cho khách thuê)
5. Click **Lưu**
6. Reply được thêm vào review
7. Hệ thống gửi thông báo cho Khách thuê
8. Khách thuê có thể xem reply trong review của mình

**Lưu ý**: 
- Quản lý có thể reply bất cứ review nào
- Reply được hiển thị công khai cho khách thuê
- Note (nếu có) chỉ hiển thị nội bộ

### 4. Cập nhật Reply

1. Truy cập chi tiết review
2. Scroll đến phần **Replies**
3. Click **Chỉnh sửa** trên reply cần cập nhật (chỉ reply của Quản lý hiện tại)
4. Cập nhật Content
5. Click **Lưu**
6. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật reply của chính mình
- Không thể cập nhật reply của Quản lý/Agent khác

### 5. Xóa Reply

1. Truy cập chi tiết review
2. Scroll đến phần **Replies**
3. Click **Xóa** trên reply cần xóa (chỉ reply của Quản lý hiện tại)
4. Xác nhận xóa
5. Hệ thống xóa reply (soft xóa)

**Lưu ý**: 
- Chỉ có thể xóa reply của chính mình
- Không thể xóa reply của Quản lý/Agent khác

### 6. Xem Thống kê

1. Truy cập **Reviews** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Tổng Reviews: Tổng số đánh giá
   - Reviews by Rating: Phân bố theo đánh giá (1-5 stars)
   - Average Rating: Đánh giá trung bình
   - Reviews by Period: Phân bố theo thời gian
   - Reviews by Phòng: Phân bố theo phòng
   - Reviews by Bất động sản: Phân bố theo bất động sản
   - Recommend Rate: Tỷ lệ khuyên thuê (có/maybe/no)

## Ràng buộc và điều kiện

### Validation Rules

- **Review**: 
  - Phải tồn tại và thuộc về tổ chức
- **Content**: 
  - Bắt buộc
  - Không được để trống
- **Note**: 
  - Tùy chọn
  - Chỉ hiển thị nội bộ (không hiển thị cho khách thuê)

### Business Rules

1. **Quản lý chỉ có thể reply review**
   - Không thể tạo, sửa, hoặc xóa review
   - Chỉ có thể reply review

2. **Reply được hiển thị công khai**
   - Reply được hiển thị cho khách thuê
   - Note (nếu có) chỉ hiển thị nội bộ

3. **Chỉ có thể cập nhật/xóa reply của chính mình**
   - Không thể cập nhật/xóa reply của Quản lý/Agent khác

4. **Review không thể xóa**
   - Review được tạo bởi Khách thuê
   - Quản lý không thể xóa review
   - Review chỉ có thể được soft xóa bởi Khách thuê

## Trạng thái và Workflow

### Review Workflow

```
Tenant creates Review → Manager/Agent views Review → Manager/Agent replies Review → Tenant views Reply
```/Agent views Review → Quản lý/Agent replies Review → Khách thuê views Reply
```

### Workflow Reply Review

1. Khách thuê tạo review
2. Quản lý xem review
3. Quản lý reply review
4. Hệ thống gửi thông báo cho Khách thuê
5. Khách thuê có thể xem reply trong review của mình

## Ví dụ

### Ví dụ 1: Reply Review

**Kịch bản:** Quản lý muốn reply review của Khách thuê

**Review:**
- Phòng: Phòng 101
- Overall Rating: 4
- Title: "Phòng đẹp, vị trí tốt"
- Content: "Phòng rất đẹp và sạch sẽ. Vị trí thuận tiện, gần trung tâm. Dịch vụ tốt."
- Recommend: `yes`

**Các bước:**
1. Truy cập chi tiết Review
2. Scroll đến phần Replies
3. Click **Reply**
4. Nhập Content: "Cảm ơn bạn đã đánh giá. Chúng tôi sẽ tiếp tục cải thiện dịch vụ để phục vụ bạn tốt hơn."
5. Click **Lưu**
6. Reply được thêm vào review
7. Hệ thống gửi thông báo cho Khách thuê

### Ví dụ 2: Cập nhật Reply

**Kịch bản:** Quản lý muốn cập nhật reply

**Các bước:**
1. Truy cập chi tiết Review
2. Scroll đến phần Replies
3. Click **Chỉnh sửa** trên reply của Quản lý
4. Cập nhật Content: "Cảm ơn bạn đã đánh giá. Chúng tôi rất vui được phục vụ bạn. Chúng tôi sẽ tiếp tục cải thiện dịch vụ."
5. Click **Lưu**
6. Reply được cập nhật

## Lưu ý

1. **Reply Reviews**
   - Reply reviews để phản hồi feedback của khách thuê
   - Giúp cải thiện dịch vụ

2. **Note (Nội bộ)**
   - Sử dụng Note để ghi chú nội bộ (không hiển thị cho khách thuê)
   - Giúp theo dõi và xử lý review

3. **Thống kê**
   - Xem thống kê để đánh giá chất lượng dịch vụ
   - Average Rating và Recommend Rate quan trọng để đo lường satisfaction

4. **Review không thể xóa**
   - Review được tạo bởi Khách thuê
   - Quản lý không thể xóa review
   - Review chỉ có thể được soft xóa bởi Khách thuê

## Troubleshooting

### Không thể reply review

1. Kiểm tra review có tồn tại không
2. Kiểm tra Content đã điền chưa
3. Liên hệ hỗ trợ nếu vẫn không thể reply

### Reply không hiển thị

1. Refresh trang
2. Kiểm tra reply có được lưu không
3. Kiểm tra khách thuê có xem được reply không
4. Liên hệ hỗ trợ nếu vẫn không hiển thị

---

**Xem thêm:**
- [Khách thuê Reviews](../tenant/10-reviews.md)
- [Quản lý Phòng](./04-units.md)

**Cập nhật: 2025-01-XX

