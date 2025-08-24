
/* Parallax Plugin JavaScript */
(function() {
    'use strict';
    
    function initParallax() {
        const parallaxSections = document.querySelectorAll('.parallax-section');
        
        if (parallaxSections.length === 0) return;
        
        // Check if user prefers reduced motion
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (prefersReducedMotion) {
            console.log('Parallax disabled due to user preference for reduced motion');
            return;
        }
        
        function updateParallax() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            parallaxSections.forEach(section => {
                const rect = section.getBoundingClientRect();
                const speed = parseFloat(section.dataset.speed) || 0.5;
                const effect = section.dataset.effect || 'scroll';
                
                // Process all sections, not just those in viewport for initial positioning
                const background = section.querySelector('.parallax-background');
                if (background) {
                    let yPos;
                    
                    if (effect === 'scroll') {
                        yPos = (scrollTop - section.offsetTop) * speed;
                    } else if (effect === 'fixed') {
                        yPos = 0;
                    } else {
                        yPos = (scrollTop - section.offsetTop) * speed;
                    }
                    
                    // Use transform3d for better performance and ensure proper initial positioning
                    background.style.transform = `translate3d(-50%, calc(-50% + ${yPos}px), 0)`;
                    
                    // Remove inline sizing - let CSS handle it
                    background.style.minWidth = '';
                    background.style.minHeight = '';
                }
            });
        }
        
        // Throttle scroll events for performance
        let ticking = false;
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
                setTimeout(() => { ticking = false; }, 16); // ~60fps
            }
        }
        
        // Initialize backgrounds immediately
        parallaxSections.forEach(section => {
            const background = section.querySelector('.parallax-background');
            if (background) {
                // Ensure immediate proper sizing and positioning
                background.style.transform = 'translate3d(-50%, -50%, 0)';
                background.style.minWidth = '';
                background.style.minHeight = '';
            }
        });
        
        // Initial call
        updateParallax();
        
        // Add scroll listener
        window.addEventListener('scroll', requestTick, { passive: true });
        window.addEventListener('resize', updateParallax, { passive: true });
        
        console.log(`Initialized parallax for ${parallaxSections.length} sections`);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initParallax);
    } else {
        initParallax();
    }
})();
