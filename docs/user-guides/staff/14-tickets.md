# QUẢN LÝ TICKET BẢO TRÌ - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý ticket bảo trì/sự cố (tickets) trong tổ chức, bao gồm xem, tạo, cập nhật, xóa, assign, add log, upload documents, cost management, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả tickets trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `work.access`
  - Tạo ticket: Cần capability `work.ticket.create`
  - Cập nhật ticket: Cần capability `work.ticket.update`
  - Xem tất cả tickets: Cần capability `work.ticket.view` hoặc `work.ticket.view_all`
  - Chỉ xem tickets từ phòng/leases được gán: Có capability `work.ticket.view_own` (mặc định)
  - Assign tickets: Cần capability `work.ticket.update`
  - Add log/Upload documents: Cần capability `work.ticket.update`
  - Xóa ticket: Cần capability `work.ticket.delete`

**Route**: `/staff/tickets`

## Các bước thực hiện

### 1. Xem danh sách Tickets

1. Truy cập **Tickets** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả tickets trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (open, in_progress, resolved, closed, cancelled)
   - Priority (low, medium, high, urgent)
   - Phòng (nếu có nhiều phòng)
   - Hợp đồng thuê (nếu có nhiều hợp đồng thuê)
   - Khách thuê (nếu có nhiều khách thuê)
   - Assigned To (Môi giới/Manager, nếu có nhiều agents)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, priority, trạng thái

### 2. Xem chi tiết Ticket

1. Click vào ticket trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Ticket ID: Mã ticket
     - Title: Tiêu đề
     - Description: Mô tả
     - Bất động sản, Phòng: Bất động sản và phòng
     - Hợp đồng thuê: Hợp đồng liên quan
     - Trạng thái: Trạng thái hiện tại
     - Priority: Độ ưu tiên
     - Assigned To: Người được gán xử lý (Môi giới/Manager)
   - **Thông tin khác:**
     - Images: Hình ảnh sự cố (nếu có)
     - Created At: Ngày tạo ticket
     - Updated At: Ngày cập nhật ticket
     - Created By: Người tạo ticket (Khách thuê)
   - **Ticket Logs:**
     - Danh sách các logs xử lý ticket
     - Mỗi log chứa: Action, Detail, Cost Số tiền, Charge To, Nhà cung cấp, Created At, Created By

### 3. Tạo Ticket mới

1. Click **Tạo Ticket** hoặc **+ New**
2. Điền thông tin:
   - **Bất động sản** (bắt buộc): Chọn bất động sản
   - **Phòng** (bắt buộc): Chọn phòng (từ bất động sản)
   - **Hợp đồng thuê** (tự động): Được link từ phòng
   - **Title** (bắt buộc): Tiêu đề ticket
   - **Description** (bắt buộc): Mô tả chi tiết sự cố
   - **Priority** (bắt buộc): Độ ưu tiên (low, medium, high, urgent)
   - **Assigned To** (tùy chọn): Chọn Môi giới/Manager xử lý
   - **Images** (tùy chọn): Upload hình ảnh sự cố
   - **Trạng thái** (tự động): `open`
3. Click **Lưu**
4. Ticket được tạo với trạng thái `open`
5. Hệ thống gửi thông báo cho Môi giới (nếu có assign) và Quản lý

### 4. Assign Ticket (Gán Ticket)

1. Truy cập chi tiết ticket cần assign
2. Click **Assign** hoặc **Gán**
3. Chọn Môi giới hoặc Quản lý từ danh sách
4. Click **Lưu**
5. Ticket được assign cho Môi giới/Manager
6. Hệ thống gửi thông báo cho Môi giới/Manager được assign

**Lưu ý**: 
- Có thể assign ticket cho Môi giới hoặc Quản lý
- Ticket có thể được reassign cho người khác

### 5. Cập nhật Trạng thái

