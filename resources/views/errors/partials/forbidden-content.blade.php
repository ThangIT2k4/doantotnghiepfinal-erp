@php
    $message = $message ?? 'Bạn không có quyền truy cập trang này.';
    $plans = $plans ?? collect();
    $fallbackUrl = $fallbackUrl ?? (auth()->check() ? route('dashboard') : route('login'));
    $backUrl = $backUrl ?? $fallbackUrl;
    $isChatLocked = $isChatLocked ?? \Illuminate\Support\Str::contains(
        mb_strtolower($message),
        ['chat với ai', 'chat ai', 'enable_chat', 'tính năng chat']
    );
    $recommendedPlanId = null;
    if ($isChatLocked && $plans->isNotEmpty()) {
        foreach ($plans as $_p) {
            $_feat = ($_p->features ?? collect())->firstWhere('feature_key', 'enable_chat');
            if ($_feat && $_feat->getValue()) {
                $recommendedPlanId = $_p->id;
                break;
            }
        }
    }
    $canSubscribe = \Illuminate\Support\Facades\Route::has('staff.subscriptions.register');
@endphp

{{-- Hero: thông báo + nút --}}
<div class="relative mb-10 md:mb-14 rounded-3xl bg-white/90 backdrop-blur-md border-2 border-gray-200/90 shadow-xl p-6 md:p-8 overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-50/80 via-white to-accent-50/40"></div>
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
        <div class="flex-1 min-w-0">
            <p class="text-red-600 font-bold text-xs uppercase tracking-widest mb-2">403 Forbidden</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3" style="font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;">Truy cập bị từ chối</h1>
            <p class="text-gray-600 text-base leading-relaxed">{{ $message }}</p>
            @if($isChatLocked)
                <div class="mt-4 rounded-2xl border border-brand-200 bg-brand-50/80 px-4 py-3 text-sm text-brand-800">
                    <i class="fas fa-robot mr-2 text-brand-600"></i>
                    Các gói có tính năng <strong>Chat với AI</strong> trong mục dưới sẽ mở khóa tính năng này.
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-3 shrink-0">
            <a href="{{ $backUrl }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm text-white bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-arrow-left"></i>
                Quay lại trang trước
            </a>
            <a href="{{ $fallbackUrl }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm border-2 border-gray-200 bg-white/80 text-gray-800 hover:bg-gray-50 hover:border-gray-300 transition-all">
                <i class="fas fa-house"></i>
                Trang mặc định
            </a>
        </div>
    </div>
</div>

