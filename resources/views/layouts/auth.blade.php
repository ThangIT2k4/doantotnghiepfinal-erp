<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ZoroRMS'))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            overflow-x: hidden;
            background: #071223;
        }

        .me-1 {
            margin-right: 0.25rem;
        }

        .me-2 {
            margin-right: 0.5rem;
        }

        /* Global glass toast system for auth notifications */
        .auth-page-wrapper #alertContainer {
            display: none !important;
        }

        .auth-page-wrapper .auth-inline-error-hidden {
            display: none !important;
        }

        .auth-toast-root {
            position: fixed;
            top: 18px;
            right: 18px;
            width: min(420px, calc(100vw - 24px));
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
            pointer-events: none;
        }

        .auth-toast {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 12px 13px;
            border-radius: 14px;
            color: #ecf8ff;
            border: 1px solid rgba(214, 242, 255, 0.45);
            background: linear-gradient(135deg, rgba(149, 214, 248, 0.24), rgba(115, 178, 219, 0.14));
            box-shadow: 0 18px 36px rgba(3, 12, 25, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.34), inset 0 -1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            opacity: 0;
            transform: translateX(20px) scale(0.97);
            transition: opacity 0.24s ease, transform 0.24s ease;
            pointer-events: auto;
        }

        .auth-toast.is-visible {
            opacity: 1;
            transform: translateX(0) scale(1);
        }

        .auth-toast.auth-toast--error {
            border-color: rgba(255, 185, 198, 0.58);
            background: linear-gradient(135deg, rgba(255, 153, 182, 0.24), rgba(255, 203, 219, 0.14));
            box-shadow: 0 18px 36px rgba(81, 20, 38, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.32), inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        }

        .auth-toast.auth-toast--success {
            border-color: rgba(145, 246, 206, 0.58);
            background: linear-gradient(135deg, rgba(123, 242, 200, 0.24), rgba(175, 255, 235, 0.14));
            box-shadow: 0 18px 36px rgba(8, 64, 50, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.32), inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        }

        .auth-toast.auth-toast--info {
            border-color: rgba(157, 227, 255, 0.58);
            background: linear-gradient(135deg, rgba(133, 217, 255, 0.26), rgba(181, 235, 255, 0.16));
            box-shadow: 0 18px 36px rgba(10, 49, 83, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.32), inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        }

        .auth-toast__icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.86rem;
            border: 1px solid rgba(255, 255, 255, 0.44);
            background: rgba(255, 255, 255, 0.2);
            color: #e8f8ff;
        }

        .auth-toast.auth-toast--error .auth-toast__icon {
            color: #ffdce7;
            border-color: rgba(255, 203, 220, 0.58);
            background: rgba(255, 154, 185, 0.2);
        }

        .auth-toast.auth-toast--success .auth-toast__icon {
            color: #ddffef;
            border-color: rgba(193, 255, 227, 0.58);
            background: rgba(122, 242, 199, 0.2);
        }

        .auth-toast.auth-toast--info .auth-toast__icon {
            color: #dff5ff;
            border-color: rgba(191, 236, 255, 0.58);
            background: rgba(126, 216, 255, 0.2);
        }

        .auth-toast__message {
            font-size: 0.87rem;
            font-weight: 600;
            line-height: 1.45;
            letter-spacing: 0.1px;
            word-break: break-word;
        }

        .auth-toast__close {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            color: #ecf8ff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.86;
            transition: opacity 0.2s ease, background 0.2s ease;
        }

        .auth-toast__close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.22);
        }

        @media (max-width: 640px) {
            .auth-toast-root {
                top: 12px;
                right: 12px;
                left: 12px;
                width: auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-toast,
            .auth-toast__close {
                transition: none;
            }
        }

        /* Decorative orbs must not intercept clicks (e.g. footer "Đăng nhập" / "Đăng ký"). */
        .auth-page-wrapper .circle,
        .auth-page-wrapper .circle1,
        .auth-page-wrapper .circle2 {
            pointer-events: none;
        }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const TOAST_ROOT_ID = 'authToastRoot';
            const activeToastKeys = new Set();

            function normalizeType(type) {
                if (type === 'success' || type === 'info' || type === 'error') {
                    return type;
                }
                return 'error';
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function getToastIcon(type) {
                if (type === 'success') {
                    return 'fa-check-circle';
                }
                if (type === 'info') {
                    return 'fa-circle-info';
                }
                return 'fa-triangle-exclamation';
            }

            function ensureToastRoot() {
                let root = document.getElementById(TOAST_ROOT_ID);
                if (!root) {
                    root = document.createElement('div');
                    root.id = TOAST_ROOT_ID;
                    root.className = 'auth-toast-root';
                    root.setAttribute('aria-live', 'polite');
                    root.setAttribute('aria-atomic', 'false');
                    document.body.appendChild(root);
                }
                return root;
            }

            function removeToast(toast, key) {
                if (!toast || toast.dataset.closing === '1') {
                    return;
                }

                toast.dataset.closing = '1';
                toast.classList.remove('is-visible');

                window.setTimeout(function () {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                    activeToastKeys.delete(key);
                }, 230);
            }

            function showAuthToast(type, message, duration) {
                const toastType = normalizeType(type);
                const cleanMessage = String(message || '').trim();
                if (!cleanMessage) {
                    return;
                }

                const toastKey = toastType + ':' + cleanMessage;
                if (activeToastKeys.has(toastKey)) {
                    return;
                }

                activeToastKeys.add(toastKey);
                const root = ensureToastRoot();
                const toast = document.createElement('div');
                toast.className = 'auth-toast auth-toast--' + toastType;
                toast.innerHTML = '<div class="auth-toast__icon"><i class="fas ' + getToastIcon(toastType) + '"></i></div>' +
                    '<div class="auth-toast__message">' + escapeHtml(cleanMessage) + '</div>' +
                    '<button type="button" class="auth-toast__close" aria-label="Close"><i class="fas fa-times"></i></button>';

                root.appendChild(toast);
                window.requestAnimationFrame(function () {
                    toast.classList.add('is-visible');
                });

                const closeButton = toast.querySelector('.auth-toast__close');
                if (closeButton) {
                    closeButton.addEventListener('click', function () {
                        removeToast(toast, toastKey);
                    });
                }

                window.setTimeout(function () {
                    removeToast(toast, toastKey);
                }, Number(duration) > 0 ? Number(duration) : 5000);
            }

            function hideInlineError(node) {
                node.classList.add('auth-inline-error-hidden');
                node.setAttribute('aria-hidden', 'true');
            }

            function consumeErrorNode(node) {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.dataset.toastified === '1') {
                    hideInlineError(node);
                    return;
                }

                if (!node.classList.contains('text-danger') && !node.classList.contains('auth-error-message')) {
                    return;
                }

                const message = (node.textContent || '').trim();
                if (!message) {
                    return;
                }

                node.dataset.toastified = '1';
                hideInlineError(node);
                showAuthToast('error', message, 5200);
            }

            function consumeAlertNode(node) {
                if (!(node instanceof HTMLElement) || !node.classList.contains('alert')) {
                    return;
                }

                const message = (node.textContent || '').trim();
                if (!message) {
                    node.remove();
                    return;
                }

                const type = node.classList.contains('alert-success')
                    ? 'success'
                    : node.classList.contains('alert-info')
                        ? 'info'
                        : 'error';

                showAuthToast(type, message, 5000);
                node.remove();
            }

            function handleNode(node) {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                consumeErrorNode(node);
                consumeAlertNode(node);

                node.querySelectorAll('.auth-error-message, .text-danger').forEach(consumeErrorNode);
                node.querySelectorAll('.alert').forEach(consumeAlertNode);
            }

            function initAuthToastBridge() {
                const wrapper = document.querySelector('.auth-page-wrapper');
                if (!wrapper) {
                    return;
                }

                ensureToastRoot();
                wrapper.querySelectorAll('.auth-error-message, .text-danger').forEach(consumeErrorNode);
                wrapper.querySelectorAll('.alert').forEach(consumeAlertNode);

                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        mutation.addedNodes.forEach(handleNode);
                    });
                });

                observer.observe(wrapper, {
                    childList: true,
                    subtree: true
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAuthToastBridge);
            } else {
                initAuthToastBridge();
            }

            window.authGlassToast = showAuthToast;
        })();
    </script>
    @stack('scripts')
</body>
</html>
