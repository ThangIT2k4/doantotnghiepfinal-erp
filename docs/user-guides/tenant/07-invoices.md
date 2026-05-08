# XEM VÀ THANH TOÁN HÓA ĐƠN - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê xem, thanh toán, download, và xuất hóa đơn của mình.

## Quyền truy cập

- **Khách thuê**: Có quyền xem và thanh toán hóa đơn của chính mình

**Route**: `/tenant/invoices`

## Các bước thực hiện

### 1. Xem danh sách Hóa đơn

1. Truy cập **Hóa đơn** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả hóa đơn của Khách thuê
3. Có thể lọc theo:
   - Trạng thái (draft, issued, đã thanh toán, overdue, cancelled)
   - Hợp đồng thuê (nếu có nhiều hợp đồng)
   - Đến hạn Ngày (today, this week, this month, overdue, tùy chỉnh range)
   - Sắp xếp theo issue_date, due_date, số tiền, trạng thái

### 2. Xem chi tiết Hóa đơn

1. Click vào hóa đơn trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Hóa đơn Number: Số hóa đơn
     - Hợp đồng thuê: Hợp đồng liên quan
     - Bất động sản, Phòng: Bất động sản và phòng
     - Issue Ngày, Đến hạn Ngày: Ngày phát hành và hạn thanh toán
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Items: Danh sách các khoản phí (rent, service, meter, etc.)
     - Subtotal: Tổng tiền
     - Tax Số tiền: Thuế
     - Discount Số tiền: Giảm giá
     - Tổng Số tiền: Tổng cộng
     - Đã thanh toán Số tiền: Đã thanh toán
     - Remaining Số tiền: Còn nợ
   - **Thông tin khác:**
     - Note: Ghi chú
     - Thanh toán: Danh sách thanh toán (nếu có)

### 3. Thanh toán Hóa đơn

#### 3.1. Chọn Phương thức Thanh toán

1. Truy cập chi tiết hóa đơn cần thanh toán
2. Click **Pay** hoặc **Thanh toán**
3. Hệ thống hiển thị danh sách phương thức thanh toán:
   - **Cash**: Tiền mặt
   - **Bank Transfer**: Chuyển khoản ngân hàng
   - **SePay**: Thanh toán qua SePay (QR code, chuyển khoản)
4. Chọn phương thức thanh toán

#### 3.2. Thanh toán bằng Tiền mặt (Cash)

1. Chọn **Cash**
2. Nhập **Số tiền** (<= remaining số tiền)
3. Nhập **Đã thanh toán At** (ngày thanh toán)
4. (Tùy chọn) Nhập **Note** hoặc **Transaction Reference**
5. Click **Pay** hoặc **Thanh toán**
6. Hệ thống tạo thanh toán với trạng thái `success`
7. Hệ thống cập nhật hóa đơn trạng thái (đã thanh toán hoặc partially_paid)
8. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Thanh toán với Cash được đánh dấu thành công ngay lập tức
- Môi giới hoặc Quản lý sẽ verify thanh toán sau

#### 3.3. Thanh toán bằng Chuyển khoản Ngân hàng (Bank Transfer)

1. Chọn **Bank Transfer**
2. Nhập **Số tiền** (<= remaining số tiền)
3. Nhập **Đã thanh toán At** (ngày thanh toán)
4. Nhập **Transaction Reference** (số tham chiếu giao dịch)
5. (Tùy chọn) Upload **Proof of Thanh toán** (ảnh chụp biên lai)
6. Click **Pay** hoặc **Thanh toán**
7. Hệ thống tạo thanh toán với trạng thái `success` (cần Môi giới/Manager verify)
8. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Thanh toán với Bank Transfer cần Môi giới/Manager verify
- Cung cấp Transaction Reference và Proof of Thanh toán để verify nhanh

#### 3.4. Thanh toán qua SePay

1. Chọn **SePay**
2. Nhập **Số tiền** (<= remaining số tiền)
3. Hệ thống hiển thị thông tin thanh toán:
   - QR Code: Quét QR code để thanh toán
   - Bank Account: Thông tin tài khoản ngân hàng (nếu chọn bank transfer)
4. Chọn phương thức SePay:
   - **QR Code**: Quét QR code bằng app ngân hàng
   - **Bank Transfer**: Chuyển khoản theo thông tin được cung cấp
5. Click **Pay** hoặc **Thanh toán**
6. Hệ thống tạo thanh toán với trạng thái `pending`
7. Hệ thống gửi request tới SePay API
8. Chờ SePay webhook callback
9. Hệ thống tự động cập nhật thanh toán trạng thái khi nhận được webhook:
   - `success`: Thanh toán thành công
   - `failed`: Thanh toán thất bại
10. Hệ thống cập nhật hóa đơn trạng thái khi thanh toán thành công

