@extends('errors.layout-403')

@section('content')
    @php
        $rawMessage = isset($exception) ? ($exception->getMessage() ?? '') : '';
        $message = trim($rawMessage) !== '' ? $rawMessage : 'Bạn không có quyền truy cập trang này.';
        $plans = \App\Models\SubscriptionPlan::query()
            ->active()
            ->with('features')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $fallbackRoute = auth()->check() ? 'dashboard' : 'login';
        $fallbackUrl = route($fallbackRoute);
        $previousUrl = url()->previous();
        $currentUrl = request()->fullUrl();
        $backUrl = ($previousUrl && $previousUrl !== $currentUrl) ? $previousUrl : $fallbackUrl;
        $isChatLocked = \Illuminate\Support\Str::contains(
            mb_strtolower($message),
            ['chat với ai', 'chat ai', 'enable_chat', 'tính năng chat', 'gói dịch vụ', 'nâng cấp']
        );
    @endphp
    @include('errors.partials.forbidden-content', compact('message', 'plans', 'backUrl', 'fallbackUrl', 'isChatLocked'))
@endsection
