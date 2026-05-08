# QUẢN LÝ TICKET BẢO TRÌ - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê tạo và quản lý ticket bảo trì/sự cố cho phòng của mình.

## Quyền truy cập

- **Khách thuê**: Có quyền tạo và quản lý tickets của chính mình

**Route**: `/tenant/tickets`

## Các bước thực hiện

### 1. Xem danh sách Tickets

1. Truy cập **Tickets** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả tickets của Khách thuê
3. Có thể lọc theo:
   - Trạng thái (open, in_progress, resolved, closed, cancelled)
   - Priority (low, medium, high, urgent)
   - Phòng (nếu có nhiều phòng)
   - Hợp đồng thuê (nếu có nhiều hợp đồng)
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
   - **Ticket Logs:**
     - Danh sách các logs xử lý ticket
     - Mỗi log chứa: Action, Detail, Cost Số tiền, Charge To, Created At
     - Logs hiển thị tiến trình xử lý ticket

### 3. Tạo Ticket mới

1. Click **Tạo Ticket** hoặc **+ New**
2. Điền thông tin:
   - **Bất động sản** (bắt buộc): Chọn bất động sản (từ hợp đồng thuê của Khách thuê)
   - **Phòng** (bắt buộc): Chọn phòng (từ hợp đồng thuê của Khách thuê)
   - **Hợp đồng thuê** (tự động): Được link từ phòng
   - **Title** (bắt buộc): Tiêu đề ticket (ví dụ: "Máy nước nóng không hoạt động")
   - **Description** (bắt buộc): Mô tả chi tiết sự cố
   - **Priority** (bắt buộc): Độ ưu tiên (low, medium, high, urgent)
   - **Images** (tùy chọn): Upload hình ảnh sự cố
3. Click **Lưu**
4. Hệ thống tạo ticket với trạng thái `open`
5. Hệ thống gửi thông báo cho Môi giới và Quản lý
6. Ticket được assign cho Môi giới hoặc Quản lý (tùy cấu hình)

### 4. Cập nhật Ticket

1. Truy cập chi tiết ticket cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `open`)
3. Cập nhật thông tin:
   - Title
   - Description
   - Priority
   - Images (thêm hoặc xóa)
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật ticket có trạng thái `open`
- Không thể cập nhật ticket đã `in_progress`, `resolved`, hoặc `closed`

### 5. Xóa Ticket

1. Truy cập chi tiết ticket cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa ticket

**Lưu ý**: 
- Chỉ có thể xóa ticket có trạng thái `open` hoặc `cancelled`
- Không thể xóa ticket đã `in_progress`, `resolved`, hoặc `closed`

### 6. Hủy Ticket

1. Truy cập chi tiết ticket cần hủy
2. Click **Hủy**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Hệ thống cập nhật ticket trạng thái = `cancelled`
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy ticket có trạng thái `open` hoặc `in_progress`
- Không thể hủy ticket đã `resolved` hoặc `closed`

### 7. Xem Ticket Logs

1. Truy cập chi tiết ticket
2. Scroll đến phần **Ticket Logs**
3. Hệ thống hiển thị danh sách logs xử lý ticket với:
   - Action: Hành động thực hiện
   - Detail: Chi tiết xử lý
   - Cost Số tiền: Chi phí (nếu có)
   - Charge To: Đối tượng chịu chi phí (none, tenant_deposit, tenant_invoice, landlord, nhà cung cấp)
   - Nhà cung cấp: Nhà cung cấp (nếu có)
   - Created At: Thời gian tạo log
   - Created By: Người tạo log (Môi giới/Manager)

**Lưu ý**: 
- Logs hiển thị tiến trình xử lý ticket
- Logs có thể chứa chi phí và thông tin nhà cung cấp

## Ràng buộc và điều kiện

### Validation Rules

- **Bất động sản**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về Khách thuê
- **Phòng**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về Khách thuê
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
  - Max size: 5MB mỗi file (tùy cấu hình)

### Business Rules

1. **Chỉ có thể tạo ticket cho phòng của mình**
   - Bất động sản và Phòng phải thuộc về hợp đồng thuê của Khách thuê
   - Không thể tạo ticket cho phòng khác

2. **Chỉ có thể cập nhật ticket có trạng thái `open`**
   - Không thể cập nhật ticket đã `in_progress`, `resolved`, hoặc `closed`
   - Liên hệ Môi giới hoặc Quản lý nếu cần cập nhật

3. **Ticket Logs**
   - Logs được tạo bởi Môi giới hoặc Quản lý
   - Logs có thể chứa chi phí và thông tin nhà cung cấp
   - Chi phí có thể charge to: tenant_deposit, tenant_invoice, landlord, nhà cung cấp

