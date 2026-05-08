# DASHBOARD - STAFF (MANAGER & AGENT)

## Tổng quan

Bảng điều khiển của Nhân viên (Quản lý và Môi giới) cung cấp cái nhìn tổng quan về hoạt động của tổ chức, bao gồm thống kê, biểu đồ, và các ERP modules linh hoạt để theo dõi performance. Bảng điều khiển được unified cho cả Quản lý và Môi giới, nhưng dữ liệu hiển thị phụ thuộc vào quyền truy cập.

## Quyền truy cập

- **Quản lý**: Có quyền truy cập bảng điều khiển với đầy đủ thống kê và ERP modules (tất cả dữ liệu của tổ chức)
- **Môi giới**: Có quyền truy cập bảng điều khiển nhưng chỉ thấy dữ liệu được Quản lý cấp quyền (qua capabilities và ERP modules)

**Route**: `/staff/dashboard` (unified nhân viên bảng điều khiển)

## Các bước thực hiện

### 1. Truy cập Bảng điều khiển

1. Đăng nhập với tài khoản Nhân viên
2. Hệ thống tự động redirect đến `/staff/dashboard`
3. Hoặc click **Bảng điều khiển Vận Hành** từ menu Nhân viên

### 2. ERP Modules System

Bảng điều khiển hỗ trợ **ERP Modules System** - hệ thống module linh hoạt cho phép Nhân viên chọn/bỏ chọn các ERP modules để hiển thị:

#### 2.1. Truy cập ERP Modules

1. Click **ERP Modules** từ menu hoặc truy cập `/staff/modules`
2. Hệ thống hiển thị danh sách tất cả ERP modules:
   - **Asset Module**: Quản lý Bất động sản, Phòng
   - **CRM Module**: Quản lý Leads, Viewings, Reviews
   - **Finance Module**: Quản lý Hóa đơn, Thanh toán, Commission
   - **Party Module**: Quản lý Khách thuê, Nhân viên, Người dùng Banking
   - **Maintenance Module**: Quản lý Tickets, Meters, Meter Readings
3. Mỗi module hiển thị:
   - Tên module và mô tả
   - Trạng thái truy cập (có quyền/không có quyền)
   - Danh sách capabilities trong module
   - Trạng thái từng capability

#### 2.2. Bảng điều khiển Modules

Bảng điều khiển cho phép Nhân viên chọn/bỏ chọn các module để hiển thị trên bảng điều khiển:

1. Click **Module Cài đặt** hoặc icon module trên bảng điều khiển
2. Hệ thống hiển thị danh sách modules:
   - **Tổng quan (Tổng quan)**: Thống kê tổng quan
   - **Doanh thu (Revenue)**: Thống kê doanh thu
   - **Khách hàng (Customers)**: Thống kê khách hàng
   - **Bất động sản (Bất động sản)**: Thống kê bất động sản
   - **Môi giới (Agents)**: Thống kê môi giới
   - **Hợp đồng (Hợp đồng)**: Thống kê hợp đồng
3. Click để chọn/bỏ chọn module
4. Click **Lưu** để lưu
5. Bảng điều khiển tự động refresh và hiển thị modules đã chọn

**Lưu ý**: 
- Mỗi module có thể được bật/tắt độc lập
- Cấu hình được lưu cho người dùng hiện tại
- Có thể thay đổi bất cứ lúc nào
- Môi giới chỉ thấy modules và dữ liệu được Quản lý cấp quyền (qua capabilities)

### 3. Xem Tổng quan (Tổng quan Module)

Module **Tổng quan** hiển thị các thống kê chính:

- **Tổng Bất động sản**: Tổng số bất động sản
- **Tổng Phòng**: Tổng số phòng/căn
- **Occupied Phòng**: Số phòng đã cho thuê
- **Vacant Phòng**: Số phòng trống
- **Tổng Hợp đồng thuê**: Tổng số hợp đồng
- **Hoạt động Hợp đồng thuê**: Số hợp đồng đang hoạt động
- **Tổng Khách thuê**: Tổng số người thuê
- **Tổng Revenue**: Tổng doanh thu
- **Outstanding Hóa đơn**: Số hóa đơn chưa thanh toán
- **Tổng Số tiền Đến hạn**: Tổng số tiền còn nợ

### 4. Xem Doanh thu (Revenue Module)

Module **Doanh thu** hiển thị:

- **Revenue Chart**: Biểu đồ doanh thu theo thời gian (line chart)
- **Revenue by Period**: Doanh thu theo kỳ (hôm nay/tuần/tháng/năm)
- **Revenue by Bất động sản**: Doanh thu theo bất động sản
- **Revenue by Phòng**: Doanh thu theo phòng
- **Top Performing Bất động sản**: Top bất động sản có doanh thu cao nhất
- **Thanh toán Methods Distribution**: Phân bố phương thức thanh toán

