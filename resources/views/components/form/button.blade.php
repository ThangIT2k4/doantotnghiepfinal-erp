@props(['variant' => 'primary', 'type' => 'submit'])

@php
	$base = 'inline-flex items-center justify-center px-5 py-2 rounded-sm text-sm font-medium transition-colors border';
	$variants = [
		'primary' => $base.' bg-[#1b1b18] text-white border-black hover:bg-black hover:border-black dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white dark:hover:border-white',
		'ghost' => $base.' bg-transparent text-[#1b1b18] dark:text-[#EDEDEC] border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A]',
	];
@endphp

@if ($attributes->has('href'))
    <a {{ $attributes->merge(['class' => $variants[$variant] ?? $variants['primary']]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $variants[$variant] ?? $variants['primary']]) }} type="{{ $type }}">
        {{ $slot }}
    </button>
@endif


