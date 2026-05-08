# QUẢN LÝ HỢP ĐỒNG THUÊ - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý hợp đồng thuê (hợp đồng thuê) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, terminate, renew, và quản lý residents, services, documents.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả hợp đồng thuê trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Tạo hợp đồng thuê: Cần capability `contract.lease.create`
  - Cập nhật hợp đồng thuê: Cần capability `contract.lease.update`
  - Xem tất cả hợp đồng thuê: Cần capability `contract.lease.view` hoặc `contract.lease.view_all`
  - Chỉ xem hợp đồng thuê được gán: Có capability `contract.lease.view_own` (mặc định)
  - Xóa hợp đồng thuê: Cần capability `contract.lease.delete`
  - Terminate/Renew: Cần capability `contract.lease.update`

**Route**: `/staff/leases`

## Các bước thực hiện

### 1. Xem danh sách Hợp đồng thuê

1. Truy cập **Hợp đồng thuê** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả hợp đồng thuê trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (draft, hoạt động, terminated, expired)
   - Bất động sản, Phòng
   - Khách thuê
   - Môi giới
   - Start Ngày, End Ngày
   - Tìm kiếm by contract_no
   - Sắp xếp theo start_date, end_date, created_at, trạng thái

### 2. Xem chi tiết Hợp đồng thuê

1. Click vào hợp đồng thuê trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Hợp đồng Number: Số hợp đồng
     - Bất động sản, Phòng: Bất động sản và phòng
     - Khách thuê: Người thuê
     - Môi giới: Người quản lý hợp đồng
     - Start Ngày, End Ngày: Ngày bắt đầu và kết thúc
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Rent Số tiền: Tiền thuê
     - Deposit Số tiền: Tiền cọc
     - Thanh toán Cycle: Chu kỳ thanh toán (hiển thị từ payment_cycle_id, có thể fallback từ bất động sản hoặc tổ chức mặc định)
     - Billing Day: Ngày tạo hóa đơn (từ Thanh toán Cycle)
     - Thanh toán Day: Ngày thanh toán (từ Thanh toán Cycle)
   - **Thông tin khác:**
     - Hợp đồng thuê Service Set: Nhóm dịch vụ (điện, nước, internet, etc.) với giá tương ứng
     - Residents: Người ở cùng (nếu có)
     - Documents: Tài liệu hợp đồng (nếu có)
     - Booking Deposit: Booking deposit liên quan (nếu có)
     - Termination Ngày: Ngày chấm dứt (nếu đã terminate)
     - Termination Reason: Lý do chấm dứt (nếu có)

### 3. Tạo Hợp đồng thuê mới

1. Click **Tạo Hợp đồng thuê** hoặc **+ New**
2. Điền thông tin:
   - **Phòng** (bắt buộc): Chọn phòng cần cho thuê
   - **Lead** (bắt buộc): Chọn lead (khách hàng tiềm năng) - hệ thống sẽ tự động tạo khách thuê người dùng từ lead nếu chưa có
   - **Booking Deposit** (tùy chọn): Chọn booking deposit đã thanh toán (nếu có) - hệ thống sẽ tự động điền thông tin phòng, khách thuê, môi giới, deposit số tiền
   - **Môi giới** (tùy chọn): Chọn môi giới quản lý hợp đồng (tự động điền nếu có booking deposit hoặc bất động sản có môi giới được gán)
   - **Start Ngày** (bắt buộc): Ngày bắt đầu hợp đồng
   - **End Ngày** (bắt buộc): Ngày kết thúc hợp đồng (phải > Start Ngày)
   - **Rent Số tiền** (bắt buộc): Tiền thuê
   - **Deposit Số tiền** (tùy chọn): Tiền cọc (tự động điền nếu có booking deposit)
   - **Thanh toán Cycle** (tùy chọn): Chọn chu kỳ thanh toán từ danh sách (nếu không chọn, sẽ dùng mặc định từ bất động sản hoặc tổ chức)
   - **Hợp đồng thuê Service Set** (bắt buộc): Chọn nhóm dịch vụ cho hợp đồng (điện, nước, internet, etc.)
   - **Hợp đồng Number** (tự động): Số hợp đồng (tự động generate nếu không nhập, format: HD{org_id}{YYYY}{MM}{XXXX}, ví dụ: HD32025110001)
   - **Signed At** (tùy chọn): Ngày ký hợp đồng
   - **Trạng thái**: Luôn là `draft` khi tạo mới (không thể chọn `active` ngay)
   - **Residents** (tùy chọn): Thêm người ở cùng sau khi tạo hợp đồng thuê:
     - Có thể chọn người dùng có sẵn trong tổ chức
     - Hoặc thêm thông tin: Name, Phone, ID Card Number
