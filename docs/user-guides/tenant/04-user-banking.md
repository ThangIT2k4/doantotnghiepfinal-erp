# QUẢN LÝ THÔNG TIN NGÂN HÀNG - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê quản lý thông tin tài khoản ngân hàng của mình, dùng cho thanh toán hóa đơn và nhận hoàn tiền thông qua hệ thống SePay.

**SePay** là hệ thống tự động hóa thanh toán giúp chia sẻ biến động số dư ngân hàng và tự xác thực thanh toán qua chuyển khoản, không tốn phí cổng thanh toán.

## Quyền truy cập

- **Khách thuê**: Có quyền quản lý thông tin ngân hàng của chính mình

**Route**: `/tenant/user-banking`

## Các khái niệm

### Ngân hàng SePay (SePay Bank)
- Là danh sách các ngân hàng được tích hợp với hệ thống SePay
- Hệ thống hỗ trợ hơn 50 ngân hàng tại Việt Nam
- Các ngân hàng phổ biến: Vietcombank, VietinBank, BIDV, Techcombank, TPBank, MBBank, ACB, VPBank, MSB, và nhiều ngân hàng khác

### Số tài khoản (Account Number)
- Số tài khoản ngân hàng của bạn tại ngân hàng đã chọn
- Thường là dãy số từ 8-15 chữ số
- Phải khớp chính xác với số tài khoản thực tế

### Tên chủ tài khoản (Account Holder Name)
- Tên đầy đủ của chủ tài khoản ngân hàng
- Phải khớp với tên trên tài khoản ngân hàng thực tế
- Nên viết IN HOA, không dấu để tránh lỗi

### Chi nhánh (Branch Name)
- Tên chi nhánh ngân hàng nơi bạn mở tài khoản
- Ví dụ: "Chi nhánh Hà Nội", "Chi nhánh TP. Hồ Chí Minh"

### Mã chi nhánh (Branch Code)
- Mã số định danh của chi nhánh ngân hàng
- Thường là 3-5 chữ số

### Mã Swift (Swift Code)
- Mã định danh quốc tế của ngân hàng (nếu có)
- Thường dùng cho giao dịch quốc tế
- Không bắt buộc cho giao dịch trong nước

## Các bước thực hiện

### 1. Xem Thông tin Ngân hàng

1. Truy cập **Thông tin Ngân hàng** (Người dùng Banking) từ menu Khách thuê
2. Hệ thống hiển thị thông tin ngân hàng hiện tại:
   - **Ngân hàng SePay**: Tên ngân hàng đã chọn
   - **Số tài khoản**: Số tài khoản của bạn
   - **Tên chủ tài khoản**: Tên đầy đủ trên tài khoản
   - **Tên chi nhánh**: Chi nhánh mở tài khoản
   - **Mã chi nhánh**: Mã số chi nhánh
   - **Mã Swift**: Mã Swift (nếu có)
   - **Ghi chú**: Ghi chú thêm (nếu có)
   - **Trạng thái**: Trạng thái tài khoản

### 2. Thêm Thông tin Ngân hàng

1. Click **Tạo mới** (Tạo) hoặc **+ Thêm mới** (nếu chưa có thông tin)
2. Điền thông tin:
   - **Ngân hàng SePay** (bắt buộc): Chọn ngân hàng từ danh sách dropdown
     - Danh sách hiển thị tất cả ngân hàng được SePay hỗ trợ
     - Có thể tìm kiếm bằng cách gõ tên ngân hàng
   - **Số tài khoản** (bắt buộc): Nhập số tài khoản ngân hàng của bạn
     - Chỉ nhập số, không có ký tự đặc biệt
     - Đảm bảo chính xác với số tài khoản thực tế
   - **Tên chủ tài khoản** (bắt buộc): Nhập tên đầy đủ trên tài khoản
     - Nên viết IN HOA, không dấu
     - Ví dụ: "NGUYEN VAN A"
   - **Tên chi nhánh** (tùy chọn): Nhập tên chi nhánh
     - Ví dụ: "Chi nhánh Hà Nội"
   - **Mã chi nhánh** (tùy chọn): Nhập mã chi nhánh
     - Thường là 3-5 chữ số
   - **Mã Swift** (tùy chọn): Nhập mã Swift nếu có
     - Thường dùng cho giao dịch quốc tế
   - **Ghi chú** (tùy chọn): Ghi chú thêm về tài khoản
     - Ví dụ: "Tài khoản thanh toán chính"
