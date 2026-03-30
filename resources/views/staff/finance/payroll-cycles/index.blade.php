@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Kỳ Lương')

@section('content')
@include('staff.components.feature-under-construction', [
    'title' => 'Quản lý Kỳ Lương',
    'message' => 'Chức năng này đang được triển khai.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])
@endsection
