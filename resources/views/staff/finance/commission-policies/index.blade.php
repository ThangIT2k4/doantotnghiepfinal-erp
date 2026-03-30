@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Chính sách Hoa hồng')

@section('content')
@include('staff.components.feature-under-construction', [
    'title' => 'Quản lý Chính sách Hoa hồng',
    'message' => 'Chức năng này đang được triển khai.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])
@endsection