3. Click **Lưu** (Lưu)
4. Hệ thống lưu thông tin và hiển thị thông báo thành công

### 3. Cập nhật Thông tin Ngân hàng

1. Truy cập thông tin ngân hàng hiện tại
2. Click **Chỉnh sửa** (Chỉnh sửa)
3. Cập nhật thông tin cần thay đổi:
   - Có thể thay đổi bất kỳ trường nào
   - Đảm bảo thông tin mới chính xác
4. Click **Lưu** (Lưu)
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý khi cập nhật:**
- Nếu thay đổi số tài khoản, đảm bảo số mới chính xác
- Nếu thay đổi tên chủ tài khoản, phải khớp với tài khoản thực tế
- Thông tin cũ sẽ được ghi đè hoàn toàn

### 4. Xóa Thông tin Ngân hàng

1. Truy cập thông tin ngân hàng cần xóa
2. Click **Xóa** (Xóa)
3. Xác nhận xóa trong hộp thoại
4. Hệ thống xóa thông tin (soft xóa - xóa mềm)

**Lưu ý**: 
- Có thể xóa thông tin ngân hàng nếu không còn sử dụng
- Thông tin được xóa mềm (soft xóa), có thể khôi phục sau nếu cần
- Nếu đang sử dụng tài khoản này để thanh toán, nên cập nhật thông tin mới trước khi xóa

## Ràng buộc và điều kiện

### Quy tắc xác thực (Validation Rules)

- **Ngân hàng SePay (SePay Bank)**: 
  - Bắt buộc phải chọn
  - Phải tồn tại trong danh sách ngân hàng được SePay hỗ trợ
  - Không thể chọn ngân hàng không có trong danh sách
  
- **Số tài khoản (Account Number)**: 
  - Bắt buộc phải nhập
  - Không được để trống
  - Chỉ chứa số, không có ký tự đặc biệt hoặc khoảng trắng
  - Độ dài thường từ 8-15 chữ số (tùy ngân hàng)
  
- **Tên chủ tài khoản (Account Holder Name)**: 
  - Bắt buộc phải nhập
  - Không được để trống
  - Nên viết IN HOA, không dấu để tránh lỗi
  - Phải khớp với tên trên tài khoản ngân hàng thực tế
  
- **Tên chi nhánh (Branch Name)**: 
  - Tùy chọn (không bắt buộc)
  - Nếu nhập thì không được để trống
  - Nên nhập đầy đủ tên chi nhánh
  
- **Mã chi nhánh (Branch Code)**: 
  - Tùy chọn (không bắt buộc)
  - Nếu nhập thì phải là số
  - Thường là 3-5 chữ số
  
- **Mã Swift (Swift Code)**: 
  - Tùy chọn (không bắt buộc)
  - Nếu nhập thì phải đúng format Swift Code
  - Format: 8-11 ký tự chữ và số

### Quy tắc nghiệp vụ (Business Rules)

1. **Một Khách thuê có thể có một thông tin ngân hàng**
   - Hiện tại hệ thống cho phép mỗi Khách thuê có một thông tin ngân hàng
   - Có thể cập nhật thông tin ngân hàng bất cứ lúc nào
   - Khi cập nhật, thông tin cũ sẽ được thay thế hoàn toàn

2. **Tích hợp SePay (SePay Integration)**
   - Thông tin ngân hàng được dùng cho thanh toán qua hệ thống SePay
   - Thông tin phải khớp chính xác với tài khoản ngân hàng thực tế
   - SePay sẽ tự động xác thực thanh toán qua chuyển khoản
   - Không tốn phí cổng thanh toán khi sử dụng SePay

3. **Phương thức thanh toán (Thanh toán Methods)**
   - Thông tin ngân hàng dùng cho chuyển khoản ngân hàng (bank transfer)
   - Dùng cho thanh toán hóa đơn thuê nhà
   - Dùng cho nhận hoàn tiền cọc hoặc refund
   - Hệ thống tự động tạo QR code thanh toán từ thông tin ngân hàng

