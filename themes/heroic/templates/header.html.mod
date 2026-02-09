<header class="bg-white shadow">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16">
      <div class="flex">
        <div class="flex-shrink-0 flex items-center">
          <a href="/" class="flex items-center">
            {{#if logo}}
              <img src="/{{logo}}" alt="{{siteName}}" class="h-8 w-auto">
            {{else}}
              <span class="text-xl font-bold text-gray-900">{{siteName}}</span>
            {{/if}}
          </a>
        </div>
        <nav class="ml-6 flex space-x-8 items-center">
          {{menu=main}}
        </nav>
      </div>
    </div>
  </div>
</header>