1. Truy cập chi tiết ticket cần cập nhật trạng thái
2. Click **Change Trạng thái** hoặc **Cập nhật Trạng thái**
3. Chọn trạng thái mới:
   - **open**: Ticket đã được tạo, chờ xử lý
   - **in_progress**: Ticket đang được xử lý
   - **resolved**: Ticket đã được giải quyết
   - **closed**: Ticket đã đóng
   - **cancelled**: Ticket đã hủy
4. Click **Lưu**
5. Hệ thống cập nhật trạng thái
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Có thể chuyển trạng thái trực tiếp hoặc thông qua hành động (mark in progress, mark resolved, close, hủy)

### 6. Mark In Progress (Đánh dấu Đang Xử lý)

1. Truy cập chi tiết ticket có trạng thái `open`
2. Click **Mark In Progress** hoặc **Đánh dấu đang xử lý**
3. Ticket trạng thái chuyển sang `in_progress`
4. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

### 7. Mark Resolved (Đánh dấu Đã Giải quyết)

1. Truy cập chi tiết ticket có trạng thái `in_progress`
2. Click **Mark Resolved** hoặc **Đánh dấu đã giải quyết**
3. Ticket trạng thái chuyển sang `resolved`
4. Hệ thống gửi thông báo cho Khách thuê
5. Khách thuê có thể xác nhận đã xử lý xong → Trạng thái `closed`

### 8. Close Ticket (Đóng Ticket)

1. Truy cập chi tiết ticket có trạng thái `resolved`
2. Click **Close** hoặc **Đóng**
3. Ticket trạng thái chuyển sang `closed`
4. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Có thể close ticket trực tiếp hoặc để Khách thuê xác nhận
- Ticket closed sẽ không được hiển thị trong danh sách hoạt động

### 9. Hủy Ticket (Hủy Ticket)

1. Truy cập chi tiết ticket cần hủy
2. Click **Hủy** hoặc **Hủy**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Ticket trạng thái chuyển sang `cancelled`
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy ticket có trạng thái `open` hoặc `in_progress`
- Không thể hủy ticket đã `resolved` hoặc `closed`

### 10. Add Ticket Log (Thêm Log Xử lý)

1. Truy cập chi tiết ticket cần thêm log
2. Click **Add Log** hoặc **Thêm log**
3. Điền thông tin:
   - **Action** (bắt buộc): Hành động thực hiện (ví dụ: "Thay máy nước nóng")
   - **Detail** (bắt buộc): Chi tiết xử lý
   - **Cost Số tiền** (tùy chọn): Chi phí (nếu có)
   - **Charge To** (tùy chọn): Đối tượng chịu chi phí:
     - `none`: Không tính phí
     - `tenant_deposit`: Trừ vào tiền cọc
     - `tenant_invoice`: Thêm vào hóa đơn của khách thuê
     - `landlord`: Tạo company hóa đơn cho landlord
     - `vendor`: Nhà cung cấp tự thanh toán
   - **Nhà cung cấp** (tùy chọn): Nhà cung cấp (nếu có)
   - **Warranty Period Days** (tùy chọn): Thời hạn bảo hành (nếu có)
4. Click **Lưu**
5. Ticket Log được thêm vào ticket
6. Hệ thống tự động xử lý cost nếu Charge To được chọn:
   - `tenant_deposit`: Trừ vào Deposit Số tiền của Hợp đồng thuê
   - `tenant_invoice`: Tạo Hóa đơn Item trong hóa đơn chưa issued
   - `landlord`: Tạo Company Hóa đơn
   - `vendor`: Lưu thông tin nhà cung cấp
7. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

### 11. Upload Documents

1. Truy cập chi tiết ticket
2. Scroll đến phần **Documents**
3. Click **Upload Documents**
4. Chọn files (PDF, Word, Images)
5. Click **Upload**
6. Hệ thống upload và hiển thị documents
7. Có thể download hoặc xóa documents sau khi upload

### 12. Cập nhật Ticket

