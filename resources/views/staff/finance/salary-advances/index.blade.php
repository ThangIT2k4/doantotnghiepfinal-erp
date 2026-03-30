@extends('layouts.staff_dashboard')

@section('title', 'Quản lý ứng lương')

@section('content')
@include('staff.components.feature-under-construction', [
    'title' => 'Quản lý ứng lương',
    'message' => 'Chức năng này đang được triển khai.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])
@endsection
