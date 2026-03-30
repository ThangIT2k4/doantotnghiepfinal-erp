@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Dòng tiền ra')

@section('content')
@include('staff.components.feature-under-construction', [
    'title' => 'Quản lý Dòng tiền ra',
    'message' => 'Chức năng này đang được triển khai.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])
@endsection