1. Truy cập chi tiết ticket cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin:
   - Title
   - Description
   - Priority
   - Assigned To
   - Images (thêm hoặc xóa)
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Có thể cập nhật ticket bất cứ lúc nào
- Cập nhật ticket không ảnh hưởng đến logs

### 13. Xóa Ticket

1. Truy cập chi tiết ticket cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa ticket

**Lưu ý**: 
- Có thể xóa ticket bất cứ lúc nào
- Xóa ticket không ảnh hưởng đến logs và cost đã charge

### 14. Xem Thống kê

1. Truy cập **Tickets** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Tickets by Trạng thái: Phân bố theo trạng thái
   - Tickets by Priority: Phân bố theo độ ưu tiên
   - Tickets by Period: Phân bố theo thời gian
   - Average Resolution Thời gian: Thời gian giải quyết trung bình
   - Tổng Cost: Tổng chi phí tickets
   - Cost by Charge To: Chi phí theo đối tượng chịu

## Ràng buộc và điều kiện

### Validation Rules

- **Bất động sản**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Phòng**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về bất động sản
- **Hợp đồng thuê**: 
  - Tự động link từ phòng
  - Phải có hợp đồng thuê hoạt động cho phòng
- **Title**: 
  - Bắt buộc
  - Không được để trống
  - Max 255 ký tự
- **Description**: 
  - Bắt buộc
  - Không được để trống
- **Priority**: 
  - Bắt buộc
  - Phải là một trong: low, medium, high, urgent
- **Trạng thái**: 
  - Khi tạo: `open`
  - Phải là một trong: open, in_progress, resolved, closed, cancelled
- **Images**: 
  - Tùy chọn
  - Phải là file ảnh hợp lệ
  - Max size: 5MB mỗi file

### Business Rules

1. **Ticket phải có Hợp đồng thuê**
   - Ticket phải link với hợp đồng thuê của phòng
   - Không thể tạo ticket nếu không có hợp đồng thuê hoạt động

2. **Charge To Options**
   - `tenant_deposit`: Trừ vào Deposit Số tiền của Hợp đồng thuê
   - `tenant_invoice`: Tạo Hóa đơn Item trong hóa đơn chưa issued
   - `landlord`: Tạo Company Hóa đơn
   - `vendor`: Nhà cung cấp tự thanh toán

3. **Auto-tạo Hóa đơn Item**
   - Nếu Charge To = `tenant_invoice`, hệ thống tự động tạo Hóa đơn Item
   - Hóa đơn Item được thêm vào hóa đơn chưa issued của hợp đồng thuê
   - Nếu không có hóa đơn chưa issued, hệ thống có thể tạo hóa đơn mới

4. **Auto-tạo Company Hóa đơn**
   - Nếu Charge To = `landlord`, hệ thống tự động tạo Company Hóa đơn
   - Company Hóa đơn có trạng thái `draft` cho đến khi Quản lý approve

5. **Trạng thái Flow**
   - `open` → `in_progress` → `resolved` → `closed`
   - `open` hoặc `in_progress` → `cancelled`

## Trạng thái và Workflow

### Ticket Trạng thái Flow

```
open → in_progress → resolved → closed
  ↓         ↓
cancelled cancelled
```

- **open**: Ticket đã được tạo, chờ xử lý
- **in_progress**: Ticket đang được xử lý
- **resolved**: Ticket đã được giải quyết
- **closed**: Ticket đã đóng
- **cancelled**: Ticket đã hủy

### Workflow Tạo Ticket

1. Khách thuê tạo ticket mới
2. Điền thông tin: Bất động sản, Phòng, Title, Description, Priority, Images
3. Hệ thống tự động link với Hợp đồng thuê
4. Click Lưu → Ticket có trạng thái `open`
5. Quản lý assign ticket cho Môi giới → Môi giới bắt đầu xử lý
6. Môi giới mark in progress → Trạng thái `in_progress`
7. Môi giới xử lý và thêm Ticket Log (với cost nếu có)
8. Môi giới mark resolved → Trạng thái `resolved`
9. Khách thuê xác nhận đã xử lý xong → Trạng thái `closed`
10. Hoặc Quản lý có thể close ticket trực tiếp → Trạng thái `closed`

