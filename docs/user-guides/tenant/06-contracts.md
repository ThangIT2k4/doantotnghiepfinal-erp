# XEM HỢP ĐỒNG CỦA TÔI - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê xem danh sách và chi tiết các hợp đồng thuê (hợp đồng thuê) của mình.

## Quyền truy cập

- **Khách thuê**: Có quyền xem hợp đồng của chính mình

**Route**: `/tenant/contracts`

## Các bước thực hiện

### 1. Xem danh sách Hợp đồng

1. Truy cập **Hợp đồng** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả hợp đồng của Khách thuê
3. Có thể lọc theo:
   - Trạng thái (hoạt động, terminated, expired)
   - Bất động sản (nếu có nhiều hợp đồng)
   - Sắp xếp theo start_date, end_date, created_at, trạng thái

### 2. Xem chi tiết Hợp đồng

1. Click vào hợp đồng trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Hợp đồng Number: Số hợp đồng
     - Bất động sản: Bất động sản
     - Phòng: Phòng/Căn
     - Start Ngày, End Ngày: Ngày bắt đầu và kết thúc
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Rent Số tiền: Tiền thuê
     - Deposit Số tiền: Tiền cọc
     - Thanh toán Cycle: Chu kỳ thanh toán
     - Thanh toán Day: Ngày thanh toán
     - Billing Day: Ngày tạo hóa đơn
   - **Thông tin khác:**
     - Môi giới: Người quản lý hợp đồng
     - Services: Dịch vụ (điện, nước, internet, etc.)
     - Residents: Người ở cùng (nếu có)
     - Documents: Tài liệu hợp đồng (nếu có)
     - Termination Ngày: Ngày chấm dứt (nếu đã terminate)
     - Termination Reason: Lý do chấm dứt (nếu có)

### 3. Download Documents

1. Truy cập chi tiết hợp đồng
2. Scroll đến phần **Documents**
3. Click **Download** cho document cần tải
4. Hệ thống tải file về máy

**Lưu ý**: 
- Chỉ có thể download documents của hợp đồng
- Documents có thể là PDF, Word, hoặc hình ảnh

### 4. Xem Hóa đơn liên quan

1. Truy cập chi tiết hợp đồng
2. Click tab **Hóa đơn** hoặc scroll đến phần **Hóa đơn**
3. Hệ thống hiển thị danh sách hóa đơn của hợp đồng với:
   - Hóa đơn Number
   - Issue Ngày, Đến hạn Ngày
   - Số tiền
   - Đã thanh toán Số tiền
   - Remaining Số tiền
   - Trạng thái (draft, issued, đã thanh toán, overdue, cancelled)

### 5. Xem Thanh toán liên quan

1. Truy cập chi tiết hợp đồng
2. Click tab **Thanh toán** hoặc scroll đến phần **Thanh toán**
3. Hệ thống hiển thị danh sách thanh toán của hợp đồng với:
   - Thanh toán Ngày
   - Số tiền
   - Thanh toán Method
   - Hóa đơn Number
   - Trạng thái (success, failed, đang chờ)

### 6. Xem Tickets liên quan

1. Truy cập chi tiết hợp đồng
2. Click tab **Tickets** hoặc scroll đến phần **Tickets**
3. Hệ thống hiển thị danh sách tickets của hợp đồng với:
   - Ticket Number
   - Title
   - Trạng thái
   - Priority
   - Created Ngày

## Ràng buộc và điều kiện

### Business Rules

1. **Khách thuê chỉ thấy hợp đồng của chính mình**
   - Không thể thấy hợp đồng của Khách thuê khác
   - Dữ liệu được lọc theo Khách thuê ID

2. **Chỉ có thể xem hợp đồng**
   - Khách thuê không thể tạo, sửa, hoặc xóa hợp đồng
   - Chỉ có thể xem thông tin và download documents

3. **Documents Truy cập**
   - Chỉ có thể download documents của hợp đồng của mình
   - Documents được lưu trong storage

## Trạng thái và Workflow

### Trạng thái Flow