### 5. Xem Khách hàng (Customers Module)

Module **Khách hàng** hiển thị:

- **Tổng Customers**: Tổng số khách hàng (Khách thuê + Leads)
- **Hoạt động Khách thuê**: Số người thuê hoạt động
- **New Leads**: Số lead mới
- **Leads by Trạng thái**: Phân bố lead theo trạng thái
- **Conversion Rate**: Tỷ lệ chuyển đổi (Lead → Hợp đồng thuê)
- **Customer Acquisition**: Biểu đồ khách hàng mới theo thời gian

### 6. Xem Bất động sản (Bất động sản Module)

Module **Bất động sản** hiển thị:

- **Tổng Bất động sản**: Tổng số bất động sản
- **Bất động sản by Loại**: Phân bố theo loại
- **Bất động sản by Trạng thái**: Phân bố theo trạng thái
- **Occupancy Rate**: Tỷ lệ lấp đầy (Occupied Phòng / Tổng Phòng)
- **Top Bất động sản**: Top bất động sản có nhiều đơn vị nhất
- **Bất động sản Map**: Bản đồ vị trí bất động sản (nếu có Google Maps API)

### 7. Xem Môi giới (Agents Module)

Module **Môi giới** hiển thị:

- **Tổng Agents**: Tổng số môi giới
- **Hoạt động Agents**: Số môi giới hoạt động
- **Top Performers**: Top môi giới có hiệu suất tốt nhất
- **Môi giới Performance**: Hiệu suất từng môi giới (Hợp đồng thuê, Revenue, Commission)
- **Môi giới Activities**: Hoạt động môi giới (Viewings, Leads, Hợp đồng thuê)

### 8. Xem Hợp đồng (Hợp đồng Module)

Module **Hợp đồng** hiển thị:

- **Tổng Hợp đồng thuê**: Tổng số hợp đồng
- **Hoạt động Hợp đồng thuê**: Số hợp đồng đang hoạt động
- **Expiring Soon**: Số hợp đồng sắp hết hạn (trong 30 ngày)
- **Terminated Hợp đồng thuê**: Số hợp đồng đã chấm dứt
- **Hợp đồng thuê Duration**: Thời gian thuê trung bình
- **Hợp đồng thuê Trạng thái Distribution**: Phân bố theo trạng thái

### 9. Lọc dữ liệu theo Kỳ (Period Lọc)

Bảng điều khiển hỗ trợ lọc dữ liệu theo kỳ:

1. Click dropdown **Period** ở header
2. Chọn kỳ:
   - **Hôm nay (Today)**: Dữ liệu hôm nay
   - **Tuần này (This Week)**: Dữ liệu tuần này
   - **Tháng này (This Month)**: Dữ liệu tháng này
   - **Năm này (This Year)**: Dữ liệu năm này
   - **Tùy chọn (Tùy chỉnh Range)**: Chọn khoảng thời gian tùy ý
3. Bảng điều khiển tự động refresh và hiển thị dữ liệu theo kỳ đã chọn

**Lưu ý**: 
- Lọc áp dụng cho tất cả modules
- Có thể thay đổi lọc bất cứ lúc nào

### 10. Auto Refresh

Bảng điều khiển tự động refresh dữ liệu mỗi 5 phút:

- Dữ liệu được refresh tự động
- Có thể refresh thủ công bằng cách click **Refresh** hoặc reload trang
- Cache được sử dụng để tối ưu performance (cache 5 phút)

### 11. Xuất dữ liệu

1. Click **Xuất** hoặc **Xuất Excel**
2. Chọn modules muốn xuất:
   - Tổng quan
   - Doanh thu
   - Khách hàng
   - Bất động sản
   - Môi giới
   - Hợp đồng
3. Click **Xuất**
4. Hệ thống tạo file Excel với dữ liệu đã chọn
5. Hệ thống tải file về máy

**Lưu ý**: 
- File Excel được format với UTF-8 BOM để tương thích Excel
- File chứa dữ liệu theo lọc period đã chọn

### 12. Clear Cache

1. Click **Clear Cache** hoặc icon cache
2. Hệ thống xóa cache bảng điều khiển
3. Bảng điều khiển refresh và hiển thị dữ liệu mới nhất

**Lưu ý**: 
- Clear cache khi cần dữ liệu real-thời gian mới nhất
- Cache được tự động xóa sau 5 phút

## Ràng buộc và điều kiện

### Data Scope

- Bảng điều khiển chỉ hiển thị dữ liệu của **tổ chức** hiện tại
- Quản lý không thể thấy dữ liệu của tổ chức khác
- Dữ liệu được lọc theo organization_id

### Cache Strategy

- Bảng điều khiển sử dụng cache để tối ưu performance
- Cache key: `dashboard_data_org_{organization_id}`
- Cache duration: 5 phút
- Có thể clear cache thủ công

### Module System