**Lưu ý**: 
- Thanh toán với SePay có thể mất vài phút để xử lý
- Có thể kiểm tra thanh toán trạng thái trong Thanh toán

#### 3.5. Kiểm tra Trạng thái Thanh toán

1. Truy cập **Thanh toán** hoặc **Thanh toán Trạng thái**
2. Hệ thống hiển thị trạng thái thanh toán:
   - `pending`: Đang chờ xử lý
   - `success`: Thanh toán thành công
   - `failed`: Thanh toán thất bại
3. Click vào thanh toán để xem chi tiết

### 4. Download Hóa đơn

1. Truy cập chi tiết hóa đơn cần download
2. Click **Download** hoặc **Tải xuống**
3. Hệ thống tạo file PDF hoặc Word
4. Hệ thống tải file về máy

**Lưu ý**: 
- File có thể là PDF hoặc Word tùy cấu hình
- File chứa thông tin đầy đủ của hóa đơn

### 5. Xuất Hóa đơn

1. Truy cập danh sách hóa đơn
2. Click **Xuất** hoặc **Xuất Excel**
3. Chọn filters (nếu muốn xuất một phần):
   - Trạng thái
   - Hợp đồng thuê
   - Ngày Range
4. Click **Xuất**
5. Hệ thống tạo file Excel
6. Hệ thống tải file về máy

**Lưu ý**: 
- File Excel chứa danh sách hóa đơn theo filters
- File có thể được mở bằng Excel hoặc Google Sheets

## Ràng buộc và điều kiện

### Validation Rules

- **Hóa đơn**: 
  - Phải tồn tại và thuộc về Khách thuê hiện tại
  - Phải có trạng thái `issued`, `overdue`, hoặc `partially_paid`
  - Không được cancelled
- **Số tiền**: 
  - Phải > 0
  - Phải <= Hóa đơn Remaining Số tiền
  - Phải <= số tiền trong tài khoản (nếu SePay)
- **Thanh toán Method**: 
  - Phải là một trong: cash, bank_transfer, sepay, other
- **Transaction Reference**: 
  - Bắt buộc nếu Thanh toán Method = bank_transfer hoặc sepay
  - Phải là số hoặc chuỗi

### Business Rules

1. **Không thể thanh toán hóa đơn đã cancelled**
   - Hóa đơn phải có trạng thái `issued`, `overdue`, hoặc `partially_paid`
   - Không thể thanh toán hóa đơn đã cancelled

2. **Thanh toán Số tiền không được vượt quá Remaining Số tiền**
   - Số tiền phải <= Hóa đơn Remaining Số tiền
   - Có thể thanh toán nhiều lần (partial thanh toán)

3. **SePay Thanh toán**
   - Thanh toán với SePay có trạng thái `pending` cho đến khi nhận được webhook
   - Hệ thống tự động cập nhật khi nhận được webhook

4. **Partial Thanh toán**
   - Có thể thanh toán nhiều lần cho một hóa đơn
   - Hóa đơn trạng thái sẽ là `partially_paid` nếu chưa thanh toán đủ
   - Hóa đơn trạng thái sẽ là `paid` khi thanh toán đủ

## Trạng thái và Workflow

### Hóa đơn Trạng thái Flow

```
draft → issued → paid/overdue
          ↓          ↓
    partially_paid  cancelled
```/overdue
          ↓          ↓
    partially_paid  cancelled
```

- **draft**: Hóa đơn đang soạn thảo (không hiển thị cho Khách thuê)
- **issued**: Hóa đơn đã phát hành, chờ thanh toán
- **đã thanh toán**: Hóa đơn đã thanh toán đủ
- **overdue**: Hóa đơn quá hạn thanh toán
- **partially_paid**: Hóa đơn đã thanh toán một phần
- **cancelled**: Hóa đơn đã hủy

### Thanh toán Trạng thái Flow

```
pending → success/failed
```/failed
```

- **đang chờ**: Thanh toán đang chờ xử lý (SePay)
- **success**: Thanh toán thành công
- **failed**: Thanh toán thất bại

### Workflow Thanh toán

1. Khách thuê xem hóa đơn cần thanh toán
2. Khách thuê click **Pay**
3. Khách thuê chọn phương thức thanh toán (Cash, Bank Transfer, SePay)
4. Khách thuê nhập số tiền và thông tin thanh toán
5. Khách thuê click **Pay**
6. Hệ thống tạo thanh toán:
   - Cash/Bank Transfer: trạng thái `success` (cần verify)
   - SePay: trạng thái `pending`
7. Hệ thống cập nhật hóa đơn trạng thái
8. Hệ thống gửi thông báo cho Môi giới và Quản lý

## Ví dụ

### Ví dụ 1: Thanh toán bằng Tiền mặt

**Kịch bản:** Khách thuê muốn thanh toán hóa đơn bằng tiền mặt