3. Click **Lưu**
4. Hợp đồng thuê được tạo với trạng thái `draft`
5. Sau khi chuyển trạng thái sang `active`, phòng trạng thái tự động chuyển sang `occupied`

### 4. Cập nhật Hợp đồng thuê

1. Truy cập chi tiết hợp đồng thuê cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `draft`)
3. Cập nhật thông tin cần thay đổi
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật hợp đồng thuê có trạng thái `draft`
- Không thể cập nhật hợp đồng thuê đã `active`, `terminated`, hoặc `expired`
- Có thể terminate và tạo hợp đồng thuê mới nếu cần

### 5. Terminate Hợp đồng thuê (Chấm dứt Hợp đồng)

1. Truy cập chi tiết hợp đồng thuê cần terminate
2. Click **Terminate** hoặc **Chấm dứt**
3. Điền thông tin:
   - **Termination Ngày** (bắt buộc): Ngày chấm dứt (phải >= hôm nay)
   - **Termination Reason** (bắt buộc): Lý do chấm dứt
   - **Refund Deposit** (tùy chọn): Checkbox để chọn có hoàn cọc hay không
4. Click **Terminate**
5. Hệ thống tự động:
   - Cập nhật hợp đồng thuê trạng thái sang `terminated`
   - Cập nhật end_date = termination_date
   - Cập nhật phòng trạng thái sang `available`
   - Tính toán số tiền:
     - Unpaid hóa đơn: Tổng số tiền còn nợ từ các hóa đơn chưa thanh toán
     - Ticket deposit costs: Tổng chi phí từ tickets đã charge vào deposit
     - Net = Deposit Số tiền - Unpaid Hóa đơn - Ticket Deposit Costs
   - Nếu `refund_deposit = true` và `net > 0`: Tạo Deposit Refund
   - Nếu `net < 0`: Tạo Hóa đơn bù và hủy các hóa đơn cũ chưa thanh toán

**Lưu ý**: 
- Chỉ có thể terminate hợp đồng thuê có trạng thái `active`
- Termination Ngày phải >= hôm nay (không thể terminate trong quá khứ)
- Nếu không chọn "Refund Deposit", hệ thống chỉ terminate mà không tạo refund

### 6. Renew Hợp đồng thuê (Gia hạn Hợp đồng)

1. Truy cập chi tiết hợp đồng thuê cần renew
2. Click **Renew** hoặc **Gia hạn**
3. Điền thông tin gia hạn:
   - **New Start Ngày** (bắt buộc): Ngày bắt đầu mới (phải > End Ngày của hợp đồng thuê cũ)
   - **New End Ngày** (bắt buộc): Ngày kết thúc mới (phải > New Start Ngày)
   - **New Rent Số tiền** (tùy chọn): Tiền thuê mới (nếu không nhập, giữ nguyên rent số tiền cũ)
   - **Renewal Notes** (tùy chọn): Ghi chú về việc gia hạn
4. Click **Renew**
5. Hệ thống tự động:
   - Cập nhật hợp đồng thuê cũ:
     - Trạng thái = `expired`
     - End Ngày = New Start Ngày - 1 ngày
   - Tạo hợp đồng thuê mới với:
     - Phòng, Khách thuê, Môi giới: Giữ nguyên từ hợp đồng thuê cũ
     - Start Ngày = New Start Ngày
     - End Ngày = New End Ngày
     - Rent Số tiền = New Rent Số tiền (hoặc giữ nguyên)
     - Deposit Số tiền: Giữ nguyên
     - Thanh toán Cycle: Giữ nguyên
     - Hợp đồng thuê Service Set: Giữ nguyên
     - Trạng thái = `active`
     - Hợp đồng Number: Tự động generate mới
     - Signed At: Ngày hiện tại
   - Cập nhật phòng trạng thái = `occupied` (nếu chưa)

