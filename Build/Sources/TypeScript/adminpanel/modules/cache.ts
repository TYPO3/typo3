((): void => {
  class AdminPanelCache {
    private readonly buttons: NodeList;

    constructor() {
      this.buttons = document.querySelectorAll('[data-typo3-role="clearCacheButton"]');

      this.buttons.forEach((element: HTMLElement): void => {
        element.addEventListener('click', (): void => {
          const url = element.dataset.typo3AjaxUrl;
          const request = new XMLHttpRequest();
          request.open('GET', url);
          request.send();
          request.onload = (): void => {
            location.reload();
          };
        });
      });
    }
  }

  window.addEventListener(
    'load',
    () => new AdminPanelCache(),
    false,
  );
})();
