<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Viewing;
use App\Models\Invoice;
use App\Models\BookingDeposit;
use Illuminate\Http\Request;

/**
 * Controller: ButtonPreviewController
 * 
 * MỤC ĐÍCH:
 * Controller đơn giản để hiển thị trang preview các button components.
 * Controller này được sử dụng để test và preview các button components trong hệ thống.
 * 
 * LUỒNG XỬ LÝ:
 * 1. index(): Hiển thị trang preview buttons
 *    - Lấy sample data từ các models (Viewing, Invoice, BookingDeposit)
 *    - Hiển thị view với sample data để preview buttons
 * 
 * ENDPOINTS:
 * - GET /staff/button-preview: Hiển thị trang preview buttons
 * 
 * DỮ LIỆU ĐỌC TỪ:
 * - Model: Viewing (bảng viewings) - Lấy bản ghi đầu tiên
 * - Model: Invoice (bảng invoices) - Lấy bản ghi đầu tiên
 * - Model: BookingDeposit (bảng booking_deposits) - Lấy bản ghi đầu tiên
 * 
 * DỮ LIỆU GHI VÀO:
 * - Không có (chỉ đọc để preview)
 * 
 * LƯU Ý:
 * - Controller này chỉ dùng cho development/testing
 * - Sample data có thể null - view sẽ xử lý gracefully
 * - Không có authentication/authorization check (có thể cần thêm)
 */
class ButtonPreviewController extends Controller
{
    /**
     * Hiển thị trang preview buttons
     * 
     * LUỒNG XỬ LÝ:
     * 1. Lấy sample data từ các models (Viewing, Invoice, BookingDeposit)
     * 2. Hiển thị view với sample data để preview buttons
     * 
     * DỮ LIỆU ĐỌC TỪ:
     * - Model: Viewing, Invoice, BookingDeposit (lấy bản ghi đầu tiên)
     * 
     * @return \Illuminate\View\View View preview buttons
     */
    public function index()
    {
        /**
         * Lấy sample data từ Viewing model
         * 
         * Viewing::first() - Lấy bản ghi đầu tiên từ bảng viewings
         *   - first() là method của Eloquent model
         *   - Trả về Viewing model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         *   - Không có điều kiện filter, lấy bất kỳ bản ghi nào
         * 
         * $viewing - Biến lưu Viewing model instance (hoặc null)
         *   - Sẽ được sử dụng trong view để preview buttons liên quan đến viewing
         *   - Nếu null, view sẽ xử lý gracefully
         */
        $viewing = Viewing::first();
        
        /**
         * Lấy sample data từ Invoice model
         * 
         * Invoice::first() - Lấy bản ghi đầu tiên từ bảng invoices
         *   - first() là method của Eloquent model
         *   - Trả về Invoice model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         *   - Không có điều kiện filter, lấy bất kỳ bản ghi nào
         * 
         * $invoice - Biến lưu Invoice model instance (hoặc null)
         *   - Sẽ được sử dụng trong view để preview buttons liên quan đến invoice
         *   - Nếu null, view sẽ xử lý gracefully
         */
        $invoice = Invoice::first();
        
        /**
         * Lấy sample data từ BookingDeposit model
         * 
         * BookingDeposit::first() - Lấy bản ghi đầu tiên từ bảng booking_deposits
         *   - first() là method của Eloquent model
         *   - Trả về BookingDeposit model instance nếu tìm thấy
         *   - Trả về null nếu không tìm thấy
         *   - Không có điều kiện filter, lấy bất kỳ bản ghi nào
         * 
         * $bookingDeposit - Biến lưu BookingDeposit model instance (hoặc null)
         *   - Sẽ được sử dụng trong view để preview buttons liên quan đến booking deposit
         *   - Nếu null, view sẽ xử lý gracefully
         */
        $bookingDeposit = BookingDeposit::first();

        /**
         * Hiển thị view preview buttons
         * 
         * view('staff.components.button-preview') - Tạo view response từ template
         *   - view() là helper function của Laravel
         *   - 'staff.components.button-preview' là path đến view file
         *   - Trả về View instance
         * 
         * compact('viewing', 'invoice', 'bookingDeposit') - Tạo array từ các biến
         *   - compact() là PHP built-in function
         *   - Tạo associative array: ['viewing' => $viewing, 'invoice' => $invoice, 'bookingDeposit' => $bookingDeposit]
         *   - Array này sẽ được truyền vào view
         * 
         * View sẽ hiển thị các button components với sample data để preview
         */
        return view('staff.components.button-preview', compact(
            'viewing',
            'invoice',
            'bookingDeposit'
        ));
    }
}

