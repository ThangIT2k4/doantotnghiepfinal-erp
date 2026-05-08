# XEM LỊCH SỬ THANH TOÁN - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê xem lịch sử thanh toán của mình, bao gồm tất cả các thanh toán đã thực hiện.

## Quyền truy cập

- **Khách thuê**: Có quyền xem lịch sử thanh toán của chính mình

**Route**: `/tenant/payments`

## Các bước thực hiện

### 1. Xem danh sách Thanh toán

1. Truy cập **Thanh toán** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả thanh toán của Khách thuê
3. Có thể lọc theo:
   - Trạng thái (đang chờ, success, failed)
   - Thanh toán Method (cash, bank_transfer, sepay, other)
   - Hóa đơn (nếu muốn xem thanh toán của hóa đơn cụ thể)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo paid_at, số tiền, trạng thái

### 2. Xem chi tiết Thanh toán

1. Click vào thanh toán trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Thanh toán ID: Mã thanh toán
     - Hóa đơn: Hóa đơn liên quan
     - Hóa đơn Number: Số hóa đơn
     - Số tiền: Số tiền thanh toán
     - Đã thanh toán At: Ngày giờ thanh toán
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin thanh toán:**
     - Thanh toán Method: Phương thức thanh toán
     - Transaction Reference: Số tham chiếu giao dịch (nếu có)
     - Bank Account: Thông tin tài khoản ngân hàng (nếu SePay)
     - QR Code: Mã QR thanh toán (nếu SePay và đang chờ)
   - **Thông tin khác:**
     - Note: Ghi chú
     - Created At: Ngày tạo thanh toán
     - Updated At: Ngày cập nhật thanh toán

### 3. Kiểm tra Trạng thái Thanh toán (SePay)

1. Truy cập chi tiết thanh toán có trạng thái `pending` (SePay)
2. Hệ thống hiển thị:
   - **Trạng thái**: đang chờ
   - **QR Code**: Mã QR để quét thanh toán (nếu chưa thanh toán)
   - **Bank Account**: Thông tin tài khoản ngân hàng
   - **Thanh toán Instructions**: Hướng dẫn thanh toán
3. Click **Check Trạng thái** hoặc refresh trang để cập nhật trạng thái
4. Hệ thống kiểm tra trạng thái từ SePay API
5. Trạng thái sẽ được cập nhật:
   - `success`: Thanh toán thành công
   - `failed`: Thanh toán thất bại

**Lưu ý**: 
- Thanh toán với SePay có thể mất vài phút để xử lý
- Hệ thống tự động cập nhật khi nhận được webhook từ SePay
- Có thể kiểm tra thủ công bằng cách refresh trang

### 4. Xem Thanh toán theo Hóa đơn

1. Truy cập **Hóa đơn** → Chọn hóa đơn cụ thể
2. Click tab **Thanh toán** hoặc scroll đến phần **Thanh toán**
3. Hệ thống hiển thị danh sách thanh toán của hóa đơn đó
4. Có thể xem chi tiết từng thanh toán

### 5. Download Receipt

1. Truy cập chi tiết thanh toán có trạng thái `success`
2. Click **Download Receipt** hoặc **Tải biên lai**
3. Hệ thống tạo file PDF receipt
4. Hệ thống tải file về máy

**Lưu ý**: 
- Chỉ có thể download receipt cho thanh toán đã thành công
- Receipt chứa thông tin đầy đủ của thanh toán

## Ràng buộc và điều kiện

### Business Rules

1. **Khách thuê chỉ thấy thanh toán của chính mình**
   - Không thể thấy thanh toán của Khách thuê khác
   - Dữ liệu được lọc theo Khách thuê ID

2. **Thanh toán Trạng thái**
   - `pending`: Thanh toán đang chờ xử lý (SePay)
   - `success`: Thanh toán thành công
   - `failed`: Thanh toán thất bại

3. **SePay Thanh toán Trạng thái Cập nhật**
   - Trạng thái được cập nhật tự động khi nhận được webhook từ SePay
   - Có thể kiểm tra thủ công bằng cách refresh trang

## Trạng thái và Workflow

### Thanh toán Trạng thái Flow