```
active → terminated/expired
```/expired
```

- **hoạt động**: Hợp đồng đang hoạt động
- **terminated**: Hợp đồng đã chấm dứt trước hạn
- **expired**: Hợp đồng đã hết hạn

### Workflow Xem Hợp đồng

1. Khách thuê truy cập Hợp đồng
2. Hệ thống hiển thị danh sách hợp đồng của Khách thuê
3. Khách thuê click vào hợp đồng để xem chi tiết
4. Hệ thống hiển thị thông tin chi tiết hợp đồng
5. Khách thuê có thể xem Hóa đơn, Thanh toán, Tickets liên quan

## Ví dụ

### Ví dụ 1: Xem danh sách Hợp đồng

**Kịch bản:** Khách thuê muốn xem tất cả hợp đồng của mình

**Các bước:**
1. Truy cập Hợp đồng
2. Hệ thống hiển thị danh sách:
   - Hợp đồng 1: Bất động sản ABC, Phòng 101, Start: 2025-01-01, End: 2025-12-31, Trạng thái: `active`
   - Hợp đồng 2: Bất động sản XYZ, Phòng 202, Start: 2024-01-01, End: 2024-12-31, Trạng thái: `expired`

### Ví dụ 2: Xem chi tiết Hợp đồng

**Kịch bản:** Khách thuê muốn xem chi tiết hợp đồng

**Các bước:**
1. Click vào hợp đồng Bất động sản ABC, Phòng 101
2. Hệ thống hiển thị:
   - Hợp đồng Number: `HD-202501-0001`
   - Bất động sản: `Property ABC`
   - Phòng: `Unit 101`
   - Start Ngày: `2025-01-01`
   - End Ngày: `2025-12-31`
   - Rent Số tiền: `10,000,000 VND`
   - Deposit Số tiền: `20,000,000 VND`
   - Thanh toán Cycle: `monthly`
   - Thanh toán Day: `5`
   - Billing Day: `1`
   - Services: `Điện, Nước, Internet`
   - Môi giới: `Agent A`
   - Trạng thái: `active`

### Ví dụ 3: Download Documents

**Kịch bản:** Khách thuê muốn tải hợp đồng về máy

**Các bước:**
1. Truy cập chi tiết hợp đồng
2. Scroll đến phần Documents
3. Click **Download** cho document "HopDong_2025.pdf"
4. Hệ thống tải file về máy

## Lưu ý

1. **Chỉ có thể xem hợp đồng**
   - Khách thuê không thể tạo, sửa, hoặc xóa hợp đồng
   - Liên hệ Môi giới hoặc Quản lý nếu cần thay đổi thông tin

2. **Documents**
   - Download documents để lưu trữ
   - Documents có thể là PDF, Word, hoặc hình ảnh

3. **Thông tin tài chính**
   - Xem thông tin Rent Số tiền, Deposit Số tiền
   - Xem Thanh toán Cycle và Thanh toán Day
   - Xem Hóa đơn và Thanh toán liên quan

4. **Thông tin liên quan**
   - Xem Hóa đơn, Thanh toán, Tickets liên quan đến hợp đồng
   - Theo dõi tình trạng thanh toán và bảo trì

## Troubleshooting

### Không thấy hợp đồng

1. Kiểm tra người dùng có hợp đồng hoạt động không
2. Kiểm tra filters có đang áp dụng không
3. Liên hệ hỗ trợ nếu vẫn không thấy

### Không thể download documents

1. Kiểm tra documents có tồn tại không
2. Kiểm tra kết nối mạng
3. Thử lại sau vài phút
4. Liên hệ hỗ trợ nếu vẫn lỗi

### Thông tin không chính xác

1. Refresh trang
2. Kiểm tra dữ liệu trong database
3. Liên hệ Môi giới hoặc Quản lý để cập nhật thông tin

---

**Lưu ý**: Xem hợp đồng giúp Khách thuê theo dõi thông tin hợp đồng thuê và các hoạt động liên quan.

**Cập nhật**: 2025-11-02

