function toggleMenu() {
    var menu = document.getElementById("myLinks");
    if (menu.classList.contains("active")) {
        menu.classList.remove("active");
    } else {
        menu.classList.add("active");
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const siteTitle = document.querySelector('.site-title');
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-navigation');
    const siteHeader = document.querySelector('.site-header');
    const scrollThreshold = 300;  // Shrink at 300px
    const expandThreshold = 100;  // Expand at 100px
    let isFading = false;
    let currentOpacity = 1;
    let lastScrollY = window.scrollY;
    let isHidden = false;
    let scrollTimeout;

    // Mobile Menu Toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            this.classList.toggle('active');
            mainNav.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
    }

    // Close menu when clicking outside
    document.addEventListener('click', function (event) {
        if (mainNav.classList.contains('active') &&
            !mainNav.contains(event.target) &&
            !menuToggle.contains(event.target)) {
            menuToggle.classList.remove('active');
            mainNav.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });

    function fadeOut() {
        if (currentOpacity > 0) {
            currentOpacity -= 0.1;
            siteTitle.style.opacity = currentOpacity;
            requestAnimationFrame(fadeOut);
        } else {
            siteTitle.classList.add('hidden');
            siteHeader.classList.add('shrunk');
            isFading = false;
            isHidden = true;
        }
    }

    function fadeIn() {
        if (currentOpacity < 1) {
            currentOpacity += 0.1;
            siteTitle.style.opacity = currentOpacity;
            requestAnimationFrame(fadeIn);
        } else {
            siteHeader.classList.remove('shrunk');
            isFading = false;
            isHidden = false;
        }
    }

    function handleScroll() {
        if (isFading) return;

        const currentScrollY = window.scrollY;
        const scrollingDown = currentScrollY > lastScrollY;
        lastScrollY = currentScrollY;

        // Clear any existing timeout
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }

        // Set a new timeout
        scrollTimeout = setTimeout(() => {
            if (currentScrollY > scrollThreshold) {
                if (!isHidden) {
                    isFading = true;
                    fadeOut();
                }
            } else if (currentScrollY < expandThreshold) {
                if (isHidden) {
                    siteTitle.classList.remove('hidden');
                    isFading = true;
                    fadeIn();
                }
            }
        }, 50);
    }

    // Initial check
    handleScroll();

    // Add scroll event listener with throttling
    let ticking = false;
    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    });
}); 