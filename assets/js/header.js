// header.js - Header scroll behavior
(function() {
    let lastScroll = 0;
    const topHeader = document.querySelector('.top-header');
    
    if (!topHeader) return;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 0) {
            topHeader.classList.remove('hidden');
            return;
        }
        
        if (currentScroll > lastScroll && currentScroll > 100) {
            // Scrolling down
            topHeader.classList.add('hidden');
        } else {
            // Scrolling up
            topHeader.classList.remove('hidden');
        }
        
        lastScroll = currentScroll;
    });
})();
