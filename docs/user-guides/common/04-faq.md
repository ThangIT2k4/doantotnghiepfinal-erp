# CÂU HỎI THƯỜNG GẶP (FAQ)

## Tổng quan

Tài liệu này trả lời các câu hỏi thường gặp về hệ thống Quản lý Bất động sản Cho thuê.

## Xác thực & Authorization

### Q1: Tôi quên mật khẩu, làm sao để lấy lại?

**A:** Sử dụng chức năng "Quên mật khẩu":
1. Truy cập trang đăng nhập
2. Click "Quên mật khẩu"
3. Nhập email hoặc số điện thoại
4. Nhận OTP qua email
5. Nhập OTP để xác thực
6. Nhập mật khẩu mới

Xem chi tiết: [Khách thuê Xác thực](./../tenant/01-authentication.md)

### Q2: Tôi không nhận được email OTP, phải làm sao?

**A:** 
1. Kiểm tra thư mục spam
2. Đợi vài phút (email có thể bị delay)
3. Sử dụng chức năng "Gửi lại OTP"
4. Kiểm tra email đã nhập đúng chưa
5. Liên hệ quản trị viên nếu vẫn không nhận được

### Q3: OTP có hiệu lực trong bao lâu?

**A:** OTP có hiệu lực trong 15 phút. Sau đó bạn cần gửi lại OTP mới.

### Q4: Tôi có thể đăng nhập bằng Google không?

**A:** Có, hệ thống hỗ trợ đăng nhập bằng Google OAuth. Click "Đăng nhập bằng Google" trên trang đăng nhập.

### Q5: Tôi không có quyền truy cập một chức năng, phải làm sao?

**A:** 
1. Liên hệ Quản lý trong tổ chức của bạn
2. Yêu cầu được cấp quyền (capability) cần thiết
3. Quản lý sẽ grant capability cho bạn

## Hợp đồng thuê & Hợp đồng

### Q6: Làm sao để tạo hợp đồng thuê mới?

**A:** 
1. Đảm bảo có Phòng available
2. Có Khách thuê hoặc Lead
3. Truy cập Hợp đồng thuê → Tạo
4. Điền đầy đủ thông tin (Phòng, Khách thuê, Start Ngày, End Ngày, Rent Số tiền, etc.)
5. Lưu

Xem chi tiết: [Nhân viên Hợp đồng thuê](./../staff/05-leases.md)

### Q7: Một Phòng có thể có bao nhiêu hợp đồng hoạt động?

**A:** Chỉ 1 hợp đồng hoạt động tại một thời điểm. Phải terminate hoặc chờ hết hạn hợp đồng hiện tại mới có thể tạo hợp đồng mới.

### Q8: Làm sao để gia hạn hợp đồng?

**A:** 
1. Truy cập Hợp đồng thuê cần gia hạn
2. Click "Renew"
3. Điền thông tin hợp đồng mới (Start Ngày, End Ngày mới)
4. Lưu

Hợp đồng cũ sẽ được đánh dấu là terminated, hợp đồng mới được tạo với thông tin mới.

Xem chi tiết: [Nhân viên Hợp đồng thuê](./../staff/05-leases.md)

### Q9: Làm sao để terminate hợp đồng trước hạn?

**A:** 
1. Truy cập Hợp đồng thuê cần terminate
2. Click "Terminate"
3. Nhập Termination Ngày và Termination Reason
4. Chọn có refund deposit không
5. Lưu

**Lưu ý:** Phải thanh toán tất cả hóa đơn còn nợ trước khi terminate.

Xem chi tiết: [Nhân viên Hợp đồng thuê](./../staff/05-leases.md)

## Hóa đơn & Thanh toán

### Q10: Hóa đơn được tạo tự động như thế nào?

**A:** Hệ thống tự động tạo hóa đơn theo Thanh toán Cycle của Hợp đồng thuê:
- Hàng tháng: Mỗi tháng 1 hóa đơn
- Hàng quý: Mỗi quý 1 hóa đơn
- Hàng năm: Mỗi năm 1 hóa đơn
- Tùy chỉnh: Theo số tháng đã cấu hình

Hóa đơn được tạo vào Billing Day của mỗi chu kỳ.

Xem chi tiết: [Nhân viên Hóa đơn](./../staff/12-invoices.md)

### Q11: Làm sao để thanh toán hóa đơn?

**A:** 
1. Truy cập Hóa đơn → Chọn hóa đơn cần thanh toán
2. Click "Pay" hoặc "Thanh toán"
3. Chọn phương thức thanh toán (Cash, Bank Transfer, SePay)
4. Nhập số tiền (<= remaining số tiền)
5. Xác nhận thanh toán

Xem chi tiết: [Khách thuê Hóa đơn](./../tenant/07-invoices.md)

### Q12: Tôi có thể thanh toán một phần hóa đơn không?