## Cách sử dụng

### Thanh toán Hóa đơn

Khi thanh toán hóa đơn thuê nhà qua hệ thống SePay:

1. Truy cập trang thanh toán hóa đơn
2. Chọn phương thức thanh toán: **Chuyển khoản qua SePay**
3. Hệ thống tự động:
   - Lấy thông tin ngân hàng của bạn đã lưu
   - Tạo QR code thanh toán với thông tin ngân hàng của chủ nhà
   - Hiển thị thông tin chuyển khoản (số tài khoản, tên chủ tài khoản, số tiền)
4. Bạn thực hiện chuyển khoản từ tài khoản ngân hàng của mình
5. SePay tự động xác thực và cập nhật trạng thái thanh toán

**Lưu ý:**
- Thông tin ngân hàng của bạn chỉ dùng để xác định danh tính khi chuyển khoản
- Thông tin ngân hàng nhận tiền là của chủ nhà (tổ chức quản lý)
- SePay sẽ tự động nhận diện giao dịch chuyển khoản

### Nhận Hoàn Tiền

Khi nhận hoàn tiền cọc hoặc hoàn tiền khác:

1. Chủ nhà/tổ chức quản lý thực hiện hoàn tiền
2. Hệ thống sử dụng thông tin ngân hàng của bạn để:
   - Chuyển khoản vào tài khoản ngân hàng đã đăng ký
   - Tự động cập nhật trạng thái hoàn tiền
3. Bạn nhận được thông báo khi hoàn tiền thành công

**Lưu ý:**
- Đảm bảo thông tin ngân hàng chính xác để nhận hoàn tiền đúng
- Kiểm tra số tài khoản và tên chủ tài khoản trước khi lưu
- Thời gian nhận hoàn tiền phụ thuộc vào ngân hàng (thường 1-3 ngày làm việc)

## Ví dụ minh họa

### Ví dụ 1: Thêm Thông tin Ngân hàng lần đầu

**Tình huống:** Bạn chưa có thông tin ngân hàng trong hệ thống và muốn thêm để thanh toán hóa đơn.

**Thông tin ngân hàng của bạn:**
- **Ngân hàng SePay**: Vietcombank (VCB)
- **Số tài khoản**: 1234567890
- **Tên chủ tài khoản**: NGUYEN VAN A
- **Tên chi nhánh**: Chi nhánh Hà Nội
- **Mã chi nhánh**: 001
- **Ghi chú**: Tài khoản thanh toán chính

**Các bước thực hiện:**
1. Đăng nhập vào hệ thống với tài khoản Khách thuê
2. Truy cập menu **Thông tin Ngân hàng** (Người dùng Banking)
3. Click nút **Tạo mới** (Tạo) hoặc **+ Thêm mới**
4. Trong form:
   - Chọn **Ngân hàng SePay**: Tìm và chọn "Vietcombank" từ dropdown
   - Nhập **Số tài khoản**: `1234567890`
   - Nhập **Tên chủ tài khoản**: `NGUYEN VAN A` (IN HOA, không dấu)
   - Nhập **Tên chi nhánh**: `Chi nhánh Hà Nội`
   - Nhập **Mã chi nhánh**: `001`
   - Nhập **Ghi chú**: `Tài khoản thanh toán chính`
5. Click nút **Lưu** (Lưu)
6. Hệ thống hiển thị thông báo: "Thông tin ngân hàng đã được lưu thành công!"
7. Thông tin ngân hàng đã được lưu và sẵn sàng sử dụng

### Ví dụ 2: Cập nhật Thông tin Ngân hàng

**Tình huống:** Bạn đã thay đổi số tài khoản ngân hàng và cần cập nhật trong hệ thống.

**Các bước thực hiện:**
1. Truy cập **Thông tin Ngân hàng** từ menu Khách thuê
2. Hệ thống hiển thị thông tin ngân hàng hiện tại
3. Click nút **Chỉnh sửa** (Chỉnh sửa)
4. Trong form chỉnh sửa:
   - Cập nhật **Số tài khoản**: Từ `1234567890` thành `0987654321`
   - Các thông tin khác giữ nguyên hoặc cập nhật nếu cần
