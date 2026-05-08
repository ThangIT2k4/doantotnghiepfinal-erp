# TỪ VỰNG VÀ ĐỊNH NGHĨA

## Routes và URLs

### Nhân viên Routes (Unified)
- **`/staff/*`**: Routes thống nhất cho cả Quản lý và Môi giới
  - Quản lý có full truy cập
  - Môi giới bị giới hạn bởi capabilities
  - Ví dụ: `/staff/dashboard`, `/staff/properties`, `/staff/leases`

### Khách thuê Routes
- **`/tenant/*`**: Routes dành cho Khách thuê
  - Ví dụ: `/tenant/dashboard`, `/tenant/invoices`

### SuperAdmin Routes
- **`/superadmin/*`**: Routes dành cho SuperAdmin
  - Ví dụ: `/superadmin/dashboard`, `/superadmin/organizations`

**Xem chi tiết**: [Routes Mapping](./00-routes-mapping.md)

---

## Tổng quan

Tài liệu này định nghĩa các thuật ngữ và khái niệm được sử dụng trong hệ thống Quản lý Bất động sản Cho thuê.

## Thuật ngữ chính

### A

**Môi giới** - Môi giới/Nhân viên
- Vai trò trong hệ thống, có thể quản lý hợp đồng thuê được giao, leads, viewings, và tạo hóa đơn/payments

**Amenity** - Tiện ích
- Các tiện ích của phòng/căn (ví dụ: wifi, điều hòa, máy nước nóng)

**Appointment** - Lịch hẹn
- Lịch xem phòng đã được đặt bởi khách thuê hoặc lead

### B

**Booking Deposit** - Đặt cọc
- Khoản tiền cọc được đặt để giữ phòng/căn trước khi ký hợp đồng

**Billing Cycle** - Chu kỳ thanh toán
- Chu kỳ tạo hóa đơn (hàng tháng, hàng quý, hàng năm, tùy chỉnh)

### C

**Capability** - Quyền hạn
- Quyền chi tiết được gán cho người dùng, kiểm soát khả năng truy cập các chức năng cụ thể

**Cash Outflow** - Dòng tiền chi ra
- Giao dịch chi tiền từ công ty (thanh toán nhà cung cấp, hoàn tiền cọc, phát lương, etc.)

**Commission** - Hoa hồng
- Khoản tiền thưởng cho môi giới khi hoàn thành giao dịch (deposit đã thanh toán, hợp đồng thuê signed, hóa đơn đã thanh toán, etc.)

**Company Hóa đơn** - Hóa đơn công ty
- Hóa đơn chi phí của công ty (master hợp đồng thuê, ticket cost, payroll, nhà cung cấp thanh toán, etc.)

**Hợp đồng Number** - Số hợp đồng
- Mã số định danh duy nhất cho hợp đồng thuê (format: HD-YYYYMM-XXXX)

### D

**Deposit Refund** - Hoàn tiền cọc
- Quá trình trả lại tiền cọc cho khách thuê sau khi kết thúc hợp đồng (sau khi trừ các khoản nợ)

**Bảng điều khiển** - Bảng điều khiển
- Trang tổng quan hiển thị thống kê, báo cáo, và thông tin quan trọng

### E

**Excel Xuất** - Xuất Excel
- Chức năng xuất dữ liệu từ hệ thống ra file Excel để phân tích hoặc lưu trữ

### I

**Hóa đơn** - Hóa đơn
- Tài liệu yêu cầu thanh toán cho khách thuê hoặc landlord, chứa các items (rent, service, meter, etc.)

**Hóa đơn Item** - Dòng hóa đơn
- Một dòng trong hóa đơn, mô tả khoản phí cụ thể (rent, service fee, meter reading, etc.)

### L

**Landlord** - Chủ nhà
- Vai trò trong hệ thống, có thể xem bất động sản, hợp đồng thuê, thanh toán, và báo cáo tài chính

**Lead** - Khách hàng tiềm năng
- Người có nhu cầu thuê nhưng chưa ký hợp đồng, có thể chuyển đổi thành khách thuê

**Hợp đồng thuê** - Hợp đồng thuê
- Thỏa thuận giữa khách thuê và tổ chức về việc thuê phòng trong khoảng thời gian nhất định

**Hợp đồng thuê Resident** - Cư dân
- Người sống trong phòng nhưng không phải khách thuê chính (không ký hợp đồng)

