@extends('errors.layout-403')

@section('content')
    @include('errors.partials.forbidden-content', [
        'message' => $message ?? 'Bạn không có quyền truy cập trang này.',
        'plans' => $plans ?? collect(),
        'backUrl' => $backUrl ?? null,
        'fallbackUrl' => $fallbackUrl ?? null,
        'isChatLocked' => $isChatLocked ?? null,
    ])
@endsection
