<script>
// Custom Variables Demo Theme JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic color application based on theme options
    const primaryColor = '{{primaryColor}}';
    const accentColor = '{{accentColor}}';
    
    // Apply accent color to interactive elements
    const interactiveElements = document.querySelectorAll('.nav-link, .tag, .btn');
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.backgroundColor = accentColor;
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Reading time calculation (if not provided in frontmatter)
    {{#if showReadingTime}}
        {{#unless readingTime}}
            const content = document.querySelector('.article-content');
            if (content) {
                const text = content.textContent;
                const wordCount = text.split(/\s+/).length;
                const readingTime = Math.ceil(wordCount / 200); // 200 words per minute
                
                const readingTimeElement = document.createElement('span');
                readingTimeElement.className = 'meta-item reading-time';
                readingTimeElement.innerHTML = '<i class="icon-clock"></i>' + readingTime + ' min read';
                
                const metaContainer = document.querySelector('.article-meta');
                if (metaContainer) {
                    metaContainer.appendChild(readingTimeElement);
                }
            }
        {{/unless}}
    {{/if}}
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script> 