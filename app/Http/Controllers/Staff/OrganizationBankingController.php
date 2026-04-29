<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\OrganizationBanking;
use App\Models\SepayBank;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrganizationBankingController extends Controller
{
    use ChecksCapabilities;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền truy cập cài đặt ngân hàng.');
        
        // Redirect to system-settings with organization-banking tab
        return redirect()->route('staff.system-settings.index')->with('active_tab', 'organization-banking');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền tạo tài khoản ngân hàng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        // Get supported banks from sepay_banks
        $sepayBanks = SepayBank::supported()->orderBy('name')->get();

        return view('staff.settings.organization-banking.create', [
            'sepayBanks' => $sepayBanks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền tạo tài khoản ngân hàng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'sepay_bank_id' => 'required|exists:sepay_banks,id',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'sepay_bank_id.required' => 'Vui lòng chọn ngân hàng.',
            'sepay_bank_id.exists' => 'Ngân hàng không hợp lệ.',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',
            'account_name.required' => 'Vui lòng nhập tên chủ tài khoản.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // If this is set as default, unset other defaults
            if ($request->is_default) {
                OrganizationBanking::where('organization_id', $organizationId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Create banking account (bank_name will be accessed via accessor from sepayBank)
            $bankingAccount = OrganizationBanking::create([
                'organization_id' => $organizationId,
                'sepay_bank_id' => $request->sepay_bank_id,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'branch' => $request->branch,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
                'is_default' => $request->has('is_default') ? (bool)$request->is_default : false,
                'notes' => $request->notes,
            ]);

            Log::info('Organization banking account created', [
                'organization_id' => $organizationId,
                'banking_account_id' => $bankingAccount->id,
                'created_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tài khoản ngân hàng đã được tạo thành công!',
                'redirect' => route('staff.organization-banking.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating organization banking account: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo tài khoản ngân hàng. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền xem tài khoản ngân hàng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thuộc tổ chức nào.'
                ], 403);
            }
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $bankingAccount = OrganizationBanking::where('organization_id', $organizationId)
            ->with('sepayBank')
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'account' => $bankingAccount
            ]);
        }

        return view('staff.settings.organization-banking.show', [
            'bankingAccount' => $bankingAccount
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền chỉnh sửa tài khoản ngân hàng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            abort(403, 'Bạn không thuộc tổ chức nào.');
        }

        $bankingAccount = OrganizationBanking::where('organization_id', $organizationId)
            ->findOrFail($id);

        // Get supported banks from sepay_banks
        $sepayBanks = SepayBank::supported()->orderBy('name')->get();

        return view('staff.settings.organization-banking.edit', [
            'bankingAccount' => $bankingAccount,
            'sepayBanks' => $sepayBanks
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền cập nhật tài khoản ngân hàng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $bankingAccount = OrganizationBanking::where('organization_id', $organizationId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'sepay_bank_id' => 'required|exists:sepay_banks,id',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'sepay_bank_id.required' => 'Vui lòng chọn ngân hàng.',
            'sepay_bank_id.exists' => 'Ngân hàng không hợp lệ.',
            'account_number.required' => 'Vui lòng nhập số tài khoản.',
            'account_name.required' => 'Vui lòng nhập tên chủ tài khoản.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // If this is set as default, unset other defaults
            if ($request->is_default && !$bankingAccount->is_default) {
                OrganizationBanking::where('organization_id', $organizationId)
                    ->where('id', '!=', $id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Update banking account (bank_name will be accessed via accessor from sepayBank)
            $bankingAccount->update([
                'sepay_bank_id' => $request->sepay_bank_id,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'branch' => $request->branch,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : $bankingAccount->is_active,
                'is_default' => $request->has('is_default') ? (bool)$request->is_default : $bankingAccount->is_default,
                'notes' => $request->notes,
            ]);

            Log::info('Organization banking account updated', [
                'organization_id' => $organizationId,
                'banking_account_id' => $bankingAccount->id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tài khoản ngân hàng đã được cập nhật thành công!',
                'redirect' => route('staff.organization-banking.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating organization banking account: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật tài khoản ngân hàng. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền xóa tài khoản ngân hàng.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $bankingAccount = OrganizationBanking::where('organization_id', $organizationId)
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            // Soft delete
            $bankingAccount->delete();

            Log::info('Organization banking account deleted', [
                'organization_id' => $organizationId,
                'banking_account_id' => $bankingAccount->id,
                'deleted_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tài khoản ngân hàng đã được xóa thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting organization banking account: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản ngân hàng. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Set as default banking account
     */
    public function setDefault($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check capability
        $this->requireCapability('billing.access', 'Bạn không có quyền đặt tài khoản mặc định.');
        
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc tổ chức nào.'
            ], 403);
        }

        $bankingAccount = OrganizationBanking::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            $bankingAccount->setAsDefault();

            Log::info('Organization banking account set as default', [
                'organization_id' => $organizationId,
                'banking_account_id' => $bankingAccount->id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tài khoản ngân hàng đã được đặt làm mặc định!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error setting default banking account: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt tài khoản mặc định. Vui lòng thử lại sau.'
            ], 500);
        }
    }
}
