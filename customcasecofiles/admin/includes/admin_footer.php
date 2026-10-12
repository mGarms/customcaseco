    </main>

  </div>

</div>


<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
  src="<?= BASE_URL ?>/assets/js/main.js"
></script>

<script
  src="<?= BASE_URL ?>/assets/js/theme.js"
></script>


<script>

  /* =====================================================
     ADMIN SIDEBAR TOGGLE
     ===================================================== */

  var toggle = document.getElementById('adminMenuToggle');

  if (toggle) {

    toggle.addEventListener('click', function () {

      document
        .querySelector('.admin-layout')
        .classList
        .toggle('sidebar-open');

    });

  }


  /* =====================================================
     ADMIN THEME TOGGLE
     ===================================================== */

  document.addEventListener('DOMContentLoaded', function () {

    var button =
      document.getElementById('adminThemeToggle');

    var icon =
      document.getElementById('adminThemeIcon');


    if (!button) {
      return;
    }


    function updateAdminThemeButton() {

      var theme =
        document.documentElement.getAttribute('data-theme')
        || 'light';


      if (theme === 'dark') {

        icon.classList.remove(
          'bi-moon-stars-fill'
        );

        icon.classList.add(
          'bi-sun-fill'
        );

        button.setAttribute(
          'aria-label',
          'Switch to light mode'
        );

        button.setAttribute(
          'title',
          'Switch to light mode'
        );

      } else {

        icon.classList.remove(
          'bi-sun-fill'
        );

        icon.classList.add(
          'bi-moon-stars-fill'
        );

        button.setAttribute(
          'aria-label',
          'Switch to dark mode'
        );

        button.setAttribute(
          'title',
          'Switch to dark mode'
        );

      }

    }


    updateAdminThemeButton();


    button.addEventListener('click', function () {

      var current =
        document.documentElement.getAttribute('data-theme')
        || 'light';

      var next =
        current === 'dark'
          ? 'light'
          : 'dark';


      document.documentElement.setAttribute(
        'data-theme',
        next
      );

      document.documentElement.classList.toggle(
        'theme-dark',
        next === 'dark'
      );

      document.documentElement.classList.toggle(
        'theme-light',
        next === 'light'
      );


      try {

        localStorage.setItem(
          'cc-theme',
          next
        );

      } catch (e) {}


      updateAdminThemeButton();

    });

  });

</script>

</body>
</html>