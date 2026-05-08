# QUY TRÌNH TÍNH LƯƠNG VÀ PHÁT LƯƠNG

## Tổng quan

Quy trình này mô tả các bước từ khi Quản lý tạo Payroll Cycle đến khi thanh toán lương cho tất cả nhân viên.

## Workflow

### Bước 1: Tạo Payroll Cycle

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập **Payroll Cycles** → **Tạo**
2. Điền thông tin:
   - **Period Month** (bắt buộc, format YYYY-MM): Tháng tính lương (ví dụ: 2025-01)
   - **Trạng thái** (tự động): `open`
3. Click **Lưu**
4. Payroll Cycle được tạo với trạng thái `open`

**Xem chi tiết:**
- [Quản lý Payroll Cycles](../manager/22-payroll-cycles.md)

### Bước 2: Generate Payslips (Tạo Phiếu Lương)

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập chi tiết Payroll Cycle
2. Click **Generate Payslips** hoặc **Tạo Phiếu Lương**
3. Hệ thống tự động:
   - Lấy danh sách Người dùng có Salary Hợp đồng hoạt động trong period
   - Với mỗi người dùng:
     - Lấy Salary Hợp đồng hoạt động
     - Tính Gross Số tiền = Base Salary + Allowances
     - Lấy Salary Advances chưa trả hết
     - Tính Deduction Số tiền = Salary Advances + Other Deductions
     - Lấy Commission Events đã approve nhưng chưa thanh toán
     - Tính Net Số tiền = Gross Số tiền - Deduction Số tiền
     - Tạo Payroll Payslip với:
       - Người dùng ID
       - Gross Số tiền
       - Deduction Số tiền
       - Net Số tiền
       - Payroll Items (Base Salary, Allowances, Advances, Commissions, Deductions)
4. Hệ thống hiển thị danh sách payslips đã tạo
5. Quản lý có thể review và chỉnh sửa payslips

**Lưu ý**: 
- Payslips được tạo tự động cho tất cả người dùng có hoạt động hợp đồng
- Quản lý có thể chỉnh sửa payslips trước khi lock cycle

### Bước 3: Review Payslips

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập chi tiết Payroll Cycle
2. Scroll đến phần **Payslips**
3. Xem danh sách tất cả payslips đã generate
4. Review từng payslip:
   - Gross Số tiền: Base Salary + Allowances
   - Deduction Số tiền: Advances + Other Deductions
   - Net Số tiền: Gross - Deductions
   - Payroll Items: Chi tiết các khoản
5. Có thể chỉnh sửa payslip nếu cần:
   - Recalculate: Tính lại payslip
   - Chỉnh sửa Items: Sửa payroll items
   - Add Items: Thêm payroll items
   - Xóa Items: Xóa payroll items

**Lưu ý**: 
- Quản lý có thể chỉnh sửa payslips khi cycle trạng thái = `open`
- Sau khi lock cycle, không thể chỉnh sửa payslips

### Bước 4: Lock Payroll Cycle

**Người thực hiện:** Quản lý

**Các bước:**
1. Sau khi review và chỉnh sửa payslips xong, Quản lý lock cycle
2. Truy cập chi tiết Payroll Cycle
3. Click **Lock Cycle** hoặc **Khóa chu kỳ**
4. Xác nhận lock
5. Payroll Cycle trạng thái chuyển sang `locked`
6. Không thể chỉnh sửa payslips nữa

**Lưu ý**: 
- Lock cycle để đảm bảo payslips không bị thay đổi
- Sau khi lock, không thể chỉnh sửa payslips

### Bước 5: Phê duyệt Phát Lương

**Người thực hiện:** Quản lý

**Các bước:**
1. Sau khi lock cycle, Quản lý phê duyệt phát lương
2. Quản lý review tất cả payslips một lần nữa
3. Quản lý approve payroll → Cycle trạng thái vẫn là `locked`
4. Quản lý bắt đầu xử lý thanh toán cho từng payslip

### Bước 6: Tạo Company Hóa đơn cho Payslip

**Người thực hiện:** Quản lý (tự động hoặc thủ công)

**Các bước:**
1. Với mỗi payslip, Quản lý tạo Company Hóa đơn:
   - **Nhà cung cấp**: Người dùng (nhân viên)
   - **Hóa đơn Loại**: `payroll_payslip`
   - **Hóa đơn Number**: Tự động tạo (unique)
   - **Issue Ngày**: Ngày phát hành
   - **Đến hạn Ngày**: Ngày thanh toán
   - **Số tiền**: Net Số tiền từ payslip
   - **Items**: Payroll Items từ payslip
   - **Source Loại**: `payroll_payslip`
   - **Source ID**: Payslip ID
2. Company Hóa đơn được tạo với trạng thái `draft`
3. Quản lý approve company hóa đơn → trạng thái `approved`
4. Company Hóa đơn được link với Payslip

**Xem chi tiết:**
- [Quản lý Company Hóa đơn](../manager/24-company-invoices.md)

### Bước 7: Thanh toán Lương

**Người thực hiện:** Quản lý

**Các bước:**
1. Với mỗi company hóa đơn, Quản lý xử lý thanh toán:
   
#### 7.1. Thanh toán bằng Bank Transfer

1. Chọn **Bank Transfer**
2. Chuyển khoản vào tài khoản ngân hàng của người dùng
3. Nhập Transaction Reference
4. Mark thanh toán success
5. Hệ thống tạo Cash Outflow record

#### 7.2. Thanh toán bằng Cash

