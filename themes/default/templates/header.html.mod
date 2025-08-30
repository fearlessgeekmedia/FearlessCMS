<!-- Skip links for accessibility -->
<a href="#main-navigation" class="skip-link">Skip to navigation</a>
<a href="#main-content" class="skip-link">Skip to main content</a>

<header class="site-header" role="banner">
  <div class="container header-inner">
    <a class="brand" href="/" aria-label="Go to {{site_name}} homepage">
      {{#if themeOptions.logo}}
        <img class="brand-logo" src="/{{themeOptions.logo}}" alt="{{site_name}} logo" aria-hidden="true">
      {{/if}}
      <span class="brand-text">{{site_name}}</span>
    </a>
    
    <!-- Accessibility controls -->
    <div class="accessibility-controls" role="toolbar" aria-label="Accessibility controls">
      <button id="fcms-hamburger" type="button" class="fcms-hamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="main-navigation">
        <span class="sr-only">Menu</span>
        <span></span><span></span><span></span>
      </button>
      <button id="fcms-theme-toggle" type="button" class="fcms-theme-toggle" aria-label="Toggle theme (system/light/dark)" title="Theme" aria-pressed="false">
        <span class="sr-only">Theme toggle</span>
        ◐
      </button>
    </div>
    
    <nav id="main-navigation" aria-label="Main navigation" role="navigation">
      {{menu=main}}
    </nav>
  </div>
  
  <script>
    (function(){
      var root=document.documentElement;
      var KEY='fcms-theme';
      var toggle=document.getElementById('fcms-theme-toggle');
      var hb=document.getElementById('fcms-hamburger');
      var nav=document.querySelector('header nav');
      
      // Theme toggle functionality
      function apply(pref){ 
        if(pref==='system'){ 
          root.removeAttribute('data-theme'); 
        } else { 
          root.setAttribute('data-theme', pref); 
        } 
      }
      
      function updateThemeToggleState(pref) {
        if (toggle) {
          toggle.setAttribute('aria-pressed', pref !== 'system');
          toggle.setAttribute('data-mode', pref);
          
          // Update aria-label with current state
          const currentMode = pref === 'system' ? 'system preference' : pref;
          toggle.setAttribute('aria-label', `Current theme: ${currentMode}. Click to change theme.`);
        }
      }
      
      try {
        var pref=localStorage.getItem(KEY)||'system';
        apply(pref);
        updateThemeToggleState(pref);
      } catch(e) {}

      if(toggle){
        toggle.addEventListener('click', function(){
          var seq=['system','light','dark'];
          var cur; 
          try { 
            cur=localStorage.getItem(KEY)||'system'; 
          } catch(e){ 
            cur='system'; 
          }
          var next=seq[(seq.indexOf(cur)+1)%3];
          try { 
            localStorage.setItem(KEY,next); 
          } catch(e) {}
          apply(next);
          updateThemeToggleState(next);
          
          // Announce theme change to screen readers
          const modeNames = {
            'system': 'system preference',
            'light': 'light mode',
            'dark': 'dark mode'
          };
          announceToScreenReader(`Theme changed to ${modeNames[next]}`);
        });
      }

      // Hamburger menu functionality
      if(hb && nav){
        hb.addEventListener('click', function(){
          var open=nav.classList.toggle('is-open');
          hb.setAttribute('aria-expanded', open);
          
          // Announce menu state to screen readers
          if (open) {
            announceToScreenReader('Navigation menu opened');
          } else {
            announceToScreenReader('Navigation menu closed');
          }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
          if (!hb.contains(e.target) && !nav.contains(e.target) && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            hb.setAttribute('aria-expanded', 'false');
            announceToScreenReader('Navigation menu closed');
          }
        });
        
        // Close menu on Escape key
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            hb.setAttribute('aria-expanded', 'false');
            announceToScreenReader('Navigation menu closed');
            hb.focus(); // Return focus to hamburger button
          }
        });
      }
      
      // Accessibility enhancement: Announce page load
      if (typeof announceToScreenReader === 'function') {
        announceToScreenReader('Page loaded successfully');
      }
    })();
    
    // Helper function for screen reader announcements
    function announceToScreenReader(message) {
      // Create live region if it doesn't exist
      let liveRegion = document.getElementById('fcms-live-region');
      if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'fcms-live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        document.body.appendChild(liveRegion);
      }
      
      liveRegion.textContent = message;
      
      // Clear message after announcement
      setTimeout(() => {
        liveRegion.textContent = '';
      }, 1000);
    }
  </script>
  
</header>