**Hợp đồng thuê Service** - Dịch vụ hợp đồng
- Dịch vụ được áp dụng cho hợp đồng thuê (điện, nước, internet, etc.)

### M

**Quản lý** - Quản lý
- Vai trò trong hệ thống, có toàn quyền trong tổ chức, quản lý bất động sản, phòng, hợp đồng thuê, nhân viên

**Master Hợp đồng thuê** - Hợp đồng tổng
- Hợp đồng giữa tổ chức và chủ nhà (landlord), thường có revenue sharing

**Meter** - Công tơ
- Thiết bị đo điện/nước/gas, được gắn vào phòng

**Meter Reading** - Chỉ số công tơ
- Giá trị đọc được từ công tơ tại thời điểm nhất định, dùng để tính tiền điện/nước

### O

**Tổ chức** - Tổ chức
- Đơn vị vận hành trong hệ thống (công ty bất động sản), có thể có nhiều bất động sản và người dùng

**Tổ chức Người dùng** - Thành viên tổ chức
- Người dùng được gán vào tổ chức với một vai trò cụ thể (quản lý, môi giới, etc.)

### P

**Thanh toán** - Thanh toán
- Giao dịch thanh toán cho hóa đơn, có thể là cash, bank transfer, hoặc SePay

**Thanh toán Cycle** - Chu kỳ thanh toán
- Xem "Billing Cycle"

**Thanh toán Method** - Phương thức thanh toán
- Cách thức thanh toán (cash, bank_transfer, sepay, etc.)

**Payroll** - Tính lương
- Quy trình tính và phát lương cho nhân viên theo chu kỳ (tháng, tuần, etc.)

**Payroll Cycle** - Chu kỳ lương
- Kỳ lương được tính (thường là tháng, format: YYYY-MM)

**Payroll Payslip** - Phiếu lương
- Tài liệu chi tiết về lương của nhân viên trong một chu kỳ

**Bất động sản** - Bất động sản
- Tòa nhà hoặc tài sản cho thuê (ví dụ: chung cư, nhà trọ, etc.)

**Bất động sản Loại** - Loại bất động sản
- Phân loại bất động sản (apartment, house, dormitory, shared_house, etc.)

### R

**Review** - Đánh giá
- Nhận xét và đánh giá của khách thuê về phòng sau khi thuê

**Role** - Vai trò
- Vai trò của người dùng trong hệ thống (SuperAdmin, Quản lý, Môi giới, Landlord, Khách thuê)

### S

**Salary Advance** - Tạm ứng lương
- Khoản tiền ứng trước từ lương tương lai, được trừ dần vào các kỳ lương sau

**Salary Hợp đồng** - Hợp đồng lương
- Thỏa thuận về mức lương và các khoản phụ cấp cho nhân viên

**SePay** - Hệ thống thanh toán
- Gateway thanh toán trực tuyến, hỗ trợ chuyển khoản và QR code

**Service** - Dịch vụ
- Dịch vụ trong hệ thống (điện, nước, internet, etc.), có thể được gán vào hợp đồng thuê

**Nhân viên** - Nhân viên
- Người dùng có vai trò Môi giới hoặc Quản lý trong tổ chức

**Trạng thái** - Trạng thái
- Tình trạng hiện tại của entity (ví dụ: đang chờ, đã phê duyệt, đã thanh toán, cancelled, etc.)

**Subscription Plan** - Gói đăng ký
- Gói dịch vụ cho tổ chức trong mô hình SAAS, quy định số lượng bất động sản, phòng, người dùng được phép

**SuperAdmin** - Quản trị viên hệ thống
- Vai trò cao nhất, quản lý toàn hệ thống, organizations, và subscription plans

### T

**Khách thuê** - Người thuê
- Vai trò trong hệ thống, có thể xem hợp đồng, thanh toán hóa đơn, tạo ticket, đánh giá

**Ticket** - Phiếu bảo trì
- Yêu cầu sửa chữa hoặc bảo trì từ khách thuê hoặc môi giới, có thể có chi phí

**Ticket Log** - Nhật ký ticket
- Ghi chú về tiến trình xử lý ticket, có thể chứa chi phí và thông tin nhà cung cấp

### U

