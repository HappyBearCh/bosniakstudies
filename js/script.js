// Mobile Navigation Toggle
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');
const navLinks = document.querySelectorAll('.nav-link');

// Toggle menu
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Close menu when link is clicked
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    });
});

// Close menu when clicking outside
document.addEventListener('click', (event) => {
    const isClickInsideNav = navMenu.contains(event.target);
    const isClickOnHamburger = hamburger.contains(event.target);
    
    if (!isClickInsideNav && !isClickOnHamburger && navMenu.classList.contains('active')) {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    }
});

// Update active nav link on page load and scroll
function updateActiveLink() {
    const currentLocation = window.location.pathname;
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentLocation || 
            (currentLocation === '/' && link.getAttribute('href') === 'index.html')) {
            link.classList.add('active');
        }
    });
}

// Update active link on load
document.addEventListener('DOMContentLoaded', updateActiveLink);

// Smooth scroll for anchor links
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

// Add scroll animation for elements
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe featured cards
document.querySelectorAll('.featured-card, .stat-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(card);
});

// Navbar background on scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    } else {
        navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
    }
});

// Smooth page transitions
document.addEventListener('DOMContentLoaded', () => {
    document.body.style.opacity = '1';
    document.body.style.transition = 'opacity 0.3s ease';
});

// Add page transition effect to links
const internalLinks = document.querySelectorAll('a[href$=".html"]');
internalLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        // Allow the click through for navigation
        // The page will naturally transition
    });
});