- Mỗi module có thể được bật/tắt độc lập
- Cấu hình được lưu cho người dùng hiện tại
- Module cài đặt được lưu trong session hoặc database

## Trạng thái và Workflow

### Bảng điều khiển Workflow

```
Login → Redirect to Dashboard → Load Modules → Display Data → Auto Refresh (5 min) → Export (if needed)
```

### Module Selection Flow

```
Module Settings → Select/Deselect Modules → Save → Refresh Dashboard → Display Selected Modules
```/Deselect Modules → Lưu → Refresh Bảng điều khiển → Display Selected Modules
```

## Ví dụ

### Ví dụ 1: Xem Bảng điều khiển với tất cả Modules

**Kịch bản:** Quản lý muốn xem tổng quan đầy đủ

**Các bước:**
1. Đăng nhập với tài khoản Nhân viên
2. Hệ thống redirect đến bảng điều khiển
3. Tất cả modules được hiển thị:
   - Tổng quan: 10 bất động sản, 50 phòng, 40 occupied, 10 vacant
   - Revenue: 500,000,000 VND (tháng này)
   - Customers: 50 khách thuê, 20 leads
   - Bất động sản: 10 bất động sản, 80% occupancy rate
   - Agents: 5 agents, top performer: Môi giới A
   - Hợp đồng: 40 hoạt động hợp đồng thuê, 3 expiring soon

### Ví dụ 2: Tùy chỉnh Modules

**Kịch bản:** Quản lý chỉ muốn xem Tổng quan và Revenue

**Các bước:**
1. Truy cập bảng điều khiển
2. Click **Module Cài đặt**
3. Bỏ chọn: Customers, Bất động sản, Agents, Hợp đồng
4. Chỉ chọn: Tổng quan, Revenue
5. Click **Lưu**
6. Bảng điều khiển chỉ hiển thị Tổng quan và Revenue modules

### Ví dụ 3: Xuất dữ liệu

**Kịch bản:** Quản lý muốn xuất dữ liệu tháng này

**Các bước:**
1. Truy cập bảng điều khiển
2. Chọn Period: **This Month**
3. Click **Xuất**
4. Chọn modules: Tổng quan, Revenue, Hợp đồng
5. Click **Xuất**
6. Hệ thống tạo file Excel: `dashboard_export_2025-01.xlsx`
7. Tải file về máy

## Lưu ý

1. **Module System**
   - Tùy chỉnh modules để phù hợp với nhu cầu
   - Bỏ chọn modules không cần thiết để tăng tốc độ tải

2. **Cache Performance**
   - Cache giúp tăng tốc độ tải trang
   - Clear cache khi cần dữ liệu mới nhất

3. **Period Lọc**
   - Sử dụng lọc để xem dữ liệu theo kỳ cụ thể
   - Tùy chỉnh range cho phép xem dữ liệu bất kỳ khoảng thời gian nào

4. **Xuất dữ liệu**
   - Xuất để lưu trữ hoặc phân tích offline
   - Chọn modules cần thiết để giảm kích thước file

5. **Auto Refresh**
   - Dữ liệu được refresh tự động mỗi 5 phút
   - Có thể refresh thủ công nếu cần

## Troubleshooting

### Bảng điều khiển không hiển thị dữ liệu

1. Kiểm tra tổ chức có dữ liệu không
2. Clear cache và refresh trang
3. Kiểm tra kết nối database
4. Kiểm tra người dùng có tổ chức membership không
5. Liên hệ hỗ trợ nếu vẫn không hiển thị

### Module không hiển thị

1. Kiểm tra module có được chọn trong cài đặt không
2. Clear cache và refresh trang
3. Kiểm tra module có data không
4. Liên hệ hỗ trợ nếu vẫn không hiển thị

### Xuất lỗi

1. Kiểm tra kết nối mạng
2. Thử xuất lại với ít modules hơn
3. Kiểm tra browser có chặn download không
4. Thử browser khác
5. Liên hệ hỗ trợ nếu vẫn lỗi

### Performance chậm

1. Bỏ chọn các modules không cần thiết
2. Clear cache
3. Sử dụng period lọc để giảm dữ liệu
4. Kiểm tra kết nối mạng
5. Liên hệ hỗ trợ nếu vẫn chậm

---

**Lưu ý**: Bảng điều khiển cung cấp cái nhìn tổng quan về hoạt động của tổ chức. Sử dụng module system để tùy chỉnh xem phù hợp với nhu cầu.

**Cập nhật**: 2025-11-11  
**Phiên bản**: 2.1  
**Lưu ý**: 
- Bảng điều khiển đã được unified cho cả Quản lý và Môi giới. Phân quyền hiển thị dựa trên role và capabilities.
- Bảng điều khiển sử dụng ERP Modules System để tổ chức các chức năng.
- Môi giới chỉ thấy dữ liệu được Quản lý cấp quyền (qua capabilities và ERP modules).

