@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Sự kiện Hoa hồng')

@section('content')
@include('staff.components.feature-under-construction', [
    'title' => 'Quản lý Sự kiện Hoa hồng',
    'message' => 'Chức năng này đang được triển khai.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])
@endsection
