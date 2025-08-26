<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="/">
      {{#if themeOptions.logo}}
        <img class="brand-logo" src="/{{themeOptions.logo}}" alt="{{site_name}}">
      {{/if}}
      <span class="brand-text">{{site_name}}</span>
    </a>
    <button id="fcms-hamburger" type="button" class="fcms-hamburger" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <button id="fcms-theme-toggle" type="button" class="fcms-theme-toggle" aria-label="Toggle theme (system/light/dark)" title="Theme">◐</button>
    <nav aria-label="Main navigation">
      {{menu=main}}
    </nav>
  </div>
  <script>
    (function(){
      var root=document.documentElement;
      var KEY='fcms-theme';
      function apply(pref){ if(pref==='system'){ root.removeAttribute('data-theme'); } else { root.setAttribute('data-theme', pref); } }
      try {
        var pref=localStorage.getItem(KEY)||'system';
        apply(pref);
      } catch(e) {}

      var toggle=document.getElementById('fcms-theme-toggle');
      if(toggle){
        toggle.addEventListener('click', function(){
          var seq=['system','light','dark'];
          var cur; try { cur=localStorage.getItem(KEY)||'system'; } catch(e){ cur='system'; }
          var next=seq[(seq.indexOf(cur)+1)%3];
          try { localStorage.setItem(KEY,next); } catch(e) {}
          apply(next);
          toggle.setAttribute('data-mode', next);
        });
      }

      var hb=document.getElementById('fcms-hamburger');
      var nav=document.querySelector('header nav');
      if(hb && nav){
        hb.addEventListener('click', function(){
          var open=nav.classList.toggle('is-open');
          hb.setAttribute('aria-expanded', open);
        });
      }
    })();
  </script>
  
</header>