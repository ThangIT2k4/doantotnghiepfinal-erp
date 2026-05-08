# TÀI LIỆU HƯỚNG DẪN SỬ DỤNG

## Thông tin liên hệ

Tên: Trần Đức Thắng
Địa chỉ: Đ. Xuân Phương, Xuân Phương, Hà Nội
Liên hệ: 0988470962



## Hệ Thống Quản Lý Bất Động Sản Cho Thuê

---

## Tổng quan

Đây là bộ tài liệu hướng dẫn sử dụng đầy đủ và chi tiết cho hệ thống Quản lý Bất động sản Cho thuê. Tài liệu được tổ chức theo từng vai trò người dùng và chức năng, bao gồm các bước thực hiện cụ thể, ràng buộc, điều kiện, và quy trình nghiệp vụ.

## Đối tượng sử dụng

- **SuperAdmin**: Quản lý toàn hệ thống, tổ chức, và subscription plans (sử dụng `/superadmin/*` routes)
- **Nhân viên (Quản lý/Agent)**: Quản lý tổ chức, bất động sản, phòng, hợp đồng thuê, nhân viên, và tài chính (sử dụng `/staff/*` routes - unified)
- **Khách thuê**: Xem hợp đồng, thanh toán hóa đơn, tạo ticket, và đánh giá (sử dụng `/tenant/*` routes)

**Lưu ý quan trọng**: 
- **Quản lý và Môi giới đã được unified** vào cùng một hệ thống routes `/staff/*`. Tất cả các chức năng của Quản lý và Môi giới đều truy cập qua `/staff/*` với phân quyền dựa trên role và capabilities.
- **Quản lý** có quyền truy cập đầy đủ tất cả chức năng trong `/staff/*`
- **Môi giới** truy cập `/staff/*` nhưng bị giới hạn bởi capabilities được Quản lý cấp
- Hệ thống sử dụng **ERP Modules** để tổ chức các chức năng theo module (Asset, CRM, Finance, Party, Maintenance, etc.)
- Hệ thống sử dụng **Capability-based permissions** thay vì role-based đơn thuần

## Cấu trúc tài liệu

### 1. SuperAdmin
Tài liệu cho vai trò SuperAdmin, bao gồm:
- Xác thực (Đăng nhập/Đăng xuất)
- Quản lý Tổ chức
- Quản lý Người dùng
- Quản lý Gói Đăng ký
- Quản lý Đăng ký của Tổ chức
- Bảng điều khiển

**Xem chi tiết**: [superadmin/](./superadmin/)

### 2. Nhân viên (Quản lý & Môi giới - Unified)
Tài liệu cho vai trò Nhân viên (unified cho cả Quản lý và Môi giới), bao gồm 35 chức năng chính:
- Xác thực & Bảng điều khiển (với ERP Modules)
- Hồ sơ Management (với OTP email change)
- ERP Modules System (Asset, CRM, Finance, Party, Maintenance, etc.)
- Quản lý Bất động sản (Bất động sản, Phòng)
- Quản lý Hợp đồng (Hợp đồng thuê, Master Hợp đồng thuê)
- Quản lý Khách hàng (Khách thuê, Leads)
- Quản lý Lịch xem phòng (Viewings)
- Quản lý Đặt cọc & Hoàn tiền (Booking Deposits, Deposit Refunds)
- Quản lý Tài chính (Hóa đơn, Thanh toán, Company Hóa đơn, Cash Outflows)
- Quản lý Bảo trì (Tickets, Meters, Meter Readings)
- Quản lý Đánh giá (Reviews)
- Quản lý Nhân sự (Commission, Payroll, Salary, Nhân viên)
- Quản lý Hệ thống (Người dùng, Capabilities, Cài đặt, Excel Xuất, SePay)

**Lưu ý**: 
- Quản lý có quyền truy cập đầy đủ tất cả chức năng
- Môi giới bị giới hạn bởi capabilities được Quản lý cấp
- Tất cả routes đều sử dụng `/staff/*` (unified)

**Xem chi tiết**: [nhân viên/](./staff/)

**Tài liệu cũ (để tham khảo)**:
- [quản lý/](./manager/) - Tài liệu cũ cho Quản lý (đã được unified vào nhân viên/)
- [môi giới/](./agent/) - Tài liệu cũ cho Môi giới (đã được unified vào nhân viên/)