**Lưu ý**: 
- Chỉ có thể renew hợp đồng thuê có trạng thái `active`
- Renew tạo hợp đồng thuê mới, hợp đồng thuê cũ được chuyển sang `expired`
- Tất cả thông tin khác (services, thanh toán cycle, etc.) được copy từ hợp đồng thuê cũ

### 7. Upload Documents

1. Truy cập chi tiết hợp đồng thuê
2. Scroll đến phần **Documents**
3. Click **Upload Documents**
4. Chọn files (PDF, Word, Images)
5. Click **Upload**
6. Hệ thống upload và hiển thị documents
7. Có thể download hoặc xóa documents sau khi upload

### 8. Xóa Hợp đồng thuê

1. Truy cập chi tiết hợp đồng thuê cần xóa
2. Click **Xóa** (chỉ khi trạng thái = `draft`)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa hợp đồng thuê
5. Phòng trạng thái tự động chuyển sang `available` nếu hợp đồng thuê là hoạt động duy nhất

**Lưu ý**: 
- Chỉ có thể xóa hợp đồng thuê có trạng thái `draft`
- Không thể xóa hợp đồng thuê đã `active`, `terminated`, hoặc `expired`

## Ràng buộc và điều kiện

### Validation Rules

- **Phòng**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
  - Phải không có hợp đồng thuê hoạt động (trừ khi cập nhật chính hợp đồng thuê đó)
- **Lead**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
  - Hệ thống tự động tạo khách thuê người dùng từ lead nếu chưa có
  - Nếu lead đã có tenant_id, sẽ sử dụng lại khách thuê đó
- **Start Ngày**: 
  - Bắt buộc
  - Phải là ngày hợp lệ
- **End Ngày**: 
  - Bắt buộc
  - Phải > Start Ngày
- **Rent Số tiền**: 
  - Bắt buộc
  - Phải >= 0
- **Deposit Số tiền**: 
  - Tùy chọn
  - Phải >= 0
- **Thanh toán Cycle**: 
  - Tùy chọn
  - Phải tồn tại trong bảng payment_cycles
  - Nếu không chọn, sẽ dùng mặc định từ bất động sản hoặc tổ chức
- **Hợp đồng thuê Service Set**: 
  - Bắt buộc
  - Phải tồn tại trong bảng lease_service_sets
  - Chứa danh sách services và giá tương ứng
- **Booking Deposit**: 
  - Tùy chọn
  - Phải tồn tại và có payment_status = 'đã thanh toán'
  - Phải chưa có hợp đồng thuê nào sử dụng
- **Hợp đồng Number**: 
  - Tự động generate nếu không nhập
  - Phải unique trong tổ chức (nếu nhập thủ công)

### Business Rules

1. **Một phòng chỉ có 1 hợp đồng thuê hoạt động tại một thời điểm**
   - Không thể tạo nhiều hợp đồng thuê hoạt động cho cùng 1 phòng
   - Phải terminate hoặc chờ hết hạn hợp đồng thuê hiện tại
   - Kiểm tra tự động khi tạo hợp đồng thuê

2. **Phòng trạng thái tự động cập nhật**
   - `occupied`: Khi hợp đồng thuê trạng thái = `active`
   - `available`: Khi hợp đồng thuê được terminate hoặc xóa

3. **Auto-tạo Hóa đơn**
   - Hóa đơn được tạo tự động theo Thanh toán Cycle
   - Hóa đơn được tạo vào Billing Day mỗi chu kỳ

4. **Termination**
   - Chỉ có thể terminate hợp đồng thuê có trạng thái `active`
   - Termination Ngày phải >= hôm nay
   - Tính toán tự động: Net = Deposit - Unpaid Hóa đơn - Ticket Deposit Costs
   - Nếu chọn "Refund Deposit" và net > 0: Tạo Deposit Refund
   - Nếu net < 0: Tạo Hóa đơn bù và hủy các hóa đơn cũ chưa thanh toán

