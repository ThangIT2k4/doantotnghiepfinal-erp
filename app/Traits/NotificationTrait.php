<?php

namespace App\Traits;

use App\Helpers\ErrorHelper;
use Illuminate\Support\Facades\Log;

trait NotificationTrait
{
    /**
     * Show success notification
     */
    protected function notifySuccess($message, $title = 'Thành công!')
    {
        session()->flash('success', $message);
        return $this->jsonResponse(true, $message, $title);
    }

    /**
     * Show error notification
     */
    protected function notifyError($message, $title = 'Lỗi!')
    {
        session()->flash('error', $message);
        return $this->jsonResponse(false, $message, $title);
    }

    /**
     * Show warning notification
     */
    protected function notifyWarning($message, $title = 'Cảnh báo!')
    {
        session()->flash('warning', $message);
        return $this->jsonResponse(false, $message, $title);
    }

    /**
     * Show info notification
     */
    protected function notifyInfo($message, $title = 'Thông tin')
    {
        session()->flash('info', $message);
        return $this->jsonResponse(true, $message, $title);
    }

    /**
     * Create JSON response with notification data
     */
    protected function jsonResponse($success, $message, $title = null, $data = [], $redirect = null)
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($title) {
            $response['title'] = $title;
        }

        if (!empty($data)) {
            $response['data'] = $data;
        }

        if ($redirect) {
            $response['redirect'] = $redirect;
        }

        return response()->json($response);
    }

    /**
     * Handle exception and return error notification
     */
    protected function handleException(\Exception $e, $defaultMessage = 'Có lỗi xảy ra')
    {
        // Log chi tiết lỗi để debug (giữ nguyên để developer có thể debug)
        Log::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Sử dụng ErrorHelper để lấy thông báo an toàn, không lộ thông tin database
        $message = ErrorHelper::getSafeErrorMessage($e, $defaultMessage);
        return $this->notifyError($message);
    }

    /**
     * Show confirmation dialog
     */
    protected function confirmAction($message, $title = 'Xác nhận hành động', $type = 'warning')
    {
        return $this->jsonResponse(false, $message, $title, [
            'confirm' => true,
            'type' => $type
        ]);
    }
}
