<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('light-mode');
        });
    }
    
    // Glitch effect
    const glitchElements = document.querySelectorAll('.cyberpunk-glitch');
    glitchElements.forEach(element => {
        setInterval(() => {
            element.style.transform = `translate(${Math.random() * 2 - 1}px, ${Math.random() * 2 - 1}px)`;
        }, 100);
    });
    
    // Typing effect for titles
    const titles = document.querySelectorAll('.cyberpunk-title');
    titles.forEach(title => {
        const text = title.textContent;
        title.textContent = '';
        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
                title.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        };
        typeWriter();
    });
});
</script> 