```
pending → success/failed
```/failed
```

- **đang chờ**: Thanh toán đang chờ xử lý (chỉ SePay)
- **success**: Thanh toán thành công
- **failed**: Thanh toán thất bại

### Workflow Kiểm tra Trạng thái

1. Khách thuê thanh toán qua SePay
2. Hệ thống tạo thanh toán với trạng thái `pending`
3. Khách thuê quét QR code hoặc chuyển khoản
4. SePay xử lý thanh toán
5. SePay gửi webhook callback cho hệ thống
6. Hệ thống tự động cập nhật thanh toán trạng thái:
   - `success`: Nếu thanh toán thành công
   - `failed`: Nếu thanh toán thất bại
7. Hệ thống cập nhật hóa đơn trạng thái
8. Hệ thống gửi thông báo cho Khách thuê

## Ví dụ

### Ví dụ 1: Xem danh sách Thanh toán

**Kịch bản:** Khách thuê muốn xem tất cả thanh toán của mình

**Các bước:**
1. Truy cập Thanh toán
2. Hệ thống hiển thị danh sách:
   - Thanh toán 1: Hóa đơn HD-202501-0001, Số tiền: 10,000,000 VND, Method: Cash, Trạng thái: `success`, Đã thanh toán At: 2025-01-15 10:00
   - Thanh toán 2: Hóa đơn HD-202501-0002, Số tiền: 5,000,000 VND, Method: SePay, Trạng thái: `success`, Đã thanh toán At: 2025-01-16 14:00
   - Thanh toán 3: Hóa đơn HD-202501-0003, Số tiền: 8,000,000 VND, Method: Bank Transfer, Trạng thái: `success`, Đã thanh toán At: 2025-01-17 09:00

### Ví dụ 2: Kiểm tra Trạng thái SePay Thanh toán

**Kịch bản:** Khách thuê đã thanh toán qua SePay và muốn kiểm tra trạng thái

**Các bước:**
1. Truy cập Thanh toán
2. Tìm thanh toán có trạng thái `pending` và method `SePay`
3. Click vào thanh toán để xem chi tiết
4. Hệ thống hiển thị:
   - Trạng thái: `pending`
   - QR Code: (đã thanh toán)
   - Thanh toán Instructions
5. Click **Check Trạng thái** hoặc refresh trang
6. Hệ thống kiểm tra trạng thái từ SePay
7. Nếu thanh toán thành công, trạng thái chuyển sang `success`

### Ví dụ 3: Download Receipt

**Kịch bản:** Khách thuê muốn tải biên lai thanh toán

**Các bước:**
1. Truy cập chi tiết thanh toán có trạng thái `success`
2. Click **Download Receipt**
3. Hệ thống tạo file PDF receipt
4. Hệ thống tải file về máy

## Lưu ý

1. **Thanh toán Trạng thái**
   - Thanh toán với Cash/Bank Transfer có trạng thái `success` ngay lập tức
   - Thanh toán với SePay có trạng thái `pending` cho đến khi nhận được webhook

2. **SePay Thanh toán**
   - Có thể mất vài phút để xử lý
   - Hệ thống tự động cập nhật khi nhận được webhook
   - Có thể kiểm tra thủ công bằng cách refresh trang

3. **Download Receipt**
   - Chỉ có thể download receipt cho thanh toán đã thành công
   - Receipt chứa thông tin đầy đủ của thanh toán

4. **Thanh toán History**
   - Lưu trữ lịch sử thanh toán để theo dõi
   - Có thể lọc và sắp xếp để tìm kiếm nhanh

## Troubleshooting

### Thanh toán đang chờ quá lâu

1. Kiểm tra kết nối SePay có ổn định không
2. Kiểm tra tài khoản ngân hàng có đủ tiền không
3. Kiểm tra thanh toán đã thành công trong app ngân hàng chưa
4. Refresh trang để cập nhật trạng thái
5. Liên hệ hỗ trợ nếu thanh toán quá lâu (quá 30 phút)

### Thanh toán failed

1. Kiểm tra tài khoản ngân hàng có đủ tiền không
2. Kiểm tra thông tin thanh toán có đúng không
3. Thử lại thanh toán
4. Liên hệ hỗ trợ nếu vẫn failed

### Không thấy thanh toán

1. Kiểm tra filters có đang áp dụng không
2. Kiểm tra thanh toán có thuộc về Khách thuê hiện tại không
3. Refresh trang
4. Liên hệ hỗ trợ nếu vẫn không thấy

### Không thể download receipt

1. Kiểm tra thanh toán có trạng thái `success` không
2. Kiểm tra kết nối mạng
3. Thử lại sau vài phút
4. Liên hệ hỗ trợ nếu vẫn lỗi

---

**Lưu ý**: Lịch sử thanh toán giúp Khách thuê theo dõi các khoản đã thanh toán và tải biên lai khi cần.

**Cập nhật**: 2025-11-02

