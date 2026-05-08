# QUY TRÌNH TỪ TICKET ĐẾN HÓA ĐƠN

## Tổng quan

Quy trình này mô tả các bước từ khi Khách thuê tạo Ticket (yêu cầu bảo trì) đến khi chi phí được thêm vào hóa đơn và Khách thuê thanh toán.

## Workflow

### Bước 1: Khách thuê tạo Ticket

**Người thực hiện:** Khách thuê

**Các bước:**
1. Truy cập **Tickets** → **Tạo**
2. Chọn **Bất động sản** và **Phòng** (từ hợp đồng thuê của Khách thuê)
3. Điền thông tin:
   - Title: Tiêu đề ticket (ví dụ: "Máy nước nóng không hoạt động")
   - Description: Mô tả chi tiết sự cố
   - Priority: Độ ưu tiên (low, medium, high, urgent)
   - Images: Upload hình ảnh sự cố (nếu có)
4. Click **Lưu**
5. Ticket được tạo với trạng thái `open`
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Xem chi tiết:**
- [Khách thuê Tickets](../tenant/09-tickets.md)

### Bước 2: Môi giới/Manager Xử lý Ticket

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Môi giới hoặc Quản lý nhận thông báo về ticket mới
2. Truy cập **Tickets** → Chọn ticket cần xử lý
3. Môi giới hoặc Quản lý assign ticket cho mình hoặc Môi giới khác
4. Môi giới hoặc Quản lý bắt đầu xử lý → ticket trạng thái = `in_progress`
5. Môi giới hoặc Quản lý xử lý sự cố và thêm Ticket Log với:
   - Action: Hành động thực hiện (ví dụ: "Thay máy nước nóng")
   - Detail: Chi tiết xử lý
   - Cost Số tiền: Chi phí (nếu có)
   - Charge To: Đối tượng chịu chi phí:
     - `none`: Không tính phí
     - `tenant_deposit`: Trừ vào tiền cọc
     - `tenant_invoice`: Thêm vào hóa đơn của khách thuê
     - `landlord`: Tạo company hóa đơn cho landlord
     - `vendor`: Nhà cung cấp tự thanh toán
   - Nhà cung cấp: Nhà cung cấp (nếu có)
   - Warranty Period Days: Thời hạn bảo hành (nếu có)
6. Click **Lưu**
7. Hệ thống lưu ticket log

**Xem chi tiết:**
- [Quản lý Tickets](../manager/14-tickets.md)
- [Môi giới Tickets](../agent/13-tickets.md)

### Bước 3: Chọn Đối tượng Chịu Chi phí (Charge To)

**Người thực hiện:** Môi giới hoặc Quản lý

**Tùy chọn:**

#### 3.1. Charge to Khách thuê Deposit (Trừ vào Tiền cọc)

1. Trong Ticket Log, chọn **Charge To** = `tenant_deposit`
2. Nhập **Cost Số tiền**
3. Click **Lưu**
4. Hệ thống trừ cost vào Deposit Số tiền của Hợp đồng thuê
5. Deposit Số tiền giảm đi theo Cost Số tiền

**Lưu ý**: 
- Chi phí được trừ trực tiếp vào tiền cọc
- Deposit Số tiền sẽ giảm theo Cost Số tiền
- Khi kết thúc hợp đồng thuê, refund số tiền sẽ là Deposit Số tiền còn lại

#### 3.2. Charge to Khách thuê Hóa đơn (Thêm vào Hóa đơn)

1. Trong Ticket Log, chọn **Charge To** = `tenant_invoice`
2. Nhập **Cost Số tiền**
3. Chọn **Linked Hóa đơn ID** (hóa đơn chưa issued của hợp đồng thuê)
4. Click **Lưu**
5. Hệ thống tạo Hóa đơn Item trong hóa đơn được chọn:
   - Item Loại: `ticket_cost`
   - Description: Mô tả từ ticket log
   - Quantity: 1
   - Phòng Price: Cost Số tiền
   - Số tiền: Cost Số tiền
6. Hệ thống cập nhật hóa đơn Tổng Số tiền
7. Hóa đơn Item được liên kết với Ticket Log

**Lưu ý**: 
- Chi phí được thêm vào hóa đơn của khách thuê
- Hóa đơn Item được thêm vào hóa đơn chưa issued
- Nếu không có hóa đơn chưa issued, hệ thống có thể tạo hóa đơn mới