4. **Cost Management**
   - Nếu ticket có cost, cost có thể được charge to:
     - Khách thuê Deposit: Trừ vào tiền cọc
     - Khách thuê Hóa đơn: Thêm vào hóa đơn
     - Landlord: Tạo company hóa đơn
     - Nhà cung cấp: Nhà cung cấp tự thanh toán

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
3. Hệ thống tạo ticket với trạng thái `open`
4. Hệ thống gửi thông báo cho Môi giới và Quản lý
5. Môi giới hoặc Quản lý assign ticket và bắt đầu xử lý → trạng thái `in_progress`
6. Môi giới hoặc Quản lý xử lý ticket và thêm logs
7. Môi giới hoặc Quản lý mark resolved → trạng thái `resolved`
8. Khách thuê xác nhận đã xử lý xong → trạng thái `closed`
9. Hoặc Khách thuê có thể hủy ticket → trạng thái `cancelled`

## Ví dụ

### Ví dụ 1: Tạo Ticket mới

**Kịch bản:** Khách thuê muốn báo sự cố máy nước nóng không hoạt động

**Các bước:**
1. Truy cập Tickets
2. Click **Tạo Ticket**
3. Điền thông tin:
   - Bất động sản: `Property ABC` (từ hợp đồng thuê)
   - Phòng: `Unit 101` (từ hợp đồng thuê)
   - Hợp đồng thuê: Tự động link từ phòng
   - Title: `Máy nước nóng không hoạt động`
   - Description: `Máy nước nóng trong phòng tắm không hoạt động. Đã kiểm tra nguồn điện nhưng vẫn không hoạt động.`
   - Priority: `high`
   - Images: Upload ảnh máy nước nóng
4. Click **Lưu**
5. Hệ thống tạo ticket với trạng thái `open`
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

### Ví dụ 2: Cập nhật Ticket

**Kịch bản:** Khách thuê muốn cập nhật thông tin ticket

**Các bước:**
1. Truy cập chi tiết ticket có trạng thái `open`
2. Click **Chỉnh sửa**
3. Cập nhật Description: Thêm thông tin "Đã kiểm tra lại, máy vẫn không hoạt động"
4. Upload thêm hình ảnh
5. Click **Lưu**
6. Hệ thống cập nhật ticket

### Ví dụ 3: Hủy Ticket

**Kịch bản:** Khách thuê đã tự xử lý sự cố và muốn hủy ticket

**Các bước:**
1. Truy cập chi tiết ticket có trạng thái `open`
2. Click **Hủy**
3. Nhập lý do: "Đã tự xử lý xong"
4. Xác nhận hủy
5. Hệ thống cập nhật ticket trạng thái = `cancelled`

## Lưu ý

1. **Mô tả chi tiết**
   - Mô tả càng chi tiết càng giúp Môi giới/Manager xử lý nhanh
   - Upload hình ảnh để minh họa sự cố

2. **Priority**
   - Chọn priority phù hợp:
     - `low`: Vấn đề nhỏ, không gấp
     - `medium`: Vấn đề thông thường
     - `high`: Vấn đề quan trọng, cần xử lý sớm
     - `urgent`: Vấn đề khẩn cấp, cần xử lý ngay

3. **Cost Management**
   - Nếu ticket có chi phí, chi phí có thể được charge to:
     - Khách thuê Deposit: Trừ vào tiền cọc
     - Khách thuê Hóa đơn: Thêm vào hóa đơn
     - Landlord: Chủ nhà chịu chi phí
     - Nhà cung cấp: Nhà cung cấp tự thanh toán

4. **Ticket Trạng thái**
   - Theo dõi trạng thái của ticket để biết tiến trình xử lý
   - Liên hệ Môi giới hoặc Quản lý nếu ticket chậm xử lý

## Troubleshooting

### Không thể tạo ticket

1. Kiểm tra Bất động sản và Phòng có thuộc về Khách thuê không
2. Kiểm tra Khách thuê có hợp đồng thuê hoạt động cho phòng không
3. Kiểm tra tất cả các trường bắt buộc đã điền chưa
4. Kiểm tra images có đúng format và size không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể cập nhật ticket

1. Kiểm tra trạng thái của ticket (phải là `open`)
2. Chỉ có thể cập nhật ticket có trạng thái `open`
3. Liên hệ Môi giới hoặc Quản lý nếu cần cập nhật ticket đã `in_progress`

### Ticket chậm xử lý

1. Kiểm tra trạng thái của ticket
2. Liên hệ Môi giới hoặc Quản lý để hỏi tiến trình
3. Cập nhật priority nếu cần thiết

### Chi phí ticket

1. Xem Ticket Logs để biết chi phí
2. Kiểm tra Charge To để biết ai chịu chi phí
3. Nếu charge to Khách thuê Hóa đơn, chi phí sẽ được thêm vào hóa đơn tiếp theo
4. Nếu charge to Khách thuê Deposit, chi phí sẽ được trừ vào tiền cọc

---

**Lưu ý**: Tạo ticket sớm và mô tả chi tiết giúp Môi giới/Manager xử lý nhanh và hiệu quả.

**Cập nhật**: 2025-11-02

