// Global JS loaded on every page. Page-specific scripts (designer, cart)
// are loaded separately through $extraJs.
document.addEventListener('DOMContentLoaded', function () {

  // 1. Auto-dismiss flash alerts after 5 seconds
  document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
    setTimeout(function () {
      if (document.body.contains(alert)) {
        bootstrap.Alert.getOrCreateInstance(alert).close();
      }
    }, 5000);
  });

  // 2. Navbar shadow + back-to-top button appear on scroll
  var navbar = document.querySelector('.cc-navbar');
  var backToTop = document.getElementById('backToTop');

  function onScroll() {
    var y = window.scrollY;
    if (navbar) navbar.classList.toggle('scrolled', y > 10);
    if (backToTop) backToTop.classList.toggle('show', y > 400);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  if (backToTop) {
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // 3. Confirm dialogs. Usage: <a href="..." data-confirm="Delete this item?">
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el && !window.confirm(el.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  });

  // 4. Prevent double form submits. Usage: <form data-once>
  document.querySelectorAll('form[data-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait...';
      }
    });
  });

  // 5. Scroll-reveal animation using IntersectionObserver.
  //    Add class="cc-reveal" to any element you want to fade/slide in.
  //    Wrap a row in class="cc-reveal-group" to stagger its children.
  var revealEls = document.querySelectorAll('.cc-reveal');
  if (revealEls.length) {
    if ('IntersectionObserver' in window) {
      var revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('cc-reveal-visible');
            revealObserver.unobserve(entry.target); // animate once, not every scroll
          }
        });
      }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

      revealEls.forEach(function (el) { revealObserver.observe(el); });
    } else {
      // Fallback for very old browsers: just show everything immediately
      revealEls.forEach(function (el) { el.classList.add('cc-reveal-visible'); });
    }

    // Also catch elements already in view on load/refresh
    revealEls.forEach(function (el) {
      var rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight && rect.bottom > 0) {
        requestAnimationFrame(function () {
          el.classList.add('cc-reveal-visible');
        });
      }
    });
  }

  // 6. Show/Hide password toggle.
  //    Usage: <button type="button" class="password-toggle-btn" data-target="password">
  //             <i class="bi bi-eye"></i>
  //           </button>
  //    data-target must match the id of the password <input> to toggle.
  document.querySelectorAll('.password-toggle-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      if (!input) return;
      var icon = btn.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
        btn.setAttribute('aria-label', 'Hide password');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
        btn.setAttribute('aria-label', 'Show password');
      }
    });
  });

});

// Shared helpers other scripts can call, e.g. CCC.updateCartBadge(3)
window.CCC = {
  updateCartBadge: function (count) {
    var badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.classList.toggle('d-none', count <= 0);
  }
};