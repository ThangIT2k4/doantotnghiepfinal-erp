@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Tài chính Nâng cao')

@section('content')
@include('staff.components.feature-under-construction', [
    'title' => 'Quản lý Tài chính Nâng cao',
    'message' => 'Chức năng này đang được triển khai.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])
@endsection