## Ví dụ

### Ví dụ 1: Tạo Ticket và Add Log với Cost

**Thông tin ticket:**
- Bất động sản: Bất động sản ABC
- Phòng: Phòng 101
- Title: "Máy nước nóng không hoạt động"
- Description: "Máy nước nóng trong phòng tắm không hoạt động. Đã kiểm tra nguồn điện nhưng vẫn không hoạt động."
- Priority: `high`
- Trạng thái: `open`

**Ticket Log:**
- Action: "Thay máy nước nóng mới"
- Detail: "Đã thay máy nước nóng mới, model ABC"
- Cost Số tiền: 2,000,000 VND
- Charge To: `tenant_invoice`
- Nhà cung cấp: Nhà cung cấp ABC

**Các bước:**
1. Khách thuê tạo ticket với thông tin trên
2. Quản lý assign ticket cho Môi giới B
3. Môi giới B mark in progress → Trạng thái `in_progress`
4. Môi giới B xử lý và thêm Ticket Log với cost
5. Hệ thống tự động tạo Hóa đơn Item trong hóa đơn chưa issued
6. Môi giới B mark resolved → Trạng thái `resolved`
7. Khách thuê xác nhận → Trạng thái `closed`

### Ví dụ 2: Charge to Khách thuê Deposit

**Kịch bản:** Khách thuê làm hỏng tài sản, có chi phí sửa chữa

**Ticket Log:**
- Action: "Sửa chữa cửa phòng"
- Detail: "Cửa phòng bị hỏng do sử dụng không đúng"
- Cost Số tiền: 1,000,000 VND
- Charge To: `tenant_deposit`

**Các bước:**
1. Quản lý hoặc Môi giới thêm Ticket Log với cost
2. Chọn Charge To: `tenant_deposit`
3. Hệ thống tự động trừ cost vào Deposit Số tiền:
   - Deposit Số tiền ban đầu: 20,000,000 VND
   - Deposit Số tiền sau khi trừ: 19,000,000 VND
4. Khi kết thúc hợp đồng thuê, refund số tiền = 19,000,000 VND

## Lưu ý

1. **Cost Management**
   - Chi phí có thể được charge to nhiều đối tượng khác nhau
   - Chọn đối tượng chịu chi phí phù hợp với tình huống

2. **Charge to Khách thuê Hóa đơn**
   - Chi phí được thêm vào hóa đơn của khách thuê
   - Khách thuê thanh toán cùng với tiền thuê

3. **Charge to Khách thuê Deposit**
   - Chi phí được trừ từ tiền cọc
   - Refund số tiền sẽ giảm tương ứng

4. **Charge to Landlord**
   - Chi phí do landlord chịu
   - Tạo company hóa đơn để thanh toán

5. **Ticket Logs**
   - Logs hiển thị tiến trình xử lý ticket
   - Logs có thể chứa chi phí và thông tin nhà cung cấp

## Troubleshooting

### Không thể tạo ticket

1. Kiểm tra Bất động sản và Phòng có tồn tại không
2. Kiểm tra Phòng có hợp đồng thuê hoạt động không
3. Kiểm tra tất cả các trường bắt buộc đã điền chưa
4. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể add log với cost

1. Kiểm tra Hợp đồng thuê có tồn tại không
2. Kiểm tra Cost Số tiền > 0
3. Kiểm tra Charge To có được chọn không
4. Kiểm tra hóa đơn chưa issued có tồn tại không (nếu Charge To = tenant_invoice)
5. Liên hệ hỗ trợ nếu vẫn không thể add log

---

**Xem thêm:**
- [Quản lý Phòng](./04-units.md)
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Quản lý Hóa đơn](./12-invoices.md)
- [Workflow Ticket to Hóa đơn](../workflows/03-ticket-to-invoice.md)

**Cập nhật: 2025-01-XX

