{{--
    Full-page loading — cùng ngôn ngữ UI với Auth (login): overlay tối + thẻ glass + orbit xoay.
    @props(['message' => 'Đang tải...'])
--}}

@props([
    'message' => 'Đang tải...',
])

<div id="preloader" class="preloader">
    <div class="preloader-auth-card" role="status" aria-live="polite">
        <div class="preloader-auth-orbit" aria-hidden="true"></div>
        <p class="preloader-auth-text" id="preloaderAuthText">{{ $message }}</p>
    </div>
</div>
