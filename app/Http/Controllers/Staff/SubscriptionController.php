<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\Organization;
use App\Services\Subscription\PlanChangeService;
use App\Services\Subscription\SubscriptionService;
use App\Services\ImageService;
use App\Traits\ChecksCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    use ChecksCapabilities;
    
    protected $subscriptionService;
    protected $imageService;
    protected PlanChangeService $planChangeService;

    public function __construct(
        SubscriptionService $subscriptionService,
        ImageService $imageService,
        PlanChangeService $planChangeService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->imageService = $imageService;
        $this->planChangeService = $planChangeService;
    }

    /**
     * Hiển thị danh sách các gói subscription để đăng ký
     */
    public function index()
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Bạn cần tham gia một tổ chức trước khi đăng ký gói dịch vụ.');
        }

        $organization = Organization::find($organizationId);
        
        // Lấy subscription active và kiểm tra xem có valid không
        $activeSubscription = $organization->activeSubscription;
        $hasValidSubscription = false;
        
        if ($activeSubscription) {
            // Kiểm tra subscription có thực sự valid không
            $hasValidSubscription = $activeSubscription->isValid();
            
            // Load plan relationship nếu chưa có
            if (!$activeSubscription->relationLoaded('plan')) {
                $activeSubscription->load('plan');
            }
        }
        
        // Lấy các gói đang active với features
        $plans = SubscriptionPlan::active()
            ->with('features')
            ->orderBy('sort_order')
            ->get();

        // Lấy plan_id của subscription hiện tại (nếu có)
        $currentPlanId = $hasValidSubscription && $activeSubscription ? $activeSubscription->plan_id : null;

        return view('staff.subscriptions.index', compact('plans', 'organization', 'activeSubscription', 'hasValidSubscription', 'currentPlanId'));
    }

    /**
     * Hiển thị form đăng ký gói subscription
     */
    public function register(SubscriptionPlan $subscriptionPlan)
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.subscriptions.index')
                ->with('error', 'Bạn cần tham gia một tổ chức trước khi đăng ký gói dịch vụ.');
        }

        $organization = Organization::find($organizationId);
        
        // Lấy subscription active hiện tại (nếu có) để hiển thị thông tin
        $activeSubscription = $organization->activeSubscription;
        
        // Load plan relationship cho activeSubscription nếu có
        if ($activeSubscription && !$activeSubscription->relationLoaded('plan')) {
            $activeSubscription->load('plan');
        }
        
        // Load features nếu chưa có
        if (!$subscriptionPlan->relationLoaded('features')) {
            $subscriptionPlan->load('features');
        }

        return view('staff.subscriptions.register', compact('subscriptionPlan', 'organization', 'activeSubscription'));
    }

    /**
     * Xử lý đăng ký subscription và tạo invoice
     *
     * Đổi gói (giá khác):
     * - Nâng cấp (đắt hơn): đổi ngay — hủy gói cũ, tạo suspended + hóa đơn (có prorate theo thời gian còn lại).
     * - Hạ cấp (rẻ hơn): giữ gói hiện tại đến hết chu kỳ — lưu pending_downgrade, scheduler command sẽ áp dụng.
     */
    public function store(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();

        if (!$organizationId) {
            return redirect()->route('staff.subscriptions.index')
                ->with('error', 'Bạn cần tham gia một tổ chức trước khi đăng ký gói dịch vụ.');
        }

        $request->validate([
            'payment_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:sepay,manual',
        ]);

        $organization = Organization::find($organizationId);

        $activeSubscription = $organization->activeSubscription;
        if ($activeSubscription) {
            $activeSubscription->loadMissing('plan');
        }

        // Xác định subscription hiện hành để nhận diện đổi gói chính xác hơn.
        // activeSubscription chỉ bao gồm trial/active còn valid; ở các case pending (suspended)
        // vẫn cần coi là "đang có gói hiện hành" để không bị chặn bởi canUseTrial().
        $currentSubscription = $activeSubscription
            ?: OrganizationSubscription::where('organization_id', $organizationId)
                ->whereIn('status', ['trial', 'active', 'suspended'])
                ->latest('id')
                ->first();

        if ($currentSubscription) {
            $currentSubscription->loadMissing('plan');
        }

        $isPlanChange = $currentSubscription
            && (int) $currentSubscription->plan_id !== (int) $subscriptionPlan->id;

        // Không chặn đăng ký/chuyển gói nếu đã hết quyền trial.
        // Khi không đủ điều kiện trial, vẫn cho tạo hóa đơn thanh toán, chỉ bỏ ưu đãi trial.
        $canUseTrial = $organization->canUseTrial();

        try {
            DB::beginTransaction();

            if ($isPlanChange) {
                $oldPlan = $currentSubscription->plan;
                if (!$oldPlan) {
                    throw new \RuntimeException('Không tìm thấy thông tin gói hiện tại.');
                }

                $changeKind = $this->planChangeService->classifyChange(
                    $oldPlan,
                    $currentSubscription->payment_cycle,
                    $subscriptionPlan,
                    $request->payment_cycle
                );

                if ($changeKind === 'downgrade') {
                    if (!$currentSubscription->current_period_end) {
                        DB::rollBack();
                        return redirect()->back()
                            ->with('error', 'Gói hiện tại không có ngày hết chu kỳ — không thể đặt lịch hạ gói. Liên hệ quản trị.')
                            ->withInput();
                    }

                    $metadata = $currentSubscription->metadata ?? [];
                    $metadata['pending_downgrade'] = [
                        'target_plan_id' => $subscriptionPlan->id,
                        'target_payment_cycle' => $request->payment_cycle,
                        'target_payment_gateway' => $request->payment_method,
                        'scheduled_at' => $currentSubscription->current_period_end->toIso8601String(),
                    ];
                    $currentSubscription->update(['metadata' => $metadata]);

                    DB::commit();

                    $when = $currentSubscription->current_period_end->format('d/m/Y H:i');

                    return redirect()->route('staff.subscriptions.index')
                        ->with('success', "Đã đặt lịch hạ xuống gói «{$subscriptionPlan->name}». Gói hiện tại vẫn hiệu lực đến {$when}.");
                }

                // Nâng cấp / cùng mức giá: đổi ngay + prorate
                $organization->markTrialUsed();

                $invoicePayload = $this->planChangeService->buildUpgradeInvoice(
                    $currentSubscription,
                    $oldPlan,
                    $currentSubscription->payment_cycle,
                    $subscriptionPlan,
                    $request->payment_cycle
                );
                $amount = $invoicePayload['amount'];
                $invoiceMetadata = $invoicePayload['metadata'];

                $registrationDate = now();

                if ($amount <= 0) {
                    $periodStart = $registrationDate->copy();
                    $periodEnd = null;
                    if ($currentSubscription->current_period_end && $currentSubscription->current_period_end->isFuture()) {
                        $periodEnd = $currentSubscription->current_period_end->copy();
                    } else {
                        if ((int) ($subscriptionPlan->trial_days ?? 0) === 0) {
                            $periodEnd = null;
                        } elseif ($request->payment_cycle === 'yearly') {
                            $periodEnd = $periodStart->copy()->addYear();
                        } else {
                            $periodEnd = $periodStart->copy()->addDays(30);
                        }
                    }

                    $newActive = OrganizationSubscription::create([
                        'organization_id' => $organizationId,
                        'plan_id' => $subscriptionPlan->id,
                        'status' => 'active',
                        'payment_cycle' => $request->payment_cycle,
                        'payment_gateway' => $request->payment_method,
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodEnd,
                        'auto_renew' => false,
                    ]);

                    $this->cancelOtherSubscriptions($organizationId, $newActive->id);

                    DB::commit();

                    return redirect()->route('staff.subscriptions.index')
                        ->with('success', 'Đã nâng cấp gói (số tiền prorate đủ bù — không phát sinh hóa đơn). Gói cũ đã kết thúc.');
                }

                $trialDays = (int) ($subscriptionPlan->trial_days ?? 0);
                if ($trialDays > 0 && !$canUseTrial) {
                    // Không còn trial: đặt mốc tại thời điểm đăng ký để không phát sinh thời gian dùng thử.
                    $trialEndsAt = $registrationDate->copy();
                } else {
                    $trialEndsAt = $trialDays > 0 ? $registrationDate->copy()->addDays($trialDays) : null;
                }

                $subscription = OrganizationSubscription::create([
                    'organization_id' => $organizationId,
                    'plan_id' => $subscriptionPlan->id,
                    'status' => 'suspended',
                    'payment_cycle' => $request->payment_cycle,
                    'payment_gateway' => $request->payment_method,
                    'current_period_start' => $registrationDate,
                    'current_period_end' => $trialEndsAt,
                    'auto_renew' => false,
                ]);

                $invoiceNumber = 'SUB' . date('Ymd') . str_pad((string) $subscription->id, 4, '0', STR_PAD_LEFT);
                $invoice = SubscriptionInvoice::create([
                    'organization_subscription_id' => $subscription->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $amount,
                    'currency' => $subscriptionPlan->currency ?? 'VND',
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                    'payment_method' => $request->payment_method,
                    'metadata' => $invoiceMetadata,
                ]);

                $this->cancelOtherSubscriptions($organizationId, $subscription->id);

                DB::commit();

                $successMessage = 'Đã tạo hóa đơn nâng cấp (đã trừ prorate theo thời gian còn lại). Gói cũ đã kết thúc — vui lòng thanh toán để kích hoạt gói mới.';

                if ($request->payment_method === 'sepay') {
                    return redirect()->route('staff.subscriptions.payment', $invoice)
                        ->with('success', $successMessage);
                }

                return redirect()->route('staff.subscriptions.invoices.show', $invoice)
                    ->with('success', $successMessage . ' Liên hệ admin nếu chọn thanh toán thủ công.');
            }

            // Đăng ký lần đầu / cùng gói: giữ luồng cũ
            $amount = $subscriptionPlan->getPrice($request->payment_cycle);
            $registrationDate = now();
            $trialDays = (int) ($subscriptionPlan->trial_days ?? 0);
            if ($trialDays > 0 && !$canUseTrial) {
                // Không còn trial: vẫn cho mua gói nhưng không thêm ngày trial.
                $trialEndsAt = $registrationDate->copy();
            } else {
                $trialEndsAt = $trialDays > 0 ? $registrationDate->copy()->addDays($trialDays) : null;
            }

            $organization->markTrialUsed();

            $subscription = OrganizationSubscription::create([
                'organization_id' => $organizationId,
                'plan_id' => $subscriptionPlan->id,
                'status' => 'suspended',
                'payment_cycle' => $request->payment_cycle,
                'payment_gateway' => $request->payment_method,
                'current_period_start' => $registrationDate,
                'current_period_end' => $trialEndsAt,
                'auto_renew' => false,
            ]);

            $invoiceNumber = 'SUB' . date('Ymd') . str_pad((string) $subscription->id, 4, '0', STR_PAD_LEFT);
            $invoice = SubscriptionInvoice::create([
                'organization_subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'currency' => $subscriptionPlan->currency ?? 'VND',
                'status' => 'pending',
                'due_date' => now()->addDays(7),
                'payment_method' => $request->payment_method,
            ]);

            DB::commit();

            $successMessage = 'Đã tạo hóa đơn thanh toán. Vui lòng thực hiện thanh toán.';

            if ($request->payment_method === 'sepay') {
                return redirect()->route('staff.subscriptions.payment', $invoice)
                    ->with('success', $successMessage);
            }

            return redirect()->route('staff.subscriptions.invoices.show', $invoice)
                ->with('success', 'Đã tạo hóa đơn. Vui lòng liên hệ admin để xác nhận thanh toán.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating subscription: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Hủy mọi subscription khác (trial / active / suspended) trừ bản ghi vừa tạo.
     */
    private function cancelOtherSubscriptions(int $organizationId, int $exceptSubscriptionId): void
    {
        $rows = OrganizationSubscription::where('organization_id', $organizationId)
            ->where('id', '!=', $exceptSubscriptionId)
            ->whereIn('status', ['trial', 'active', 'suspended'])
            ->get();

        foreach ($rows as $old) {
            $old->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }
    }

    /**
     * Hiển thị trang thanh toán sepay
     */
    public function payment(SubscriptionInvoice $invoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Load invoice relationships
        $invoice->load(['subscription.plan', 'subscription.organization']);
        
        // Admin có quyền truy cập tất cả
        $isAdmin = $user->userRoles()->where('key_code', 'admin')->exists();
        
        if ($isAdmin) {
            // Admin có thể truy cập bất kỳ invoice nào
        } else {
            // Lấy organization ID từ invoice
            $invoiceOrganizationId = $invoice->subscription->organization_id ?? null;
            
            if (!$invoiceOrganizationId) {
                return redirect()->route('staff.subscriptions.index')
                    ->with('error', 'Hóa đơn không hợp lệ.');
            }
            
            // Kiểm tra user có thuộc organization của invoice không (kể cả inactive)
            // Manager và Agent đều có thể truy cập hóa đơn của organization họ thuộc về
            $userBelongsToOrg = \App\Models\OrganizationUser::where('user_id', $user->id)
                ->where('organization_id', $invoiceOrganizationId)
                ->exists();
            
            if (!$userBelongsToOrg) {
                return redirect()->route('staff.subscriptions.index')
                    ->with('error', 'Bạn không có quyền truy cập hóa đơn này.');
            }
        }

        if ($invoice->status !== 'pending') {
            return redirect()->route('staff.subscriptions.invoices.show', $invoice)
                ->with('error', 'Hóa đơn này đã được xử lý.');
        }

        // Lấy thông tin bank config từ env (thông tin ngân hàng của tổ chức SaaS)
        $bankConfig = [
            'bank_name' => config('services.sepay.bank_name', 'TPBank'),
            'account_number' => config('services.sepay.account_number', '46166378666'),
            'account_name' => config('services.sepay.account_name', 'Le Xuan Thanh Quan'),
            'branch' => config('services.sepay.branch', 'Chi nhánh Hà Nội'),
        ];
        
        // Tạo nội dung chuyển khoản
        $content = "THANH TOAN HOA DON SUB {$invoice->invoice_number}";
        
        // Tạo QR code URL
        $qrParams = [
            'acc' => $bankConfig['account_number'],
            'bank' => $bankConfig['bank_name'],
            'amount' => $invoice->amount,
            'des' => $content
        ];
        $qrUrl = 'https://qr.sepay.vn/img?' . http_build_query($qrParams);
        
        return view('staff.subscriptions.payment', compact('invoice', 'bankConfig', 'qrUrl', 'content'));
    }

    /**
     * Process cash payment for subscription invoice
     */
    public function processCashPayment(Request $request, SubscriptionInvoice $invoice)
    {
        try {
            // Validate document if provided
            $request->validate([
                'document' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
            ]);

            // Check if invoice can be paid
            if ($invoice->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hóa đơn này đã được xử lý.'
                ], 400);
            }

            DB::beginTransaction();

            // Handle document upload
            $documentToAttach = null;
            if ($request->hasFile('document')) {
                try {
                    $file = $request->file('document');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'subscriptions', 'subscription-payments');
                    
                    $documentToAttach = [
                        'path' => $uploadedFile['original'],
                        'filename' => $uploadedFile['filename'],
                        'original_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                    ];
                    
                    Log::info('Document prepared for subscription payment', [
                        'invoice_id' => $invoice->id,
                        'path' => $uploadedFile['original'],
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error preparing document for subscription payment: ' . $e->getMessage(), [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getTraceAsString(),
                    ]);
                    $documentToAttach = null;
                }
            }

            // Update invoice metadata
            $metadata = $invoice->metadata ?? [];
            $metadata['payment_method'] = 'cash';
            $metadata['confirmed_by'] = Auth::id();
            $metadata['confirmed_at'] = now()->toIso8601String();
            
            if ($documentToAttach) {
                $metadata['document_path'] = $documentToAttach['path'];
            }

            $invoice->update([
                'metadata' => $metadata,
            ]);

            // Handle document upload - save as document attachment
            if ($documentToAttach) {
                try {
                    $document = \App\Models\Document::create([
                        'owner_type' => SubscriptionInvoice::class,
                        'owner_id' => $invoice->id,
                        'file_url' => $documentToAttach['path'], // Đã là normalized path
                        'file_name' => $documentToAttach['original_name'],
                        'mime_type' => $documentToAttach['mime_type'],
                        'file_size' => $documentToAttach['file_size'],
                        'document_type' => 'document',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);
                    
                    // Kiểm tra document đã được tạo thành công
                    if (!$document || !$document->id) {
                        throw new \Exception('Document was created but has no ID');
                    }
                    
                    Log::info('Subscription payment document created successfully', [
                        'invoice_id' => $invoice->id,
                        'document_id' => $document->id,
                        'file_path' => $documentToAttach['path'],
                    ]);
                } catch (\Exception $e) {
                    // Xóa file đã upload nếu tạo document thất bại
                    $fullPath = public_path('storage/' . $documentToAttach['path']);
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    
                    Log::error('Error creating document for subscription payment', [
                        'invoice_id' => $invoice->id,
                        'file_path' => $documentToAttach['path'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    // Throw lại exception để rollback transaction
                    throw $e;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán tiền mặt đã được lưu thành công',
                'invoice_id' => $invoice->id,
                'payment_id' => $invoice->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in SubscriptionController@processCashPayment: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý thanh toán tiền mặt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Sepay payment for subscription invoice
     */
    public function processSepayPayment(Request $request, SubscriptionInvoice $invoice)
    {
        try {
            // Validate document if provided
            $request->validate([
                'document' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
            ]);

            // Check if invoice can be paid
            if ($invoice->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hóa đơn này đã được xử lý.'
                ], 400);
            }

            DB::beginTransaction();

            // Get bank config from env (SaaS organization bank account)
            $bankConfig = [
                'bank_name' => config('services.sepay.bank_name', 'TPBank'),
                'account_number' => config('services.sepay.account_number', '46166378666'),
                'account_name' => config('services.sepay.account_name', 'Le Xuan Thanh Quan'),
                'branch' => config('services.sepay.branch', 'Chi nhánh Hà Nội'),
            ];

            // Handle document upload
            $documentToAttach = null;
            if ($request->hasFile('document')) {
                try {
                    $file = $request->file('document');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'subscriptions', 'subscription-payments');
                    
                    $documentToAttach = [
                        'path' => $uploadedFile['original'],
                        'filename' => $uploadedFile['filename'],
                        'original_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                    ];
                    
                    Log::info('Document prepared for subscription payment', [
                        'invoice_id' => $invoice->id,
                        'path' => $uploadedFile['original'],
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error preparing document for subscription payment: ' . $e->getMessage(), [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getTraceAsString(),
                    ]);
                    $documentToAttach = null;
                }
            }

            // Update invoice metadata
            $metadata = $invoice->metadata ?? [];
            $metadata['payment_method'] = 'sepay';
            $metadata['confirmed_by'] = Auth::id();
            $metadata['confirmed_at'] = now()->toIso8601String();
            $metadata['bank_info'] = $bankConfig;
            
            if ($documentToAttach) {
                $metadata['document_path'] = $documentToAttach['path'];
            }

            $invoice->update([
                'metadata' => $metadata,
            ]);

            // Handle document upload - save as document attachment
            if ($documentToAttach) {
                try {
                    $document = \App\Models\Document::create([
                        'owner_type' => SubscriptionInvoice::class,
                        'owner_id' => $invoice->id,
                        'file_url' => $documentToAttach['path'], // Đã là normalized path
                        'file_name' => $documentToAttach['original_name'],
                        'mime_type' => $documentToAttach['mime_type'],
                        'file_size' => $documentToAttach['file_size'],
                        'document_type' => 'document',
                        'uploaded_by' => Auth::id(),
                        'created_at' => now(),
                    ]);
                    
                    // Kiểm tra document đã được tạo thành công
                    if (!$document || !$document->id) {
                        throw new \Exception('Document was created but has no ID');
                    }
                    
                    Log::info('Subscription payment document created successfully', [
                        'invoice_id' => $invoice->id,
                        'document_id' => $document->id,
                        'file_path' => $documentToAttach['path'],
                    ]);
                } catch (\Exception $e) {
                    // Xóa file đã upload nếu tạo document thất bại
                    $fullPath = public_path('storage/' . $documentToAttach['path']);
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    
                    Log::error('Error creating document for subscription payment', [
                        'invoice_id' => $invoice->id,
                        'file_path' => $documentToAttach['path'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    // Throw lại exception để rollback transaction
                    throw $e;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thông tin thanh toán chuyển khoản đã được lưu thành công',
                'invoice_id' => $invoice->id,
                'payment_id' => $invoice->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in SubscriptionController@processSepayPayment: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý thanh toán chuyển khoản: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý xác nhận thanh toán (sau khi chuyển khoản)
     */
    public function confirmPayment(Request $request, SubscriptionInvoice $invoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Load invoice relationships
        $invoice->load(['subscription.plan', 'subscription.organization']);
        
        // Admin có quyền truy cập tất cả
        $isAdmin = $user->userRoles()->where('key_code', 'admin')->exists();
        
        if ($isAdmin) {
            // Admin có thể truy cập bất kỳ invoice nào
        } else {
            // Lấy organization ID từ invoice
            $invoiceOrganizationId = $invoice->subscription->organization_id ?? null;
            
            if (!$invoiceOrganizationId) {
                return redirect()->route('staff.subscriptions.index')
                    ->with('error', 'Hóa đơn không hợp lệ.');
            }
            
            // Kiểm tra user có thuộc organization của invoice không (kể cả inactive)
            // Manager và Agent đều có thể truy cập hóa đơn của organization họ thuộc về
            $userBelongsToOrg = \App\Models\OrganizationUser::where('user_id', $user->id)
                ->where('organization_id', $invoiceOrganizationId)
                ->exists();
            
            if (!$userBelongsToOrg) {
                return redirect()->route('staff.subscriptions.index')
                    ->with('error', 'Bạn không có quyền truy cập hóa đơn này.');
            }
        }

        if ($invoice->status !== 'pending') {
            return redirect()->route('staff.subscriptions.invoices.show', $invoice)
                ->with('error', 'Hóa đơn này đã được xử lý.');
        }

        $request->validate([
            'transaction_id' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'payment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
        ]);

        try {
            DB::beginTransaction();
            
            // Xử lý upload ảnh thanh toán
            $document = null;
            if ($request->hasFile('payment_image')) {
                try {
                    $file = $request->file('payment_image');
                    
                    // Sử dụng ImageService để upload
                    $uploadedFile = $this->imageService->uploadFile($file, 'subscriptions', 'subscription-payments');
                    
                    // Lưu document - throw exception nếu thất bại để rollback
                    $document = \App\Models\Document::create([
                        'owner_type' => SubscriptionInvoice::class,
                        'owner_id' => $invoice->id,
                        'file_url' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_size' => $uploadedFile['size'],
                        'document_type' => 'image',
                        'is_primary' => false,
                        'uploaded_by' => $user->id,
                        'created_at' => now(),
                    ]);
                    
                    // Kiểm tra document đã được tạo thành công
                    if (!$document || !$document->id) {
                        throw new \Exception('Document was created but has no ID');
                    }
                    
                    Log::info('Subscription payment image uploaded successfully', [
                        'invoice_id' => $invoice->id,
                        'document_id' => $document->id,
                        'file_path' => $uploadedFile['original'],
                        'file_name' => $uploadedFile['original_name'],
                    ]);
                } catch (\Exception $e) {
                    // Xóa file đã upload nếu tạo document thất bại
                    if (isset($uploadedFile) && isset($uploadedFile['original'])) {
                        $fullPath = public_path('storage/' . $uploadedFile['original']);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                    
                    Log::error('Error creating document for payment image', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    // Throw lại exception để rollback transaction
                    throw $e;
                }
            }
            
            // Cập nhật invoice với thông tin giao dịch
            $metadata = [
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'note' => $request->note,
            ];
            
            if ($document) {
                $metadata['payment_proof_document_id'] = $document->id;
            }
            
            $invoice->update([
                'gateway_transaction_id' => $request->transaction_id,
                'metadata' => $metadata,
            ]);
            
            // Staff chỉ lưu thông tin thanh toán, không tự động đánh dấu paid
            // Chỉ Super Admin hoặc Sepay webhook mới có quyền đánh dấu paid và kích hoạt subscription
            DB::commit();
            
            return redirect()->route('staff.subscriptions.invoices.show', $invoice)
                ->with('success', 'Đã lưu thông tin thanh toán. Vui lòng chờ Super Admin xác nhận thanh toán để kích hoạt gói dịch vụ.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirming payment: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Danh sách invoices của tổ chức
     */
    public function invoices()
    {
        $user = Auth::user();
        $organizationId = $this->getCurrentOrganizationId();
        
        if (!$organizationId) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Bạn cần tham gia một tổ chức.');
        }

        $invoices = SubscriptionInvoice::whereHas('subscription', function($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->with(['subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('staff.subscriptions.invoices.index', compact('invoices'));
    }

    /**
     * Chi tiết invoice
     */
    public function showInvoice(SubscriptionInvoice $invoice)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Load invoice relationships
        $invoice->load(['subscription.plan', 'subscription.organization']);
        
        // Admin có quyền truy cập tất cả
        $isAdmin = $user->userRoles()->where('key_code', 'admin')->exists();
        
        if ($isAdmin) {
            // Admin có thể truy cập bất kỳ invoice nào
        } else {
            // Lấy organization ID từ invoice
            $invoiceOrganizationId = $invoice->subscription->organization_id ?? null;
            
            if (!$invoiceOrganizationId) {
                return redirect()->route('staff.subscriptions.index')
                    ->with('error', 'Hóa đơn không hợp lệ.');
            }
            
            // Kiểm tra user có thuộc organization của invoice không (kể cả inactive)
            // Manager và Agent đều có thể truy cập hóa đơn của organization họ thuộc về
            $userBelongsToOrg = \App\Models\OrganizationUser::where('user_id', $user->id)
                ->where('organization_id', $invoiceOrganizationId)
                ->exists();
            
            if (!$userBelongsToOrg) {
                return redirect()->route('staff.subscriptions.index')
                    ->with('error', 'Bạn không có quyền truy cập hóa đơn này.');
            }
        }

        return view('staff.subscriptions.invoices.show', compact('invoice'));
    }

}