{{-- Pricing-style section (giống index #pricing) --}}
<section id="error403-pricing" class="py-12 md:py-16 bg-gradient-to-b from-brand-50/30 via-white to-accent-50/30 relative overflow-hidden rounded-3xl border border-white/60 shadow-lg">
    <div class="absolute inset-0 -z-10 pointer-events-none">
        <div class="absolute top-1/4 right-0 w-96 h-96 bg-brand-200 rounded-full blur-3xl opacity-20 animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 left-0 w-96 h-96 bg-accent-200 rounded-full blur-3xl opacity-20 animate-pulse-slow" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-brand-100 to-accent-100 rounded-full blur-3xl opacity-10"></div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <p class="text-brand-600 font-semibold text-sm uppercase tracking-wide mb-3">Gói đăng ký</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3" style="font-family: 'Space Grotesk', sans-serif;">Chọn gói phù hợp để tiếp tục</h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">So sánh giới hạn và tính năng — nâng cấp để sử dụng đầy đủ hệ thống.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($plans as $index => $plan)
                @php
                    $features = $plan->features ?? collect();
                    $chatFeat = $features->firstWhere('feature_key', 'enable_chat');
                    $hasChat = $chatFeat && $chatFeat->getValue();
                    $limitOrder = ['max_properties' => 1, 'max_units' => 2, 'max_users' => 3, 'max_leases' => 4];
                    $limitFeatures = $features->filter(fn ($f) => $f->isLimit())->sortBy(fn ($f) => $limitOrder[$f->feature_key] ?? 99);
                    $booleanFeatures = $features->filter(fn ($f) => $f->isBoolean() && $f->getValue())->sortBy('feature_name');
                    $recommended = $recommendedPlanId !== null && (int) $plan->id === (int) $recommendedPlanId;
                    $isPopular = $index === 1 && $plans->count() >= 3;
                    $cardHighlight = $isPopular || $recommended;
                @endphp
                <div>
                    <div class="relative p-6 rounded-3xl bg-white border-2 {{ $cardHighlight ? 'border-brand-500 shadow-xl' : 'border-gray-200 shadow-lg' }} hover-lift transition-all duration-300 h-full flex flex-col glass-card">
                        @if($recommended)
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
                                <span class="px-3 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-xs font-bold rounded-full shadow-lg">
                                    <i class="fas fa-unlock mr-1"></i>
                                    Gợi ý mở khóa
                                </span>
                            </div>
                        @elseif($isPopular)
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
                                <span class="px-3 py-1 bg-gradient-to-r from-brand-600 to-brand-700 text-white text-xs font-bold rounded-full shadow-lg">
                                    <i class="fas fa-star mr-1"></i>
                                    Phổ biến
                                </span>
                            </div>
                        @endif

                        <div class="text-center mb-5 {{ ($recommended || $isPopular) ? 'pt-2' : '' }}">
                            <h3 class="font-bold text-xl {{ $cardHighlight ? 'text-brand-600' : 'text-gray-800' }} mb-2">{{ $plan->name }}</h3>
                            <p class="text-xs text-gray-500 font-mono">{{ $plan->code }}</p>
                            <div class="mt-3">
                                @if((float) $plan->price_monthly > 0)
                                    <div class="flex items-baseline justify-center gap-2">
                                        <span class="text-3xl font-extrabold {{ $cardHighlight ? 'text-brand-600' : 'text-gray-800' }}">
                                            {{ number_format((float) $plan->price_monthly, 0, ',', '.') }}
                                        </span>
                                        <span class="text-gray-500 text-sm">{{ $plan->currency === 'VND' ? 'đ' : $plan->currency }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">/ tháng</p>
                                    @if((float) $plan->price_yearly > 0)
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ number_format((float) $plan->price_yearly, 0, ',', '.') }} {{ $plan->currency }}/năm
                                        </p>
                                    @endif
                                @else
                                    <div class="text-2xl font-bold text-gray-800">Miễn phí</div>
                                    <p class="text-xs text-gray-500 mt-1">Bắt đầu không phí</p>
                                @endif
                            </div>
                        </div>

                        @if($plan->description)
                            <p class="text-center text-gray-600 text-sm mb-5 pb-5 border-b border-gray-200">{{ $plan->description }}</p>
                        @endif

                        @if($limitFeatures->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                                    <i class="fas fa-chart-line text-brand-600"></i>
                                    Giới hạn
                                </h4>
                                <div class="space-y-2">
                                    @foreach($limitFeatures as $feature)
                                        @php
                                            $value = $feature->getValue();
                                            $key = $feature->feature_key;
                                            $iconMap = [
                                                'max_properties' => ['icon' => 'fa-building', 'color' => 'amber', 'bg' => 'from-amber-50 to-amber-100/50', 'border' => 'border-amber-200', 'text' => 'text-amber-600', 'badge' => 'bg-amber-500'],
                                                'max_units' => ['icon' => 'fa-home', 'color' => 'green', 'bg' => 'from-green-50 to-green-100/50', 'border' => 'border-green-200', 'text' => 'text-green-600', 'badge' => 'bg-green-500'],
                                                'max_users' => ['icon' => 'fa-users', 'color' => 'purple', 'bg' => 'from-purple-50 to-purple-100/50', 'border' => 'border-purple-200', 'text' => 'text-purple-600', 'badge' => 'bg-purple-500'],
                                                'max_leases' => ['icon' => 'fa-file-contract', 'color' => 'blue', 'bg' => 'from-blue-50 to-blue-100/50', 'border' => 'border-blue-200', 'text' => 'text-blue-600', 'badge' => 'bg-blue-500'],
                                            ];
                                            $style = $iconMap[$key] ?? ['icon' => 'fa-circle', 'color' => 'gray', 'bg' => 'from-gray-50 to-gray-100/50', 'border' => 'border-gray-200', 'text' => 'text-gray-600', 'badge' => 'bg-gray-500'];
                                        @endphp
                                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-gradient-to-r {{ $style['bg'] }} border {{ $style['border'] }}">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg {{ $style['badge'] }} flex items-center justify-center flex-shrink-0">
                                                    <i class="fas {{ $style['icon'] }} text-white text-xs"></i>
                                                </div>
                                                <span class="text-gray-700 font-medium text-xs">{{ $feature->feature_name }}</span>
                                            </div>
                                            <div class="text-right">
                                                @if($value == -1)
                                                    <span class="infinity-pricing {{ $style['color'] }}" style="font-size: 1.25rem;">∞</span>
                                                @elseif($value > 0)
                                                    <span class="text-base font-bold {{ $style['text'] }}">{{ number_format((int) $value, 0, ',', '.') }}</span>
                                                @else
                                                    <span class="text-sm font-semibold text-gray-400">0</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($booleanFeatures->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                                    <i class="fas fa-star text-amber-500"></i>
                                    Tính năng nâng cao
                                </h4>
                                <div class="space-y-2">
                                    @foreach($booleanFeatures as $feature)
                                        @php
                                            $key = $feature->feature_key;
                                            $featureIconMap = [
                                                'enable_reports' => 'fa-chart-bar',
                                                'enable_webhooks' => 'fa-plug',
                                                'enable_advanced_permissions' => 'fa-user-shield',
                                                'enable_data_export' => 'fa-file-excel',
                                                'enable_chat' => 'fa-robot',
                                                'enable_priority_support' => 'fa-headset',
                                            ];
                                            $icon = $featureIconMap[$key] ?? 'fa-check-circle';
                                        @endphp
                                        <div class="flex items-center gap-2 p-2.5 rounded-lg bg-gradient-to-r from-indigo-50 to-indigo-100/50 border border-indigo-200">
                                            <div class="w-7 h-7 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
                                                <i class="fas {{ $icon }} text-white text-xs"></i>
                                            </div>
                                            <span class="text-gray-700 font-medium text-xs flex-1">{{ $feature->feature_name }}</span>
                                            <i class="fas fa-check-circle text-green-500 text-xs"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-auto pt-2">
                            @if($canSubscribe)
                                @if((float) $plan->price_monthly > 0)
                                    <a href="{{ route('staff.subscriptions.register', $plan) }}" class="block w-full text-center px-4 py-3 rounded-xl {{ $cardHighlight ? 'bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white shadow-lg' : 'bg-gray-100 hover:bg-gray-200 text-gray-800 border-2 border-gray-200' }} font-semibold text-sm transition-all duration-300 hover:shadow-xl transform hover:-translate-y-0.5">
                                        <i class="fas fa-arrow-right mr-2"></i>
                                        {{ $isChatLocked && !$hasChat ? 'Xem & đăng ký gói' : 'Đăng ký gói này' }}
                                    </a>
                                    @if((int) $plan->trial_days > 0)
                                        <p class="text-center text-xs text-brand-600 font-semibold mt-2">
                                            <i class="fas fa-gift mr-1"></i>
                                            Dùng thử miễn phí {{ (int) $plan->trial_days }} ngày
                                        </p>
                                    @endif
                                @else
                                    <a href="{{ route('staff.subscriptions.register', $plan) }}" class="block w-full text-center px-4 py-3 rounded-xl {{ $cardHighlight ? 'bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white shadow-lg' : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-lg' }} font-semibold text-sm transition-all duration-300 hover:shadow-xl transform hover:-translate-y-0.5">
                                        <i class="fas fa-user-plus mr-2"></i>
                                        Chọn gói miễn phí
                                    </a>
                                    <p class="text-center text-xs text-green-600 font-semibold mt-2">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Không cần thanh toán
                                    </p>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="block w-full text-center px-4 py-3 rounded-xl bg-gray-200 text-gray-700 font-semibold text-sm">Đăng nhập để đăng ký gói</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                    </div>
                    <p class="text-gray-500 text-lg">Chưa có gói đăng ký nào. Vui lòng liên hệ quản trị viên.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
