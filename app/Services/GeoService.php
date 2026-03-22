<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Service: GeoService
 * 
 * MỤC ĐÍCH:
 * Service quản lý dữ liệu địa lý (tỉnh/thành phố, phường/xã) từ các view unified - cung cấp danh sách địa danh
 * đã được hợp nhất từ nhiều nguồn dữ liệu khác nhau
 * 
 * LUỒNG XỬ LÝ CHÍNH:
 * 1. listUnifiedProvinces(): Lấy danh sách tỉnh/thành phố từ view unified → Trả về mảng các tỉnh/thành phố
 * 2. listUnifiedWards(): Lấy danh sách phường/xã từ view unified → Trả về mảng các phường/xã
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - View: vw_geo_provinces_unified (bảng tỉnh/thành phố đã hợp nhất) - Lấy danh sách tỉnh/thành phố
 * - View: vw_geo_wards_unified (bảng phường/xã đã hợp nhất) - Lấy danh sách phường/xã
 * 
 * DỮ LIỆU GHI VÀO:
 * - Không có (chỉ đọc)
 * 
 * LƯU Ý:
 * - Dữ liệu được lấy từ view unified (đã hợp nhất từ nhiều nguồn)
 * - Kết quả được sắp xếp theo tên (name) tăng dần
 * - Mỗi bản ghi có thông tin source để biết nguồn gốc dữ liệu
 */
class GeoService
{
    /**
     * Lấy danh sách tỉnh/thành phố đã hợp nhất
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách tất cả tỉnh/thành phố từ view unified, đã được hợp nhất từ nhiều nguồn dữ liệu
     * 
     * INPUT:
     * - Database: vw_geo_provinces_unified (view)
     * 
     * OUTPUT:
     * - array: Mảng các tỉnh/thành phố với các trường code, name, source
     * 
     * LUỒNG XỬ LÝ:
     * 1. Query từ view vw_geo_provinces_unified
     * 2. Select các trường: code, name, source
     * 3. Sắp xếp theo tên (name) tăng dần
     * 4. Convert collection thành array và trả về
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - View vw_geo_provinces_unified: Lấy danh sách tỉnh/thành phố đã hợp nhất
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function listUnifiedProvinces(): array
    {
        return DB::table('vw_geo_provinces_unified') // Query từ view tỉnh/thành phố đã hợp nhất
            ->select(['code', 'name', 'source']) // Chọn các trường: mã, tên, nguồn dữ liệu
            ->orderBy('name') // Sắp xếp theo tên tăng dần → Dễ tìm kiếm và hiển thị
            ->get() // Lấy tất cả kết quả
            ->toArray(); // Convert collection thành array → Trả về dạng mảng
    }

    /**
     * Lấy danh sách phường/xã đã hợp nhất
     * 
     * MỤC ĐÍCH:
     * Lấy danh sách tất cả phường/xã từ view unified, đã được hợp nhất từ nhiều nguồn dữ liệu
     * 
     * INPUT:
     * - Database: vw_geo_wards_unified (view)
     * 
     * OUTPUT:
     * - array: Mảng các phường/xã với các trường code, name, district_code, source
     * 
     * LUỒNG XỬ LÝ:
     * 1. Query từ view vw_geo_wards_unified
     * 2. Select các trường: code, name, district_code, source
     * 3. Sắp xếp theo tên (name) tăng dần
     * 4. Convert collection thành array và trả về
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - View vw_geo_wards_unified: Lấy danh sách phường/xã đã hợp nhất
     * 
     * DỮ LIỆU GHI VÀO:
     * - Không có (chỉ đọc)
     */
    public function listUnifiedWards(): array
    {
        return DB::table('vw_geo_wards_unified') // Query từ view phường/xã đã hợp nhất
            ->select(['code', 'name', 'district_code', 'source']) // Chọn các trường: mã, tên, mã quận/huyện, nguồn dữ liệu
            ->orderBy('name') // Sắp xếp theo tên tăng dần → Dễ tìm kiếm và hiển thị
            ->get() // Lấy tất cả kết quả
            ->toArray(); // Convert collection thành array → Trả về dạng mảng
    }
}