5. Click nút **Lưu** (Lưu)
6. Hệ thống hiển thị thông báo: "Thông tin ngân hàng đã được cập nhật thành công!"
7. Thông tin mới đã được lưu và sẽ được sử dụng cho các giao dịch tiếp theo

### Ví dụ 3: Thanh toán Hóa đơn sử dụng thông tin ngân hàng

**Tình huống:** Bạn cần thanh toán hóa đơn thuê nhà tháng 11/2025.

**Các bước thực hiện:**
1. Truy cập **Hóa đơn** (Hóa đơn) từ menu Khách thuê
2. Chọn hóa đơn cần thanh toán
3. Click **Thanh toán** (Pay)
4. Chọn phương thức: **Chuyển khoản qua SePay**
5. Hệ thống hiển thị:
   - Thông tin ngân hàng nhận tiền (của chủ nhà)
   - Số tiền cần thanh toán
   - QR code để quét thanh toán
6. Bạn mở ứng dụng ngân hàng trên điện thoại
7. Quét QR code hoặc chuyển khoản thủ công với thông tin hiển thị
8. SePay tự động xác thực và cập nhật trạng thái thanh toán
9. Hóa đơn được đánh dấu "Đã thanh toán"

## Lưu ý quan trọng

1. **Bảo mật Thông tin**
   - Không chia sẻ thông tin tài khoản ngân hàng với người khác
   - Đảm bảo thông tin chính xác để tránh lỗi thanh toán
   - Chỉ nhập thông tin trên trang chính thức của hệ thống
   - Không cung cấp thông tin qua email hoặc tin nhắn không chính thức

2. **Tích hợp SePay**
   - Thông tin ngân hàng phải khớp chính xác với tài khoản thực tế
   - Kiểm tra lại thông tin trước khi lưu
   - SePay sẽ tự động xác thực thanh toán, không cần xác nhận thủ công
   - Không tốn phí khi sử dụng SePay để thanh toán

3. **Độ chính xác thông tin**
   - Số tài khoản phải chính xác 100%, sai một số sẽ không thể thanh toán
   - Tên chủ tài khoản phải khớp với tên trên tài khoản ngân hàng
   - Nên viết tên IN HOA, không dấu để tránh lỗi encoding
   - Kiểm tra lại thông tin trước khi lưu

4. **Định dạng số tài khoản**
   - Đảm bảo số tài khoản chính xác, không có khoảng trắng
   - Chỉ nhập số, không có ký tự đặc biệt
   - Độ dài số tài khoản tùy thuộc vào từng ngân hàng (thường 8-15 chữ số)
   - Kiểm tra lại số tài khoản từ thẻ ATM hoặc sổ tiết kiệm

5. **Cập nhật thông tin**
   - Nếu thay đổi tài khoản ngân hàng, cần cập nhật ngay trong hệ thống
   - Thông tin cũ sẽ không còn hiệu lực sau khi cập nhật
   - Nên cập nhật trước khi thanh toán hóa đơn để tránh lỗi

## Troubleshooting

### Không thể thêm thông tin ngân hàng

1. Kiểm tra tất cả các trường bắt buộc đã điền chưa
2. Kiểm tra Account Number có đúng format không
3. Kiểm tra SePay Bank có được chọn không
4. Liên hệ hỗ trợ nếu vẫn không thể thêm

### Thanh toán không thành công

1. Kiểm tra thông tin ngân hàng có chính xác không
2. Kiểm tra tài khoản có đủ tiền không
3. Kiểm tra kết nối SePay có ổn định không
4. Liên hệ hỗ trợ nếu vẫn lỗi

### Không nhận được hoàn tiền

1. Kiểm tra thông tin ngân hàng có chính xác không
2. Kiểm tra trạng thái refund có đã đã phê duyệt và đã thanh toán không
3. Kiểm tra kết nối SePay có ổn định không
4. Liên hệ hỗ trợ nếu vẫn không nhận được

---

**Lưu ý**: Thông tin ngân hàng là cần thiết để thanh toán hóa đơn và nhận hoàn tiền. Đảm bảo thông tin chính xác.

---

**Cập nhật**: 2025-11-13  
**Phiên bản**: 2.0  
**Tích hợp**: SePay Banking System