**A:** Có, bạn có thể thanh toán nhiều lần:
1. Tạo thanh toán với số tiền < remaining số tiền
2. Hóa đơn vẫn giữ trạng thái `issued` hoặc `overdue` (không có trạng thái `partially_paid`)
3. Hệ thống tự động tính remaining số tiền = total_amount - paid_amount
4. Tiếp tục thanh toán cho đến khi đủ
5. Hóa đơn sẽ chuyển sang trạng thái "Đã thanh toán" tự động khi total_paid >= total_amount

### Q13: Thanh toán qua SePay như thế nào?

**A:** 
1. Chọn phương thức "SePay" khi thanh toán
2. Hệ thống tạo thanh toán với trạng thái "đang chờ"
3. Scan QR code hoặc chuyển khoản theo thông tin được cung cấp (có mã hóa đơn trong nội dung chuyển khoản)
4. SePay gửi webhook callback về hệ thống
5. Hệ thống tự động cập nhật thanh toán trạng thái thành "success" hoặc "failed"
6. Hóa đơn tự động chuyển sang "đã thanh toán" nếu thanh toán đủ
7. Nếu webhook bị delay, có thể kiểm tra thủ công thanh toán trạng thái hoặc sử dụng "Check Đang chờ Thanh toán"

Xem chi tiết: [Khách thuê Thanh toán](./../tenant/08-payments.md)

## Booking Deposits

### Q14: Làm sao để tạo đặt cọc?

**A:** 
1. Đảm bảo Phòng có trạng thái "available"
2. Truy cập Booking Deposits → Tạo
3. Chọn Phòng, Khách thuê/Lead, Môi giới
4. Nhập Số tiền, Deposit Loại, Hold Until
5. Lưu

Deposit sẽ có trạng thái "đang chờ", cần Quản lý approve trước.

Xem chi tiết: [Nhân viên Booking Deposits](./../staff/10-booking-deposits.md)

### Q15: Đặt cọc có tự động hết hạn không?

**A:** Có, nếu quá Hold Until và payment_status chưa là `paid`, deposit sẽ tự động expire (payment_status = `expired`). Phòng sẽ được release về trạng thái "available" nếu không còn deposit hoạt động khác.

### Q16: Làm sao để hoàn tiền cọc?

**A:** 
1. Khi hợp đồng thuê kết thúc hoặc bị terminate
2. Hệ thống tính toán: Refund Số tiền = Deposit Số tiền - Deducted Số tiền
3. Tạo Deposit Refund record
4. Quản lý approve
5. Process thanh toán (Bank Transfer, Cash, SePay)

Xem chi tiết: [Nhân viên Deposit Refunds](./../staff/11-deposit-refunds.md) và [Workflow Booking Deposit Refund](./../workflows/06-booking-deposit-refund.md)

## Tickets & Maintenance

### Q17: Làm sao để tạo ticket bảo trì?

**A:** 
1. Truy cập Tickets → Tạo
2. Chọn Phòng (hoặc Hợp đồng thuê)
3. Nhập Title, Description
4. Upload hình ảnh (nếu có)
5. Chọn Priority
6. Lưu

Xem chi tiết: [Khách thuê Tickets](./../tenant/09-tickets.md)

### Q18: Ticket có thể có chi phí không?

**A:** Có, khi Môi giới/Manager xử lý ticket và thêm Ticket Log với Cost Số tiền, chi phí có thể được charge to:
- Khách thuê Deposit: Trừ vào tiền cọc
- Khách thuê Hóa đơn: Thêm vào hóa đơn của khách thuê
- Landlord: Tạo company hóa đơn
- Nhà cung cấp: Nhà cung cấp tự thanh toán

Xem chi tiết: [Nhân viên Tickets](./../staff/14-tickets.md)

## Meters & Readings

### Q19: Làm sao để ghi chỉ số công tơ?

**A:** 
1. Truy cập Meter Readings → Tạo
2. Chọn Meter
3. Nhập Reading Ngày, Value
4. Upload hình ảnh công tơ (nếu có)
5. Lưu

Hệ thống sẽ tự động:
- Tính Usage = Current Value - Last Value
- Tạo Hóa đơn Item nếu có Service trong Hợp đồng thuê

Xem chi tiết: [Nhân viên Meter Readings](./../staff/16-meter-readings.md)

### Q20: Chỉ số công tơ có thể giảm không?

**A:** Không, chỉ số mới phải >= chỉ số trước. Nếu chỉ số giảm, có thể:
- Công tơ bị hỏng và thay mới (cần tạo meter mới)
- Nhập sai chỉ số (kiểm tra lại)

## Commission & Payroll

### Q21: Hoa hồng được tính như thế nào?

**A:** Hoa hồng được tính tự động khi có trigger event:
- deposit_paid: Khi đặt cọc được thanh toán
- lease_signed: Khi ký hợp đồng
- invoice_paid: Khi hóa đơn được thanh toán
- viewing_done: Khi hoàn tất lịch xem phòng
- listing_published: Khi đăng tin

Hoa hồng được tính theo Commission Policy, có thể là:
- Percent: % từ số tiền
- Flat: Số tiền cố định
- Tiered: Theo bậc thang

Xem chi tiết: [Workflow Commission Calculation](./../workflows/04-commission-calculation.md)

### Q22: Hoa hồng có giới hạn số tháng không?

