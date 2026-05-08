<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\ErpModuleService;
use App\Services\CapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ErpModuleController extends Controller
{
    /**
     * Display ERP modules dashboard
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $user->organizations()->first()?->id;

        if (!$organizationId) {
            return redirect()->route('login')
                ->with('error', 'Bạn cần tham gia một tổ chức trước khi sử dụng hệ thống.');
        }

        // Get accessible modules
        $modules = ErpModuleService::getUserAccessibleModules($user->id, $organizationId);

        // Get all modules config for display
        $allModules = ErpModuleService::getModules();

        // Check user capabilities for each module
        $moduleCapabilities = [];
        foreach ($allModules as $moduleKey => $moduleConfig) {
            $moduleCapabilities[$moduleKey] = [
                'has_access' => ErpModuleService::userHasModuleAccess($user->id, $organizationId, $moduleKey),
                'capabilities' => [],
            ];

            // Check each capability in the module
            foreach ($moduleConfig['capabilities'] ?? [] as $capKey => $capName) {
                $moduleCapabilities[$moduleKey]['capabilities'][$capKey] = 
                    CapabilityService::userHas($user->id, $organizationId, $capKey);
            }
        }

        return view('staff.erp-modules.index', compact('modules', 'allModules', 'moduleCapabilities'));
    }

    /**
     * Display a specific ERP module
     */
    public function show(string $moduleKey)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organizationId = $user->organizations()->first()?->id;

        if (!$organizationId) {
            return redirect()->route('login')
                ->with('error', 'Bạn cần tham gia một tổ chức trước khi sử dụng hệ thống.');
        }

        // Check module access
        if (!ErpModuleService::userHasModuleAccess($user->id, $organizationId, $moduleKey)) {
            abort(403, 'Bạn không có quyền truy cập module này.');
        }

        $module = ErpModuleService::getModule($moduleKey);

        if (!$module) {
            abort(404, 'Module không tồn tại.');
        }

        // Get user capabilities for this module
        $capabilities = [];
        foreach ($module['capabilities'] ?? [] as $capKey => $capName) {
            $capabilities[$capKey] = [
                'name' => $capName,
                'has' => CapabilityService::userHas($user->id, $organizationId, $capKey),
            ];
        }

        return view('staff.erp-modules.show', compact('module', 'moduleKey', 'capabilities'));
    }
}


