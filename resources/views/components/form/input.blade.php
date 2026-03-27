@props(['id' => null, 'type' => 'text', 'name', 'value' => old($name), 'autocomplete' => null, 'placeholder' => null])

<input
	{{ $attributes->merge([
		'class' => 'w-full px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-[#f53003]/30',
	]) }}
	@if($id) id="{{ $id }}" @endif
	type="{{ $type }}"
	name="{{ $name }}"
	value="{{ $value }}"
	@if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
	@if($placeholder) placeholder="{{ $placeholder }}" @endif
/>