**A:** Có, nếu Commission Policy có Apply Limit Months (ví dụ: 3), hoa hồng chỉ được tính cho N tháng đầu tiên khi thanh toán hóa đơn. Từ tháng thứ (N+1) trở đi, không tính hoa hồng nữa.

### Q23: Làm sao để xem phiếu lương?

**A:** 
1. Truy cập Payslips (Môi giới) hoặc Payroll Payslips (Quản lý)
2. Chọn Payroll Cycle
3. Xem Payslip của mình

Payslip hiển thị:
- Gross Số tiền = Base Salary + Allowances
- Deduction Số tiền = Salary Advances + Other Deductions
- Net Số tiền = Gross - Deduction

Xem chi tiết: [Nhân viên Payroll Payslips](./../staff/21-payroll-payslips.md)

## Báo cáo & Analytics

### Q24: Làm sao để xem báo cáo doanh thu?

**A:** 
1. Truy cập Bảng điều khiển → Revenue Module (Quản lý)
2. Hoặc Báo cáo → Revenue Báo cáo (Môi giới)
3. Chọn period (week/month/year)
4. Xem biểu đồ và số liệu

Xem chi tiết: [Nhân viên Bảng điều khiển](./../staff/02-dashboard.md)

### Q25: Làm sao để xuất dữ liệu ra Excel?

**A:** 
1. Truy cập Excel Xuất (Quản lý)
2. Chọn Table cần xuất
3. Chọn Columns
4. Apply filters (nếu cần)
5. Click "Xuất"

Xem chi tiết: Excel Xuất đã được tích hợp vào các module, không còn route riêng

## Cài đặt & Cấu hình

### Q26: Làm sao để thay đổi mật khẩu?

**A:** 
1. Truy cập Hồ sơ → Cài đặt
2. Click "Change Password"
3. Nhập mật khẩu hiện tại
4. Nhập mật khẩu mới
5. Xác nhận mật khẩu mới
6. Lưu

### Q27: Làm sao để cập nhật thông tin ngân hàng?

**A:** 
1. Truy cập Người dùng Banking
2. Click "Chỉnh sửa" hoặc "Tạo"
3. Nhập thông tin ngân hàng (Bank, Account Number, Account Holder Name, etc.)
4. Lưu

Xem chi tiết: [Khách thuê Người dùng Banking](./../tenant/04-user-banking.md)

### Q28: Làm sao để cấu hình Thanh toán Cycle?

**A:** 
1. Quản lý truy cập Thanh toán Cycle Cài đặt
2. Có thể cấu hình ở 3 level:
   - Tổ chức level (áp dụng cho tất cả bất động sản)
   - Bất động sản level (áp dụng cho bất động sản cụ thể)
   - Hợp đồng thuê level (áp dụng cho hợp đồng thuê cụ thể)
3. Lưu

Xem chi tiết: [Nhân viên Thanh toán Cycle Cài đặt](./../staff/32-payment-cycle-settings.md)

## Thông báo

### Q29: Tôi nhận được thông báo nào?

**A:** Hệ thống gửi thông báo cho bạn khi:
- Hợp đồng thuê được tạo/cập nhật
- Hóa đơn được phát hành
- Thanh toán được nhận
- Ticket được tạo/cập nhật
- Viewing được xác nhận/hủy
- Booking deposit được approve
- Commission event được tạo
- Payroll payslip được generate
- Và nhiều sự kiện khác

### Q30: Làm sao để xem thông báo?

**A:** 
1. Click icon notification trên header
2. Hoặc truy cập Thông báo
3. Xem danh sách thông báo
4. Click để xem chi tiết
5. Mark as read để đánh dấu đã đọc

Xem chi tiết: [Khách thuê Thông báo](./../tenant/11-notifications.md)

## Troubleshooting

### Q31: Tôi không thấy dữ liệu, phải làm sao?

**A:** 
1. Kiểm tra tổ chức của bạn
2. Kiểm tra quyền truy cập (capabilities)
3. Kiểm tra filters trên trang
4. Liên hệ Quản lý nếu vẫn không thấy

### Q32: Hệ thống chậm, phải làm sao?

**A:** 
1. Refresh trang
2. Clear browser cache
3. Kiểm tra kết nối mạng
4. Đóng và mở lại trình duyệt
5. Liên hệ quản trị viên nếu vẫn chậm

### Q33: Tôi không thể upload file, phải làm sao?

**A:** 
1. Kiểm tra kích thước file (có thể quá lớn)
2. Kiểm tra format file (có thể không được hỗ trợ)
3. Thử file khác
4. Kiểm tra kết nối mạng
5. Liên hệ quản trị viên nếu vẫn lỗi

Xem chi tiết: [Error Handling](./03-error-handling.md)

---

**Lưu ý**: Nếu câu hỏi của bạn không có trong danh sách này, vui lòng:
1. Xem [Error Handling](./03-error-handling.md) để xử lý lỗi
2. Liên hệ quản trị viên hệ thống
3. Tham khảo tài liệu chi tiết theo chức năng trong các folder tương ứng

**Cập nhật**: 2025-01-XX

