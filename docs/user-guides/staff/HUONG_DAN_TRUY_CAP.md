# HƯỚNG DẪN TRUY CẬP TÀI LIỆU HƯỚNG DẪN STAFF

## Cách truy cập

### 1. Qua trình duyệt web

Truy cập các URL sau:

- **Trang chủ tài liệu Nhân viên**: `/docs/staff` hoặc `/docs/staff/README`
- **Tài liệu cụ thể**: `/docs/staff/{tên-file}`

Ví dụ:
- `/docs/staff` - Xem trang tổng quan (README.md)
- `/docs/staff/01-authentication` - Xem hướng dẫn đăng nhập
- `/docs/staff/05-leases` - Xem hướng dẫn quản lý hợp đồng

### 2. Qua menu trong hệ thống

Nếu có menu "Tài liệu" hoặc "Hướng dẫn" trong hệ thống, click vào đó và chọn "Hướng dẫn Nhân viên".

### 3. Thêm link vào index/header

Bạn có thể thêm link vào trang index hoặc header:

```html
<a href="/docs/staff" class="nav-link">
    <i class="fas fa-book"></i>
    Hướng dẫn sử dụng
</a>
```/docs/staff" class="nav-link">
    <i class="fas fa-book"></i>
    Hướng dẫn sử dụng
</a>
```

Hoặc trong Laravel Blade:

```blade
<a href="{{ route('docs.show', ['section' => 'staff']) }}" class="nav-link">
    <i class="fas fa-book"></i>
    Hướng dẫn sử dụng
</a>
```/i>
    Hướng dẫn sử dụng
</a>
```

## Tính năng

- ✅ Sidebar navigation với tất cả tài liệu
- ✅ Tìm kiếm trong tài liệu
- ✅ Hiển thị markdown đẹp mắt
- ✅ Responsive design
- ✅ Syntax highlighting cho code blocks

## Lưu ý

- Tài liệu được đọc từ thư mục `docs/user-guides/staff/`/user-guides/staff/`
- File README.md sẽ xuất hiện ở đầu danh sách trong sidebar
- Tất cả file markdown (.md) sẽ được hiển thị tự động

