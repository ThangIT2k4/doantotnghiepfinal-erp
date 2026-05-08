// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNav = document.getElementById('mobile-nav');
    
    if (mobileMenuBtn && mobileNav) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileNav.classList.toggle('active');
        });
    }
    
    // Search Form Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Here you could add logic to show/hide different form content
            // based on the selected tab
            const tabType = this.getAttribute('data-tab');
            console.log('Selected tab:', tabType);
        });
    });
    
    // Favorite Button Toggle
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent card click event
            this.classList.toggle('active');
            
            // Add animation effect
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    // Property Card Click (for navigation)
    const propertyCards = document.querySelectorAll('.property-card');
    
    propertyCards.forEach(card => {
        card.addEventListener('click', function() {
            // Add click animation
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Here you would navigate to property detail page
            console.log('Property card clicked');
        });
    });
    
    // Category Card Click
    const categoryCards = document.querySelectorAll('.category-card');
    
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            // Add click animation
            this.style.transform = 'scale(0.98) translateY(-4px)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Here you would navigate to category listings
            console.log('Category card clicked');
        });
    });
    
    // Search Functionality
    const searchButtons = document.querySelectorAll('.search-btn, .btn-search');
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get search term from nearest input
            const searchTerm = this.parentElement.querySelector('.search-input')?.value || 
                              document.querySelector('.search-input')?.value;
            
            if (searchTerm && searchTerm.trim()) {
                console.log('Searching for:', searchTerm);
                // Here you would implement the search functionality
                
                // Add loading animation
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 1000);
            }
        });
    });
    
    // Form submission for hero search
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect all form data
            const formData = new FormData(this);
            const searchData = {};
            
            // Get select values
            const selects = this.querySelectorAll('.form-select');
            selects.forEach(select => {
                if (select.value && select.value !== select.options[0].value) {
                    searchData[select.previousElementSibling.textContent] = select.value;
                }
            });
            
            console.log('Search criteria:', searchData);
            // Here you would send the search request to your backend
        });
    }
    
    // Newsletter Subscription
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();
            
            if (email && isValidEmail(email)) {
                console.log('Newsletter subscription for:', email);
                
                // Add success animation
                const button = this.querySelector('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.style.background = 'var(--success)';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                    emailInput.value = '';
                }, 2000);
            } else {
                // Show error
                emailInput.style.borderColor = 'var(--danger)';
                setTimeout(() => {
                    emailInput.style.borderColor = '';
                }, 2000);
            }
        });
    }
    
    // Smooth scrolling for anchor links
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add fade-in animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.property-card, .category-card, .section-header');
    animatedElements.forEach(el => observer.observe(el));
    
    // Add staggered animation to property cards
    const propertyCardsForAnimation = document.querySelectorAll('.property-card');
    propertyCardsForAnimation.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Add staggered animation to category cards
    const categoryCardsForAnimation = document.querySelectorAll('.category-card');
    categoryCardsForAnimation.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});

// Utility Functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Add loading states for buttons
function addLoadingState(button, duration = 1000) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, duration);
}

// Toast notification system (simple implementation)
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    // Add toast styles if not already added
    if (!document.querySelector('#toast-styles')) {
        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 8px;
                z-index: 1000;
                animation: slideInRight 0.3s ease;
            }
            .toast-success { border-left: 4px solid var(--success); }
            .toast-error { border-left: 4px solid var(--danger); }
            .toast-info { border-left: 4px solid var(--primary); }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}