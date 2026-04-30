document.addEventListener('DOMContentLoaded', () => {
    // 1. Reveal Animations (Intersection Observer)
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                // Optional: Unobserve after revealing to prevent re-animating
                // observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    // 2. Smooth Scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
  
                window.scrollTo({
                     top: offsetPosition,
                     behavior: "smooth"
                });
            }
        });
    });

    // 3. Header Scroll Effect
    const header = document.getElementById('header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 20) {
            header.classList.add('scrolled');
            header.style.background = 'rgba(255, 255, 255, 1)'; // 滚动后完全不透明
        } else {
            header.classList.remove('scrolled');
            header.style.background = 'rgba(255, 255, 255, 0.7)'; // 初始半透明
        }
    });

    // 4. Hero Mockup Scroll Parallax (Levelling effect)
    const heroMockup = document.getElementById('hero-mockup');
    if (heroMockup) {
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            // When scrolled down about 300px, it should be fully leveled (rotateX(0))
            const progress = Math.min(scrollY / 300, 1);
            
            // Starts at 5deg, goes to 0deg
            const angle = 5 - (progress * 5);
            // Starts at 0.95 scale, goes to 1
            const scale = 0.95 + (progress * 0.05);
            
            heroMockup.style.transform = `rotateX(${angle}deg) scale(${scale})`;
        });
    }

    // 5. Bento Card Mouse Spotlight
    const bentoCards = document.querySelectorAll('.bento-card');
    bentoCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Pass coordinates to CSS variables
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });

    // 6. Code Window Tab Switching
    const tabButtons = document.querySelectorAll('.code-tab');
    const codeBodies = document.querySelectorAll('.code-body');
    let activeTabIndex = 0;
    let autoPlayInterval;

    const switchTab = (index) => {
        const btn = tabButtons[index];
        const targetId = btn.getAttribute('data-target');

        // Update Buttons
        tabButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Update Code Blocks
        codeBodies.forEach(body => {
            body.classList.remove('active');
            if (body.id === targetId) {
                body.classList.add('active');
            }
        });
        activeTabIndex = index;
    };

    tabButtons.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            switchTab(index);
            // 用户点击后停止自动播放
            clearInterval(autoPlayInterval);
        });
    });

    // 自动轮播逻辑 (每 4 秒切换一次)
    autoPlayInterval = setInterval(() => {
        let nextIndex = (activeTabIndex + 1) % tabButtons.length;
        switchTab(nextIndex);
    }, 4000);
});
