/**
 * Laravel Echo Configuration
 * Handles real-time features for the application
 */

// Initialize Echo when both Pusher and Echo are available
document.addEventListener('DOMContentLoaded', function() {
    // Check if Pusher and Echo are available
    if (typeof Pusher !== 'undefined' && typeof Echo !== 'undefined') {
        try {
            // Get Pusher config from meta tags or use defaults
            const pusherKey = document.querySelector('meta[name="pusher-key"]')?.getAttribute('content') || 
                             (window.PUSHER_KEY || 'your-pusher-key');
            const pusherCluster = document.querySelector('meta[name="pusher-cluster"]')?.getAttribute('content') || 
                                 (window.PUSHER_CLUSTER || 'ap1');
            
            // Initialize Echo with Pusher
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: pusherCluster,
                forceTLS: true,
                encrypted: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                },
                enabledTransports: ['ws', 'wss']
            });
            
            console.log('Laravel Echo initialized successfully');
        } catch (error) {
            console.error('Error initializing Laravel Echo:', error);
            createFallbackEcho();
        }
    } else {
        console.warn('Pusher or Echo is not loaded. Real-time features will not work.');
        createFallbackEcho();
    }
});

// Fallback Echo object for when Pusher/Echo is not available
function createFallbackEcho() {
    if (typeof window.Echo === 'undefined') {
        window.Echo = {
            channel: function() {
                return {
                    listen: function() { return this; },
                    stopListening: function() { return this; }
                };
            },
            private: function() {
                return {
                    listen: function() { return this; },
                    stopListening: function() { return this; }
                };
            },
            leave: function() {},
            disconnect: function() {}
        };
    }
}