**Hóa đơn:**
- Hóa đơn Number: `HD-202501-0001`
- Số tiền: `10,000,000 VND`
- Remaining Số tiền: `10,000,000 VND`

**Các bước:**
1. Truy cập chi tiết hóa đơn `HD-202501-0001`
2. Click **Pay**
3. Chọn **Cash**
4. Nhập Số tiền: `10,000,000 VND`
5. Nhập Đã thanh toán At: `2025-01-15 10:00`
6. Click **Pay**
7. Hệ thống tạo thanh toán với trạng thái `success`
8. Hóa đơn trạng thái chuyển sang `paid`

### Ví dụ 2: Thanh toán qua SePay

**Kịch bản:** Khách thuê muốn thanh toán hóa đơn qua SePay

**Hóa đơn:**
- Hóa đơn Number: `HD-202501-0002`
- Số tiền: `5,000,000 VND`
- Remaining Số tiền: `5,000,000 VND`

**Các bước:**
1. Truy cập chi tiết hóa đơn `HD-202501-0002`
2. Click **Pay**
3. Chọn **SePay**
4. Nhập Số tiền: `5,000,000 VND`
5. Hệ thống hiển thị QR Code
6. Khách thuê quét QR code bằng app ngân hàng
7. Khách thuê xác nhận thanh toán
8. Hệ thống tạo thanh toán với trạng thái `pending`
9. Chờ vài phút, SePay gửi webhook callback
10. Hệ thống tự động cập nhật thanh toán trạng thái = `success`
11. Hóa đơn trạng thái chuyển sang `paid`

### Ví dụ 3: Thanh toán một phần (Partial Thanh toán)

**Kịch bản:** Khách thuê muốn thanh toán một phần hóa đơn

**Hóa đơn:**
- Hóa đơn Number: `HD-202501-0003`
- Số tiền: `20,000,000 VND`
- Remaining Số tiền: `20,000,000 VND`

**Thanh toán 1:**
- Số tiền: `10,000,000 VND`

**Các bước:**
1. Truy cập chi tiết hóa đơn `HD-202501-0003`
2. Click **Pay**
3. Chọn **Bank Transfer**
4. Nhập Số tiền: `10,000,000 VND` (< remaining số tiền)
5. Nhập Transaction Reference: `REF123456`
6. Upload Proof of Thanh toán
7. Click **Pay**
8. Hệ thống tạo thanh toán với trạng thái `success`
9. Hóa đơn trạng thái chuyển sang `partially_paid`
10. Remaining Số tiền còn lại: `10,000,000 VND`
11. Có thể thanh toán tiếp phần còn lại

## Lưu ý

1. **Thanh toán đúng hạn**
   - Thanh toán trước Đến hạn Ngày để tránh Overdue
   - Kiểm tra Đến hạn Ngày trước khi thanh toán

2. **SePay Thanh toán**
   - Thanh toán có thể mất vài phút để xử lý
   - Kiểm tra thanh toán trạng thái trong Thanh toán
   - Liên hệ hỗ trợ nếu thanh toán quá lâu

3. **Proof of Thanh toán**
   - Upload proof of thanh toán khi thanh toán bằng bank transfer
   - Giúp Môi giới/Manager verify nhanh hơn

4. **Partial Thanh toán**
   - Có thể thanh toán nhiều lần cho một hóa đơn
   - Hóa đơn trạng thái sẽ là `partially_paid` nếu chưa thanh toán đủ

## Troubleshooting

### Không thể thanh toán hóa đơn

1. Kiểm tra hóa đơn trạng thái (phải là `issued`, `overdue`, hoặc `partially_paid`)
2. Kiểm tra hóa đơn có bị cancelled không
3. Kiểm tra remaining số tiền có > 0 không
4. Liên hệ hỗ trợ nếu vẫn không thể thanh toán

### SePay thanh toán đang chờ quá lâu

1. Kiểm tra thanh toán trạng thái trong Thanh toán
2. Kiểm tra kết nối SePay có ổn định không
3. Kiểm tra tài khoản ngân hàng có đủ tiền không
4. Liên hệ hỗ trợ nếu thanh toán quá lâu (quá 30 phút)

### Thanh toán failed

1. Kiểm tra tài khoản ngân hàng có đủ tiền không
2. Kiểm tra thông tin thanh toán có đúng không
3. Thử lại thanh toán
4. Liên hệ hỗ trợ nếu vẫn failed

### Không thể download hóa đơn

1. Kiểm tra hóa đơn có tồn tại không
2. Kiểm tra kết nối mạng
3. Thử lại sau vài phút
4. Liên hệ hỗ trợ nếu vẫn lỗi

---

**Lưu ý**: Thanh toán hóa đơn đúng hạn giúp Khách thuê tránh các khoản phí phạt và duy trì quan hệ tốt với chủ nhà.

**Cập nhật**: 2025-11-02

