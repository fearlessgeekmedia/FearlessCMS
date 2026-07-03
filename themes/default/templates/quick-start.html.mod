<section class="quickstart">
  <div class="container">
    <h2 class="section-title">Get started in minutes</h2>
    <p class="section-lead">Install, create your first page, and publish. No database, no fuss.</p>
    <div class="quick-grid">
      <div class="quick-card">
        <span class="quick-step">1</span>
        <h3>Install FearlessCMS</h3>
        <pre><code>git clone https://github.com/fearlessgeekmedia/FearlessCMS
cd FearlessCMS
php -S localhost:8000 -t . router.php</code></pre>
      </div>
      <div class="quick-card">
        <span class="quick-step">2</span>
        <h3>Create your first page</h3>
        <pre><code>echo "<!-- json {\"title\": \"Hello\"} -->\n\nWelcome to FearlessCMS" \
> content/hello.md</code></pre>
      </div>
      <div class="quick-card">
        <span class="quick-step">3</span>
        <h3>Customize the theme</h3>
        <pre><code># Upload a logo and hero banner
Admin → Themes → Theme Options</code></pre>
      </div>
    </div>
  </div>
</section>