### 4. Khách thuê
Tài liệu cho vai trò Khách thuê, bao gồm 11 chức năng:
- Xác thực (Đăng ký, Đăng nhập, Quên mật khẩu, Email Verification)
- Bảng điều khiển & Hồ sơ
- Người dùng Banking
- Quản lý Appointments (Lịch xem phòng)
- Xem Hợp đồng
- Xem & Thanh toán Hóa đơn
- Xem Thanh toán
- Quản lý Tickets
- Tạo & Quản lý Reviews
- Thông báo

**Xem chi tiết**: [khách thuê/](./tenant/)

### 5. Workflows
Tài liệu mô tả các quy trình nghiệp vụ chính:
- Lead to Hợp đồng thuê: Quy trình từ Lead đến Hợp đồng thuê
- Hợp đồng thuê to Thanh toán: Quy trình từ Hợp đồng thuê đến Thanh toán
- Ticket to Hóa đơn: Quy trình từ Ticket đến Hóa đơn
- Commission Calculation: Quy trình Tính toán Hoa hồng
- Payroll Process: Quy trình Tính Lương và Phát Lương
- Booking Deposit Refund: Quy trình Đặt Cọc và Hoàn Tiền
- Meter Reading to Hóa đơn: Quy trình Đọc Chỉ số Công tơ đến Hóa đơn

**Xem chi tiết**: [workflows/](./workflows/)

### 6. Common
Tài liệu chung cho tất cả người dùng:
- Routes Mapping: Ánh xạ routes
- Glossary: Từ vựng và Định nghĩa
- Constraints: Ràng buộc và Điều kiện
- Error Handling: Xử lý Lỗi
- FAQ: Câu hỏi Thường gặp
- Company Information: Thông tin doanh nghiệp ZoroRMS

**Xem chi tiết**: [common/](./common/)

## Cấu trúc mỗi file tài liệu

Mỗi file tài liệu chức năng sẽ bao gồm:

1. **Tổng quan**: Mô tả ngắn gọn chức năng
2. **Quyền truy cập**: Ai có thể sử dụng chức năng này
3. **Các bước thực hiện**: Chi tiết từng bước thực hiện
4. **Ràng buộc và điều kiện**: Validation rules và business rules
5. **Trạng thái và Workflow**: Trạng thái flow và state transitions
6. **Ví dụ**: Ví dụ cụ thể minh họa
7. **Lưu ý**: Tips, warnings, và best practices
8. **Troubleshooting**: Xử lý sự cố thường gặp

## Cách sử dụng tài liệu

1. **Tìm tài liệu theo vai trò**: Điều hướng đến folder tương ứng với vai trò của bạn
2. **Tìm tài liệu theo chức năng**: Mỗi file được đánh số và đặt tên theo chức năng cụ thể
3. **Tham khảo Workflows**: Xem folder `workflows/` để hiểu quy trình nghiệp vụ tổng thể
4. **Tra cứu thuật ngữ**: Xem `common/glossary.md`/glossary.md` để hiểu các thuật ngữ trong hệ thống
5. **Xử lý lỗi**: Tham khảo `common/error-handling.md`/error-handling.md` và `common/faq.md`/faq.md`

## Quy ước trong tài liệu

- **Route**: Đường dẫn URL trong hệ thống
  - Nhân viên (Quản lý/Agent): `/staff/*` (unified routes)
  - Khách thuê: `/tenant/*`
  - SuperAdmin: `/superadmin/*`
- **Trạng thái**: Trạng thái của entity (ví dụ: `pending`, `approved`, `paid`)
- **CRUD**: Tạo, Read, Cập nhật, Xóa - các thao tác cơ bản
- **Validation**: Quy tắc kiểm tra dữ liệu đầu vào
- **Workflow**: Quy trình xử lý nghiệp vụ
- **Constraint**: Ràng buộc không được vi phạm
- **Capability**: Quyền hạn chi tiết được Quản lý cấp cho Môi giới (relational system)
- **ERP Module**: Module trong hệ thống ERP (Asset, CRM, Finance, Party, Maintenance, etc.)
- **Tổ chức**: Tổ chức/công ty sử dụng hệ thống (multi-khách thuê)

## Liên hệ và hỗ trợ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng:
1. Tham khảo `common/faq.md`/faq.md` để xem câu hỏi thường gặp
2. Xem `common/error-handling.md`/error-handling.md` để xử lý lỗi
3. Liên hệ quản trị viên hệ thống

---

**Phiên bản tài liệu**: 2.1  
**Cập nhật**: 2025-11-11  
**Dựa trên**: SRS.md v1.0, Laravel 12, Unified Nhân viên Routes, ERP Modules System, Capability-based Permissions