#### 3.3. Charge to Landlord (Tạo Company Hóa đơn)

1. Trong Ticket Log, chọn **Charge To** = `landlord`
2. Nhập **Cost Số tiền**
3. Click **Lưu**
4. Hệ thống tạo Company Hóa đơn:
   - Hóa đơn Loại: `ticket_cost`
   - Nhà cung cấp: Khách thuê (hoặc tổ chức)
   - Số tiền: Cost Số tiền
   - Source Loại: `ticket`
   - Source ID: Ticket ID
5. Company Hóa đơn được tạo với trạng thái `draft`

**Lưu ý**: 
- Chi phí được charge cho landlord
- Company Hóa đơn được tạo tự động
- Quản lý cần approve và mark đã thanh toán company hóa đơn

#### 3.4. Charge to Nhà cung cấp (Nhà cung cấp tự thanh toán)

1. Trong Ticket Log, chọn **Charge To** = `vendor`
2. Nhập **Cost Số tiền**
3. Chọn **Nhà cung cấp ID** (nhà cung cấp thực hiện)
4. Click **Lưu**
5. Hệ thống lưu thông tin nhà cung cấp
6. Nhà cung cấp tự thanh toán chi phí

**Lưu ý**: 
- Chi phí do nhà cung cấp tự thanh toán
- Không tạo hóa đơn trong hệ thống
- Chỉ ghi nhận thông tin

### Bước 4: Tạo Hóa đơn Item (Nếu Charge to Khách thuê Hóa đơn)

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống tự động tạo Hóa đơn Item trong hóa đơn được chọn:
   - **Item Loại**: `ticket_cost`
   - **Description**: Mô tả từ ticket log (ví dụ: "Sửa chữa máy nước nóng - Ticket #123")
   - **Quantity**: 1
   - **Phòng Price**: Cost Số tiền từ ticket log
   - **Số tiền**: Cost Số tiền
2. Hệ thống cập nhật hóa đơn:
   - Subtotal: Tăng lên theo Cost Số tiền
   - Tổng Số tiền: Tăng lên theo Cost Số tiền
3. Hóa đơn Item được liên kết với Ticket Log

**Lưu ý**: 
- Hóa đơn Item được thêm vào hóa đơn chưa issued
- Nếu không có hóa đơn chưa issued, hệ thống có thể tạo hóa đơn mới
- Hóa đơn Tổng Số tiền được cập nhật tự động

### Bước 5: Môi giới/Manager Mark Ticket Resolved

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Sau khi xử lý xong ticket, Môi giới hoặc Quản lý mark resolved
2. Ticket trạng thái chuyển sang `resolved`
3. Hệ thống gửi thông báo cho Khách thuê
4. Khách thuê xác nhận đã xử lý xong → ticket trạng thái = `closed`
5. Hoặc ticket tự động closed sau một khoảng thời gian

### Bước 6: Khách thuê Thanh toán Hóa đơn

**Người thực hiện:** Khách thuê

**Các bước:**
1. Khách thuê nhận thông báo về hóa đơn có ticket cost
2. Khách thuê truy cập **Hóa đơn** → Xem hóa đơn có ticket cost item
3. Khách thuê thanh toán hóa đơn (xem [Khách thuê Hóa đơn](../tenant/07-invoices.md))
4. Hóa đơn trạng thái chuyển sang `paid`
5. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Khách thuê thanh toán hóa đơn bao gồm cả ticket cost
- Ticket cost được tính vào tổng số tiền của hóa đơn

## Trạng thái và Chuyển đổi

### Ticket Trạng thái Flow

```
open → in_progress → resolved → closed
```

### Ticket Log Charge To Flow

```
Ticket Log → Charge To:
  - tenant_deposit → Update Deposit Amount
  - tenant_invoice → Create Invoice Item → Invoice → Tenant Payment
  - landlord → Create Company Invoice → Manager Payment
  - vendor → Vendor Pays
  - none → No Charge
```

### Hóa đơn Trạng thái Flow

```
draft → issued → paid
```

### Company Hóa đơn Trạng thái Flow (Nếu Charge to Landlord)

```
draft → approved → paid
```

## Ràng buộc

1. **Ticket phải có Hợp đồng thuê**
   - Ticket phải link với hợp đồng thuê của khách thuê
   - Không thể charge cost nếu không có hợp đồng thuê

