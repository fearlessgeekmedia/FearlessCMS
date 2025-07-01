// Throttle function to limit how often a function can be called
function throttle(func, limit) {
    let inThrottle;
    return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Handle scroll events
window.addEventListener('scroll', throttle(function () {
    const header = document.querySelector('.site-header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
}, 100));

// Mobile menu toggle
function toggleMenu() {
    const nav = document.querySelector('.main-navigation ul');
    nav.classList.toggle('active');
} 