1. Chọn **Cash**
2. Thanh toán tiền mặt cho người dùng
3. Mark thanh toán success
4. Hệ thống tạo Cash Outflow record

#### 7.3. Thanh toán qua SePay

1. Chọn **SePay**
2. Chuyển khoản qua SePay
3. Nhập Transaction Reference
4. Chờ SePay webhook callback
5. Hệ thống tự động cập nhật thanh toán trạng thái
6. Hệ thống tạo Cash Outflow record

2. Sau khi thanh toán thành công:
   - Company Hóa đơn trạng thái = `paid`
   - Payslip trạng thái = `paid`
   - Hệ thống cập nhật Salary Advances đã trả
   - Hệ thống cập nhật Commission Events đã thanh toán
   - Hệ thống gửi thông báo cho Người dùng

### Bước 8: Hoàn tất Payroll Cycle

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Sau khi tất cả payslips được thanh toán, Payroll Cycle trạng thái chuyển sang `paid`
2. Hệ thống gửi thông báo cho tất cả người dùng đã nhận lương
3. Payroll Cycle hoàn tất

## Trạng thái và Chuyển đổi

### Payroll Cycle Trạng thái Flow

```
open → locked → paid
```

- **open**: Cycle đang mở, có thể chỉnh sửa payslips
- **locked**: Cycle đã khóa, không thể chỉnh sửa payslips
- **đã thanh toán**: Cycle đã thanh toán xong

### Payslip Trạng thái Flow

```
draft → paid
```

- **draft**: Payslip đã được tạo, chờ thanh toán
- **đã thanh toán**: Payslip đã được thanh toán

### Company Hóa đơn Trạng thái Flow (cho Payslip)

```
draft → approved → paid
```

## Ràng buộc

1. **Không thể chỉnh sửa payslips sau khi lock cycle**
   - Payslips chỉ có thể chỉnh sửa khi cycle trạng thái = `open`
   - Sau khi lock, phải unlock để chỉnh sửa (nếu có chức năng unlock)

2. **Payslip phải được tạo Company Hóa đơn trước khi thanh toán**
   - Tạo company hóa đơn để track thanh toán
   - Link payslip với company hóa đơn

3. **Thanh toán Số tiền phải = Net Số tiền**
   - Thanh toán số tiền phải khớp với Net Số tiền của payslip
   - Không thể thanh toán nhiều hơn hoặc ít hơn

4. **Salary Advances phải được trừ vào payslip**
   - Advances chưa trả hết được trừ vào Deduction Số tiền
   - Sau khi thanh toán payslip, advances được đánh dấu đã trả

5. **Commission Events phải được thêm vào payslip**
   - Commission events đã approve nhưng chưa thanh toán được thêm vào payslip
   - Sau khi thanh toán payslip, commission events được đánh dấu đã thanh toán

## Ví dụ

### Ví dụ hoàn chỉnh

**Payroll Cycle:**
- Period Month: `2025-01`
- Trạng thái: `open`

**Người dùng: Môi giới A**
- Salary Hợp đồng: Base Salary = 10,000,000 VND, Allowances = 2,000,000 VND
- Salary Advances: 1,000,000 VND (chưa trả)
- Commission Events: 500,000 VND (đã approve, chưa thanh toán)

**Bước 1:** Quản lý tạo Payroll Cycle 2025-01

**Bước 2:** Quản lý generate payslips:
- Môi giới A:
  - Gross Số tiền = 10,000,000 + 2,000,000 = 12,000,000 VND
  - Deduction Số tiền = 1,000,000 VND
  - Net Số tiền = 12,000,000 - 1,000,000 = 11,000,000 VND
  - Payroll Items:
    - Base Salary: 10,000,000 VND
    - Allowances: 2,000,000 VND
    - Advances: -1,000,000 VND
    - Commissions: 500,000 VND (thêm vào Gross)
    - Net: 11,500,000 VND

**Bước 3:** Quản lý review payslips

**Bước 4:** Quản lý lock cycle → trạng thái = `locked`

**Bước 5:** Quản lý approve payroll

**Bước 6:** Quản lý tạo Company Hóa đơn:
- Hóa đơn Loại: `payroll_payslip`
- Số tiền: 11,500,000 VND
- Link với Payslip

**Bước 7:** Quản lý thanh toán:
- Bank Transfer: 11,500,000 VND vào tài khoản Môi giới A
- Mark thanh toán success
- Payslip trạng thái = `paid`
- Advances được đánh dấu đã trả
- Commission Events được đánh dấu đã thanh toán

**Bước 8:** Cycle trạng thái = `paid`

## Lưu ý

1. **Generate Payslips**
   - Payslips được tạo tự động cho tất cả người dùng có hoạt động hợp đồng
   - Quản lý có thể chỉnh sửa trước khi lock

2. **Lock Cycle**
   - Lock cycle để đảm bảo payslips không bị thay đổi
   - Cân nhắc kỹ trước khi lock

3. **Thanh toán Processing**
   - Có thể thanh toán nhiều payslips cùng lúc
   - Mỗi payslip tạo một company hóa đơn riêng

4. **Advances và Commissions**
   - Advances được trừ vào Deduction Số tiền
   - Commissions được thêm vào Gross Số tiền (hoặc Deduction tùy cấu hình)

---

**Xem thêm:**
- [Quản lý Payroll Cycles](../manager/22-payroll-cycles.md)
- [Quản lý Payroll Payslips](../manager/23-payroll-payslips.md)
- [Quản lý Company Hóa đơn](../manager/24-company-invoices.md)

**Cập nhật**: 2025-11-02