2. **Charge to Khách thuê Hóa đơn**
   - Phải có hóa đơn chưa issued của hợp đồng thuê
   - Nếu không có, hệ thống có thể tạo hóa đơn mới
   - Hóa đơn Item được thêm vào hóa đơn chưa issued

3. **Charge to Khách thuê Deposit**
   - Deposit Số tiền phải >= Cost Số tiền
   - Nếu không đủ, phải charge to khách thuê hóa đơn hoặc tạo hóa đơn mới

4. **Cost Số tiền**
   - Phải >= 0
   - Nếu Cost Số tiền = 0, không charge (Charge To = none)

## Ví dụ

### Ví dụ 1: Charge to Khách thuê Hóa đơn

**Kịch bản:** Khách thuê báo sự cố máy nước nóng, có chi phí sửa chữa

**Ticket:**
- Title: "Máy nước nóng không hoạt động"
- Phòng: P101
- Trạng thái: `open`

**Các bước:**
1. Khách thuê tạo ticket
2. Môi giới assign ticket và bắt đầu xử lý → trạng thái `in_progress`
3. Môi giới sửa chữa và thêm Ticket Log:
   - Action: "Thay máy nước nóng mới"
   - Detail: "Đã thay máy nước nóng mới, model ABC"
   - Cost Số tiền: 2,000,000 VND
   - Charge To: `tenant_invoice`
   - Nhà cung cấp: Nhà cung cấp ABC
4. Môi giới chọn Hóa đơn HD-202502-0001 (hóa đơn chưa issued)
5. Hệ thống tạo Hóa đơn Item:
   - Item Loại: `ticket_cost`
   - Description: "Sửa chữa máy nước nóng - Ticket #123"
   - Số tiền: 2,000,000 VND
6. Hóa đơn Tổng Số tiền tăng từ 10,000,000 VND lên 12,000,000 VND
7. Môi giới mark ticket resolved → trạng thái `resolved`
8. Khách thuê xác nhận → trạng thái `closed`
9. Quản lý issue hóa đơn → trạng thái `issued`
10. Khách thuê thanh toán hóa đơn (bao gồm cả ticket cost)

### Ví dụ 2: Charge to Khách thuê Deposit

**Kịch bản:** Khách thuê làm hỏng tài sản, có chi phí sửa chữa

**Ticket:**
- Title: "Hỏng cửa phòng"
- Phòng: P101
- Trạng thái: `open`

**Các bước:**
1. Khách thuê tạo ticket
2. Môi giới xử lý và thêm Ticket Log:
   - Cost Số tiền: 1,000,000 VND
   - Charge To: `tenant_deposit`
3. Hệ thống trừ cost vào Deposit Số tiền:
   - Deposit Số tiền ban đầu: 20,000,000 VND
   - Deposit Số tiền sau khi trừ: 19,000,000 VND
4. Khi kết thúc hợp đồng thuê, refund số tiền = 19,000,000 VND (trừ đi các khoản khác nếu có)

### Ví dụ 3: Charge to Landlord

**Kịch bản:** Sửa chữa do hư hỏng tự nhiên, landlord chịu chi phí

**Ticket:**
- Title: "Hỏng hệ thống điện"
- Phòng: P101
- Trạng thái: `open`

**Các bước:**
1. Khách thuê tạo ticket
2. Môi giới xử lý và thêm Ticket Log:
   - Cost Số tiền: 5,000,000 VND
   - Charge To: `landlord`
3. Hệ thống tạo Company Hóa đơn:
   - Hóa đơn Loại: `ticket_cost`
   - Số tiền: 5,000,000 VND
   - Source Loại: `ticket`
   - Source ID: Ticket ID
4. Quản lý approve và mark đã thanh toán company hóa đơn

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

5. **Warranty Period**
   - Có thể thiết lập thời hạn bảo hành cho ticket log
   - Theo dõi warranty period để xử lý nếu có vấn đề

---

**Xem thêm:**
- [Khách thuê Tickets](../tenant/09-tickets.md)
- [Quản lý Tickets](../manager/14-tickets.md)
- [Môi giới Tickets](../agent/13-tickets.md)
- [Khách thuê Hóa đơn](../tenant/07-invoices.md)

**Cập nhật**: 2025-11-02

