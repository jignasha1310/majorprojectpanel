// ===== NAVBAR SCROLL EFFECT =====
const navbar = document.getElementById('navbar');

window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ===== MOBILE MENU TOGGLE =====
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('nav-menu');
const navButtons = document.getElementById('nav-buttons');
const loginDropdown = document.querySelector('.login-dropdown');
const loginDropdownToggle = document.querySelector('.login-dropdown-toggle');
const loginSearchInput = document.querySelector('.login-search-input');
const loginOptions = document.querySelectorAll('.login-option');
const loginNoResults = document.querySelector('.login-no-results');

hamburger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    navButtons.classList.toggle('active');
    hamburger.classList.toggle('active');
    if (!navButtons.classList.contains('active') && loginDropdown) {
        loginDropdown.classList.remove('open');
        if (loginDropdownToggle) loginDropdownToggle.setAttribute('aria-expanded', 'false');
    }
});

// Close menu when clicking a nav link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        navButtons.classList.remove('active');
        hamburger.classList.remove('active');
    });
});

if (loginDropdown && loginDropdownToggle) {
    const filterLoginOptions = () => {
        if (!loginSearchInput) return;
        const query = loginSearchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        loginOptions.forEach((option) => {
            const label = option.getAttribute('data-label') || option.textContent.toLowerCase();
            const matches = label.includes(query);
            option.style.display = matches ? 'block' : 'none';
            if (matches) visibleCount += 1;
        });

        if (loginNoResults) {
            loginNoResults.hidden = visibleCount !== 0;
        }
    };

    const resetLoginFilter = () => {
        if (loginSearchInput) {
            loginSearchInput.value = '';
        }
        loginOptions.forEach((option) => {
            option.style.display = 'block';
        });
        if (loginNoResults) {
            loginNoResults.hidden = true;
        }
    };

    loginDropdownToggle.addEventListener('click', (e) => {
        e.preventDefault();
        const isOpen = loginDropdown.classList.toggle('open');
        loginDropdownToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) {
            resetLoginFilter();
            if (loginSearchInput) loginSearchInput.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (!loginDropdown.contains(e.target)) {
            loginDropdown.classList.remove('open');
            loginDropdownToggle.setAttribute('aria-expanded', 'false');
            resetLoginFilter();
        }
    });

    loginDropdown.querySelectorAll('.login-dropdown-menu a').forEach(link => {
        link.addEventListener('click', () => {
            loginDropdown.classList.remove('open');
            loginDropdownToggle.setAttribute('aria-expanded', 'false');
            resetLoginFilter();
        });
    });

    if (loginSearchInput) {
        loginSearchInput.addEventListener('input', filterLoginOptions);
        loginSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                loginDropdown.classList.remove('open');
                loginDropdownToggle.setAttribute('aria-expanded', 'false');
                resetLoginFilter();
            }
        });
    }
}

// ===== ACTIVE NAV LINK ON SCROLL =====
const sections = document.querySelectorAll('section[id]');

window.addEventListener('scroll', () => {
    const scrollY = window.pageYOffset;

    sections.forEach(section => {
        const sectionHeight = section.offsetHeight;
        const sectionTop = section.offsetTop - 100;
        const sectionId = section.getAttribute('id');
        const navLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);

        if (navLink && scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            navLink.classList.add('active');
        }
    });
});

// ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const headerOffset = 80;
            const elementPosition = target.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// ===== CONTACT FORM HANDLING =====
const contactForm = document.getElementById('contact-form');

if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value;

        // Show success message (In production, this would send to a server)
        alert(`Thank you, ${name}! Your message has been received. We'll get back to you at ${email} soon.`);

        // Reset form
        contactForm.reset();
    });
}

// ===== ANIMATION ON SCROLL (Intersection Observer) =====
const animateOnScroll = () => {
    const elements = document.querySelectorAll('.feature-card, .panel-card, .step-card, .about-stat');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    elements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
};

// Initialize animations when DOM is ready
document.addEventListener('DOMContentLoaded', animateOnScroll);

// ===== STATS COUNTER ANIMATION =====
const animateCounters = () => {
    const counters = document.querySelectorAll('.stat-number');

    counters.forEach(counter => {
        const target = counter.innerText;
        const hasPlus = target.includes('+');
        const hasK = target.includes('K');
        let num = parseInt(target.replace(/\D/g, ''));

        if (hasK) num = num * 1000;

        let current = 0;
        const increment = num / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= num) {
                current = num;
                clearInterval(timer);
            }

            let display = Math.floor(current);
            if (hasK) display = Math.floor(current / 1000) + 'K';
            if (hasPlus) display += '+';

            counter.innerText = display;
        }, 30);
    });
};

// Trigger counter animation when hero section is in view
const heroObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            heroObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const heroStats = document.querySelector('.hero-stats');
if (heroStats) heroObserver.observe(heroStats);
