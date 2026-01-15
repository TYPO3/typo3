const BREAKPOINT = 1280;

const body         = document.body;
const menuButtons  = document.querySelectorAll('.JS_header-menu-button');
const nav          = document.getElementById('navigation');
const subnavLinks  = document.querySelectorAll('.JS_header_subnav_link');

if (menuButtons.length && nav) {

  const isMobile = () => window.innerWidth < BREAKPOINT;

  const updateScrollLock = () => {
    const menuOpen = nav.classList.contains('header__menu--open');
    body.style.overflow = menuOpen ? 'hidden' : '';
  };

  // menu buttons
  menuButtons.forEach(menuButton => {
    menuButton.addEventListener('click', event => {
      if (!isMobile()) return;
      event.preventDefault();

      nav.classList.toggle('header__menu--open');
      menuButton.classList.toggle('header__menu-button--open');
      body.classList.toggle('body--menu-open');

      updateScrollLock();
    });
  });

  // toggle subnavigation
  subnavLinks.forEach(link => {
    link.addEventListener('click', event => {
      if (!isMobile()) return;
      event.preventDefault();

      const targetId = link.dataset.target;
      if (!targetId) return;

      const subnav = document.getElementById(targetId);
      if (!subnav) return;

      subnav.classList.toggle('header__subnav--active');
    });
  });

  // Reset at desktop viewport
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      nav.classList.remove('header__menu--open');
      body.classList.remove('body--menu-open');
      body.style.overflow = '';

      menuButtons.forEach(btn => {
        btn.classList.remove('header__menu-button--open');
      });

      document
        .querySelectorAll('.JS_header-subnav.header__subnav--active')
        .forEach(openSubnav => {
          openSubnav.classList.remove('header__subnav--active');
        });
    }
  });
}
