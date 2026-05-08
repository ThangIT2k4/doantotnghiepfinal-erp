@extends('layouts.app')

@section('title', 'Preloader Demo')

@section('content')
<div class="container my-5">
    <h1 class="mb-4">Preloader Demonstrations</h1>
    
    <div class="row">
        <!-- Style 1: Default -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Default Style</h5>
                    <p class="card-text">Logo icon với spinner và loading bar</p>
                    <button class="btn btn-primary" onclick="showPreloader('default')">
                        <i class="fas fa-play"></i> Xem Demo
                    </button>
                </div>
            </div>
        </div>

        <!-- Style 2: House -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">House Style</h5>
                    <p class="card-text">Icon ngôi nhà với animation</p>
                    <button class="btn btn-success" onclick="showPreloader('house')">
                        <i class="fas fa-play"></i> Xem Demo
                    </button>
                </div>
            </div>
        </div>

        <!-- Style 3: Minimal -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Minimal Style</h5>
                    <p class="card-text">Thiết kế tối giản, gọn nhẹ</p>
                    <button class="btn btn-info" onclick="showPreloader('minimal')">
                        <i class="fas fa-play"></i> Xem Demo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Preloader Demos -->
    <h2 class="mt-5 mb-4">Inline Preloader (for AJAX)</h2>
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Small - Primary</h6>
                    <x-preloader-inline size="sm" color="primary" />
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Medium - Success</h6>
                    <x-preloader-inline size="md" color="success" />
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Large - Danger</h6>
                    <x-preloader-inline size="lg" color="danger" />
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Medium - Warning</h6>
                    <x-preloader-inline size="md" color="warning" />
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Guide -->
    <h2 class="mt-5 mb-4">Hướng dẫn sử dụng</h2>
    <div class="card">
        <div class="card-body">
            <h5>1. Full Page Preloader</h5>
            <pre class="bg-light p-3"><code>{{-- In layout --}}
&lt;x-preloader /&gt;

{{-- With custom style --}}
&lt;x-preloader style="house" /&gt;
&lt;x-preloader style="minimal" /&gt;

{{-- Without percentage --}}
&lt;x-preloader :showPercentage="false" /&gt;</code></pre>

            <h5 class="mt-4">2. Inline Preloader (AJAX)</h5>
            <pre class="bg-light p-3"><code>&lt;x-preloader-inline /&gt;
&lt;x-preloader-inline size="sm" color="primary" /&gt;
&lt;x-preloader-inline size="lg" color="success" /&gt;</code></pre>

            <h5 class="mt-4">3. JavaScript Control</h5>
            <pre class="bg-light p-3"><code>// Show preloader
window.Preloader.show();

// Hide preloader
window.Preloader.hide();

// Listen for hidden event
window.addEventListener('preloaderHidden', function() {
    console.log('Preloader hidden');
});</code></pre>

            <h5 class="mt-4">4. AJAX Example</h5>
            <pre class="bg-light p-3"><code>// Show preloader before AJAX
window.Preloader.show();

fetch('/api/data')
    .then(response => response.json())
    .then(data => {
        // Process data
        window.Preloader.hide();
    })
    .catch(error => {
        window.Preloader.hide();
    });</code></pre>
        </div>
    </div>
</div>

<!-- Demo Preloaders (hidden by default) -->
<div id="demo-preloader-default" style="display: none;">
    <x-preloader style="default" />
</div>

<div id="demo-preloader-house" style="display: none;">
    <x-preloader style="house" />
</div>

<div id="demo-preloader-minimal" style="display: none;">
    <x-preloader style="minimal" />
</div>

@push('scripts')
<script>
function showPreloader(style) {
    const preloaderHtml = document.getElementById('demo-preloader-' + style).innerHTML;
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = preloaderHtml;
    document.body.appendChild(tempDiv.firstElementChild);
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    }, 3000);
}
</script>
@endpush
@endsection

