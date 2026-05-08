# HƯỚNG DẪN CẬP NHẬT TÀI LIỆU USER-GUIDES

## Tổng quan

Tài liệu này hướng dẫn cách cập nhật các file trong `docs/user-guides/`/user-guides/` để phản ánh hệ thống hiện tại sau khi unified routes Quản lý và Môi giới.

## Thay đổi chính

### 1. Routes đã được unified

- **Trước**: Quản lý sử dụng `/manager/*`, Môi giới sử dụng `/agent/*`
- **Sau**: Cả Quản lý và Môi giới đều sử dụng `/staff/*`
- **Phân quyền**: Dựa trên role và capabilities (relational system)

### 2. Laravel version

- **Hiện tại**: Laravel 12
- **PHP**: 8.2+

### 3. Capability System

- **Trước**: JSON-based capabilities
- **Sau**: Relational capabilities (bảng `capabilities`, `organization_user_capabilities`)
- **Audit trail**: Track ai grant/revoke và khi nào

## Quy tắc cập nhật

### 1. Routes

**Tìm và thay thế:**
- `/manager/` → `/staff/`
- `/agent/` → `/staff/`

**Lưu ý:**
- Giữ nguyên `/tenant/*` và `/superadmin/*`
- Thêm ghi chú về capability check cho Môi giới

### 2. Xác thực

**Cập nhật redirect:**
- `Manager → /manager/dashboard`/manager/dashboard` → `Manager → /staff/dashboard`
- `Agent → /agent/dashboard`/agent/dashboard` → `Agent → /staff/dashboard`

### 3. Capability References

**Thêm thông tin về capabilities:**
- Môi giới chỉ có thể truy cập nếu có capability
- Quản lý có wildcard (`*`) = tất cả quyền
- Tham chiếu đến [Capability Management Guide](../../CAPABILITY_MANAGEMENT_GUIDE.md)

### 4. HTMX

**Cập nhật nếu có đề cập đến filters:**
- Hệ thống đã sử dụng HTMX cho filters
- Tham chiếu đến [HTMX System Guide](../../HTMX_SYSTEM_WIDE_GUIDE.md)

## Checklist cập nhật mỗi file

- [ ] Thay thế tất cả routes `/manager/` và `/agent/` thành `/staff/`
- [ ] Cập nhật redirect sau đăng nhập
- [ ] Thêm ghi chú về capability check (nếu là Môi giới)
- [ ] Cập nhật ngày cập nhật cuối cùng
- [ ] Thêm link đến [Routes Mapping](./common/00-routes-mapping.md) nếu cần
- [ ] Kiểm tra các link nội bộ vẫn đúng

## Ví dụ cập nhật

### Trước:
```markdown
## Route
`/manager/properties`

## Các bước
1. Truy cập `/manager/properties`
2. Click "Create Property"
````/manager/properties`

## Các bước
1. Truy cập `/manager/properties`
2. Click "Tạo Bất động sản"
```

### Sau:
```markdown
## Route
`/staff/properties` (unified staff route)

## Quyền truy cập
- **Manager**: Full access
- **Agent**: Cần capability `property.create`

## Các bước
1. Truy cập `/staff/properties`
2. Click "Create Property"
````/staff/properties` (unified nhân viên route)

## Quyền truy cập
- **Quản lý**: Full truy cập
- **Môi giới**: Cần capability `property.create`

## Các bước
1. Truy cập `/staff/properties`
2. Click "Tạo Bất động sản"
```

## Files đã cập nhật

- ✅ `README.md` - Đã cập nhật với thông tin routes mới
- ✅ `common/00-routes-mapping.md`/00-routes-mapping.md` - File mới, mapping routes
- ✅ `manager/01-authentication.md`/01-authentication.md` - Đã cập nhật routes
- ✅ `agent/01-authentication.md`/01-authentication.md` - Đã cập nhật routes
- ✅ `manager/02-dashboard.md`/02-dashboard.md` - Đã cập nhật routes
- ✅ `workflows/01-lead-to-lease.md`/01-lead-to-lease.md` - Đã cập nhật routes và links

## Files cần cập nhật

### Quản lý (35 files)
- [ ] `03-properties.md` đến `35-settings.md`

### Môi giới (27 files)
- [ ] `02-dashboard.md` đến `27-notifications.md`

### Workflows (7 files)
- [x] `01-lead-to-lease.md` - ✅ Đã cập nhật
- [ ] `02-lease-to-payment.md` đến `07-meter-reading-to-invoice.md`

### Common (4 files)
- [ ] `01-glossary.md` - Cần thêm routes mới
- [x] `00-routes-mapping.md` - ✅ File mới
- [ ] `02-constraints.md`, `03-error-handling.md`, `04-faq.md`

## Script hỗ trợ (Tùy chọn)

Có thể sử dụng script để tự động thay thế:

```bash
# Thay thế routes trong tất cả file .md
find docs/user-guides -name "*.md" -type f -exec sed -i 's|/manager/|/staff/|g' {} \;
find docs/user-guides -name "*.md" -type f -exec sed -i 's|/agent/|/staff/|g' {} \;

# Lưu ý: Cần kiểm tra lại sau khi chạy script
```/user-guides -name "*.md" -loại f -exec sed -i 's|/manager/|/staff/|g' {} \;
find docs/user-guides -name "*.md" -loại f -exec sed -i 's|/agent/|/staff/|g' {} \;

# Lưu ý: Cần kiểm tra lại sau khi chạy script
```

## Lưu ý quan trọng

1. **Không thay thế trong code examples**: Nếu có code examples với routes cũ, có thể giữ nguyên hoặc thêm comment
2. **Kiểm tra links nội bộ**: Đảm bảo các link `[text](../path)`/path)` vẫn đúng
3. **Giữ nguyên context**: Không thay đổi logic nghiệp vụ, chỉ cập nhật routes
4. **Thêm ghi chú phân quyền**: Nếu Môi giới có hạn chế, ghi rõ

## Tài liệu tham khảo

- [Routes Mapping](./common/00-routes-mapping.md) - Mapping đầy đủ routes
- [Capability Management Guide](../../CAPABILITY_MANAGEMENT_GUIDE.md) - Hệ thống phân quyền
- [HTMX System Guide](../../HTMX_SYSTEM_WIDE_GUIDE.md) - HTMX implementation
- [SRS.md](../../SRS.md) - Đặc tả hệ thống

---

**Cập nhật**: 2025-11-03  
**Phiên bản**: 2.0