5. **Renewal**
   - Chỉ có thể renew hợp đồng thuê có trạng thái `active`
   - Renew tạo hợp đồng thuê mới với trạng thái `active`
   - Hợp đồng thuê cũ được chuyển sang trạng thái `expired`
   - Tất cả thông tin (services, thanh toán cycle, etc.) được copy từ hợp đồng thuê cũ

## Trạng thái và Workflow

### Hợp đồng thuê Trạng thái Flow

```
draft → active → terminated/expired
```/expired
```

- **draft**: Hợp đồng đang soạn thảo
- **hoạt động**: Hợp đồng đang hoạt động
- **terminated**: Hợp đồng đã chấm dứt trước hạn
- **expired**: Hợp đồng đã hết hạn

### Workflow Tạo Hợp đồng thuê

1. Quản lý hoặc Môi giới (có quyền) tạo hợp đồng thuê mới
2. Chọn Lead (bắt buộc) - hệ thống tự động tạo khách thuê người dùng từ lead nếu chưa có
3. Chọn Phòng (bắt buộc)
4. Có thể chọn Booking Deposit (nếu có, payment_status = 'đã thanh toán') - tự động điền thông tin phòng, khách thuê, môi giới, deposit số tiền
5. Điền thông tin: Môi giới, Dates, Amounts, Thanh toán Cycle (từ dropdown), Hợp đồng thuê Service Set (bắt buộc)
6. Click Lưu
7. Hợp đồng thuê được tạo với trạng thái `draft` (luôn là draft khi tạo mới, không thể chọn hoạt động ngay)
8. Quản lý hoặc Môi giới (có quyền) cần chuyển trạng thái sang `active` để kích hoạt hợp đồng
9. Khi trạng thái = `active`, phòng trạng thái tự động chuyển sang `occupied`
10. Hóa đơn được tạo tự động theo Thanh toán Cycle sau khi hợp đồng thuê hoạt động

## Ví dụ

### Ví dụ 1: Tạo Hợp đồng thuê mới

**Thông tin hợp đồng thuê:**
- Lead: Lead #123 (Nguyễn Văn A - chưa có khách thuê account)
- Phòng: P101 (Bất động sản ABC)
- Môi giới: Môi giới B
- Start Ngày: 2025-01-01
- End Ngày: 2025-12-31
- Rent Số tiền: 10,000,000 VND
- Deposit Số tiền: 20,000,000 VND
- Thanh toán Cycle: Hàng tháng (từ dropdown)
- Hợp đồng thuê Service Set: "Dịch vụ cơ bản" (chứa: Electricity 3,000 VND/kWh, Water 15,000 VND/m³, Internet 200,000 VND/tháng)
- Trạng thái: `draft` (mặc định)

**Các bước:**
1. Truy cập Hợp đồng thuê
2. Click **Tạo Hợp đồng thuê**
3. Chọn Lead #123 (hệ thống tự động tạo khách thuê người dùng "Nguyễn Văn A" nếu chưa có)
4. Chọn Phòng P101
5. Có thể chọn Booking Deposit (nếu đã thanh toán)
6. Điền thông tin: Môi giới, Dates, Amounts
7. Chọn Thanh toán Cycle "Hàng tháng" từ dropdown (hoặc để trống để dùng mặc định)
8. Chọn Hợp đồng thuê Service Set "Dịch vụ cơ bản" (bắt buộc)
9. Click **Lưu**
10. Hợp đồng thuê được tạo với trạng thái `draft`
11. Quản lý hoặc Môi giới (có quyền) chuyển trạng thái sang `active` để kích hoạt
12. Phòng trạng thái chuyển sang `occupied`
13. Hóa đơn đầu tiên được tạo tự động theo Thanh toán Cycle

### Ví dụ 2: Terminate Hợp đồng thuê

**Kịch bản:** Khách thuê muốn chấm dứt hợp đồng sớm

**Hợp đồng thuê:**
- Start Ngày: 2025-01-01
- End Ngày: 2025-12-31
- Deposit Số tiền: 20,000,000 VND
- Trạng thái: `active`
- Unpaid Hóa đơn: 5,000,000 VND
- Ticket Deposit Costs: 1,000,000 VND

**Các bước:**
1. Truy cập chi tiết hợp đồng thuê
2. Click **Terminate**
3. Điền thông tin:
   - Termination Ngày: 2025-06-30 (>= hôm nay)
   - Termination Reason: "Khách thuê yêu cầu chấm dứt sớm"
   - Refund Deposit: ✓ (checked)
4. Click **Terminate**
5. Hệ thống tự động tính toán:
   - Net = 20,000,000 - 5,000,000 - 1,000,000 = 14,000,000 VND
   - Vì net > 0 và refund_deposit = true: Tạo Deposit Refund 14,000,000 VND
6. Hợp đồng thuê trạng thái chuyển sang `terminated`
7. End Ngày được cập nhật = 2025-06-30
8. Phòng trạng thái chuyển sang `available`

**Trường hợp khác:** Nếu net < 0 (ví dụ: unpaid hóa đơn = 25,000,000 VND):
- Net = 20,000,000 - 25,000,000 - 1,000,000 = -6,000,000 VND
- Hệ thống tạo Hóa đơn bù 6,000,000 VND
- Hủy các hóa đơn cũ chưa thanh toán

## Lưu ý

1. **One Phòng, One Hoạt động Hợp đồng thuê**
   - Một phòng chỉ có thể có 1 hợp đồng thuê hoạt động
   - Kiểm tra kỹ trước khi tạo hợp đồng thuê mới

2. **Thanh toán Cycle**
   - Chọn Thanh toán Cycle từ dropdown (nếu không chọn, dùng mặc định)
   - Thanh toán Cycle được quản lý trong hệ thống, có thể fallback từ bất động sản hoặc tổ chức

3. **Hợp đồng thuê Service Set**
   - Bắt buộc phải chọn một Hợp đồng thuê Service Set
   - Service Set chứa danh sách services và giá tương ứng
   - Services có thể được sử dụng cho meter readings và tính toán hóa đơn tự động

4. **Residents**
   - Thêm residents để theo dõi người ở cùng
   - Residents có thể được thêm/sửa/xóa sau khi tạo hợp đồng thuê

5. **Termination**
   - Terminate sớm để giải phóng phòng
   - Deposit Refund được tạo tự động

## Troubleshooting

### Không thể tạo hợp đồng thuê

1. Kiểm tra phòng có hợp đồng thuê hoạt động không
2. Kiểm tra Lead đã được chọn chưa (bắt buộc)
3. Kiểm tra Hợp đồng thuê Service Set đã được chọn chưa (bắt buộc)
4. Kiểm tra End Ngày > Start Ngày
5. Kiểm tra Booking Deposit (nếu có) đã thanh toán và chưa có hợp đồng thuê nào sử dụng
6. Kiểm tra subscription limit (số lượng hợp đồng thuê tối đa của gói dịch vụ)
7. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể terminate hợp đồng thuê

1. Kiểm tra hợp đồng thuê có trạng thái `active` không
2. Kiểm tra Termination Ngày >= hôm nay (không thể terminate trong quá khứ)
3. Kiểm tra Termination Reason đã điền chưa
4. Liên hệ hỗ trợ nếu vẫn không thể terminate

### Phòng trạng thái không cập nhật

1. Refresh trang
2. Kiểm tra hợp đồng thuê trạng thái có đúng không
3. Liên hệ hỗ trợ nếu vẫn không cập nhật

---

**Xem thêm:**
- [Nhân viên Phòng](./04-units.md)
- [Nhân viên Khách thuê](./07-tenants.md)
- [Nhân viên Hóa đơn](./12-invoices.md)
- [Workflow Lead to Hợp đồng thuê](../workflows/01-lead-to-lease.md)
- [Workflow Hợp đồng thuê to Thanh toán](../workflows/02-lease-to-payment.md)

**Cập nhật: 2025-01-XX