**Phòng** - Phòng/Căn
- Đơn vị cho thuê trong bất động sản (phòng trọ, căn hộ, etc.), thuộc về một bất động sản

**Phòng Amenity** - Tiện ích của phòng
- Tiện ích cụ thể của một phòng (wifi, điều hòa, máy nước nóng, etc.)

**Phòng Loại** - Loại phòng
- Phân loại phòng (room, apartment, dorm, shared, etc.)

**Người dùng** - Người dùng
- Tài khoản trong hệ thống, có thể là SuperAdmin, Quản lý, Môi giới, Landlord, hoặc Khách thuê

**Người dùng Banking** - Thông tin ngân hàng
- Thông tin tài khoản ngân hàng của người dùng, dùng cho thanh toán và nhận lương

### V

**Nhà cung cấp** - Nhà cung cấp
- Nhà cung cấp dịch vụ hoặc hàng hóa (thợ sửa chữa, nhà cung cấp internet, etc.)

**Viewing** - Lịch xem phòng
- Cuộc hẹn để khách thuê/lead xem bất động sản/unit trước khi quyết định thuê

### W

**Webhook** - Webhook
- Callback từ hệ thống bên ngoài (SePay) để cập nhật trạng thái thanh toán

**Workflow** - Quy trình
- Chuỗi các bước xử lý nghiệp vụ theo thứ tự nhất định

---

## Ký hiệu và định dạng

### Trạng thái Codes

#### Hóa đơn Trạng thái
- `draft` - Nháp (chưa phát hành)
- `issued` - Đã phát hành
- `paid` - Đã thanh toán đủ
- `overdue` - Quá hạn thanh toán
- `cancelled` - Đã hủy

#### Thanh toán Trạng thái
- `pending` - Đang chờ xử lý
- `success` - Thanh toán thành công
- `failed` - Thanh toán thất bại
- `refunded` - Đã hoàn tiền

#### Booking Deposit Thanh toán Trạng thái
- `pending_approval` - Chờ duyệt
- `pending` - Chờ thanh toán
- `paid` - Đã thanh toán
- `refunded` - Đã hoàn tiền
- `expired` - Đã hết hạn
- `cancelled` - Đã hủy

#### Hợp đồng thuê Trạng thái
- `draft` - Nháp
- `active` - Đang hoạt động
- `terminated` - Đã chấm dứt
- `expired` - Đã hết hạn

#### Company Hóa đơn Trạng thái
- `draft` - Nháp
- `pending` - Chờ xử lý
- `approved` - Đã phê duyệt
- `paid` - Đã thanh toán
- `overdue` - Quá hạn
- `cancelled` - Đã hủy

#### Cash Outflow Trạng thái
- `pending` - Chờ xử lý
- `success` - Thành công
- `failed` - Thất bại
- `reversed` - Đã đảo ngược

#### Ticket Trạng thái
- `open` - Mở
- `in_progress` - Đang xử lý
- `resolved` - Đã giải quyết
- `closed` - Đã đóng
- `cancelled` - Đã hủy

#### Deposit Refund Trạng thái
- `pending` - Chờ duyệt
- `approved` - Đã phê duyệt
- `paid` - Đã thanh toán
- `cancelled` - Đã hủy

#### General Trạng thái
- `active` - Đang hoạt động
- `inactive` - Không hoạt động

### Format Codes

- Hợp đồng Number: `HD{org_id}{YYYY}{MM}{XXXX}` (ví dụ: HD32025110001)
- Hóa đơn Number: `HD{org_id}{YYYY}{MM}{XXXX}` (tương tự hợp đồng number, ví dụ: HD32025110001)
- Payroll Cycle: `YYYY-MM` (ví dụ: 2025-11)
- OTP: 6 chữ số

### Thanh toán Methods

Thanh toán methods được lưu trong bảng `payment_methods` với `key_code`:
- `cash` - Tiền mặt
- `bank_transfer` - Chuyển khoản ngân hàng
- `sepay` - Thanh toán qua SePay (QR code, chuyển khoản)
- `other` - Phương thức khác

**Lưu ý**: Thanh toán methods được quản lý qua bảng `payment_methods`, không phải hard-coded enum.

### Deposit Types

- `booking` - Cọc đặt phòng
- `security` - Tiền cọc an toàn
- `advance` - Tạm ứng

---

**Cập nhật**: 2025-01-XX

