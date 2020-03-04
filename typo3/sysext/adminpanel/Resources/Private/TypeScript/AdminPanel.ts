namespace TYPO3 {
  export const AdminPanelSelectors = {
    adminPanelRole: 'form[data-typo3-role=typo3-adminPanel]',
    moduleTriggerRole: '[data-typo3-role=typo3-adminPanel-module-trigger]',
    moduleParentClass: '.typo3-adminPanel-module',
    contentTabRole: '[data-typo3-role=typo3-adminPanel-content-tab]',
    saveButtonRole: '[data-typo3-role=typo3-adminPanel-saveButton]',
    triggerRole: '[data-typo3-role=typo3-adminPanel-trigger]',
    popupTriggerRole: '[data-typo3-role=typo3-adminPanel-popup-trigger]',
    panelTriggerRole: '[data-typo3-role=typo3-adminPanel-panel-trigger]',
    panelParentClass: '.typo3-adminPanel-panel',
    contentSettingsTriggerRole: '[data-typo3-role=typo3-adminPanel-content-settings]',
    contentSettingsParentClass: '.typo3-adminPanel-content-settings',
    contentParentClass: '.typo3-adminPanel-content',
    zoomTarget: '[data-typo3-zoom-target]',
    zoomClose: '[data-typo3-zoom-close]',
    currentContentRole: '[data-typo3-role=typo3-adminPanel-content]',
    contentPaneRole: '[data-typo3-role=typo3-adminPanel-content-pane]',
  };

  export const AdminPanelClasses = {
    active: 'active',
    activeModule: 'typo3-adminPanel-module-active',
    activeContentSetting: 'typo3-adminPanel-content-settings-active',
    backdrop: 'typo3-adminPanel-backdrop',
    activeTab: 'typo3-adminPanel-content-header-item-active',
    activePane: 'typo3-adminPanel-content-panes-item-active',
    noScroll: 'typo3-adminPanel-noscroll',
    zoomShow: 'typo3-adminPanel-zoom-show',
  };

  export class AdminPanel {
    private readonly adminPanel: HTMLFormElement;
    private readonly modules: AdminPanelModule[];
    private readonly popups: AdminPanelPopup[];
    private readonly panels: AdminPanelPanel[];
    private readonly contentSettings: AdminPanelContentSetting[];
    private readonly trigger: HTMLElement;

    constructor() {
      this.installPolyfills();
      this.adminPanel = document.querySelector(AdminPanelSelectors.adminPanelRole) as HTMLFormElement;
      this.modules = (this.querySelectorAll(AdminPanelSelectors.moduleTriggerRole) as Element[]).map(
        (moduleTrigger: HTMLElement) => {
          const moduleParent = moduleTrigger.closest(AdminPanelSelectors.moduleParentClass);
          return new AdminPanelModule(this, moduleParent, moduleTrigger);
        },
      );
      this.popups = this.querySelectorAll(AdminPanelSelectors.popupTriggerRole).map(
        (popupTrigger: HTMLElement) => new AdminPanelPopup(this, popupTrigger),
      );
      this.panels = this.querySelectorAll(AdminPanelSelectors.panelTriggerRole).map(
        (panelTrigger: HTMLElement) => {
          const panelParent = panelTrigger.closest(AdminPanelSelectors.panelParentClass);
          return new AdminPanelPanel(panelParent, panelTrigger);
        },
      );
      this.contentSettings = this.querySelectorAll(AdminPanelSelectors.contentSettingsTriggerRole).map(
      (contentSettingTrigger: HTMLElement) => {
          const contentSettingElement = contentSettingTrigger
            .closest(AdminPanelSelectors.contentParentClass)
            .querySelector(AdminPanelSelectors.contentSettingsParentClass);
          return new AdminPanelContentSetting(contentSettingElement, contentSettingTrigger);
        },
      );
      this.trigger = document.querySelector(AdminPanelSelectors.triggerRole) as HTMLElement;

      this.initializeEvents();
      this.addBackdropListener();
    }

    disableModules(): void {
      this.modules.forEach((module: AdminPanelModule) => module.disable());
    }

    disablePopups(): void {
      this.popups.forEach((popup: AdminPanelPopup) => popup.disable());
    }

    renderBackdrop(): void {
      const adminPanel = document.querySelector('#TSFE_ADMIN_PANEL_FORM');
      const backdrop = document.createElement('div');
      const body = document.querySelector('body');
      body.classList.add(AdminPanelClasses.noScroll);
      backdrop.classList.add(AdminPanelClasses.backdrop);
      adminPanel.appendChild(backdrop);
      this.addBackdropListener();
    }

    removeBackdrop(): void {
      const backdrop = document.querySelector('.' + AdminPanelClasses.backdrop);
      const body = document.querySelector('body');
      body.classList.remove(AdminPanelClasses.noScroll);
      if (backdrop !== null) {
        backdrop.parentNode.removeChild(backdrop);
      }
    }

    private installPolyfills(): void {
      if (!Element.prototype.matches) {
        // @ts-ignore
        Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
      }

      if (!Element.prototype.closest) {
        Element.prototype.closest = function(this: Element, s: string): Element {
          let el = this;

          do {
            if (el.matches(s)) {
              return el;
            }
            el = el.parentElement;
          } while (el !== null && el.nodeType === 1);
          return null;
        };
      }
    }

    private querySelectorAll(selectors: string, subject: Element | Document = document): Node[] {
      const collection: Node[] = [];
      let elements = subject.querySelectorAll(selectors);
      for (let i = 0; i < elements.length; i++) {
        collection.push(elements[i]);
      }
      return collection;
    }

    private initializeEvents(): void {
      this
        .querySelectorAll(AdminPanelSelectors.contentTabRole)
        .forEach((tab: HTMLElement) => tab.addEventListener('click', this.switchTab.bind(this)));
      this
        .querySelectorAll(AdminPanelSelectors.zoomTarget)
        .forEach((zoomTrigger: HTMLElement) => zoomTrigger.addEventListener('click', this.openZoom.bind(this)));
      this
        .querySelectorAll(AdminPanelSelectors.zoomClose)
        .forEach((zoomTrigger: HTMLElement) => zoomTrigger.addEventListener('click', this.closeZoom.bind(this)));
      this
        .querySelectorAll(AdminPanelSelectors.triggerRole)
        .forEach((trigger: HTMLElement) => trigger.addEventListener('click', this.toggleAdminPanelState.bind(this)));
      this
        .querySelectorAll(AdminPanelSelectors.saveButtonRole)
        .forEach((elm: HTMLElement) => elm.addEventListener('click', this.sendAdminPanelForm.bind(this)));

      this
        .querySelectorAll('[data-typo3-role=typo3-adminPanel-content-close]')
        .forEach((elm: HTMLElement) => {
          elm.addEventListener('click', () => {
            this.disableModules();
            this.removeBackdrop();
          });
        });
      this
        .querySelectorAll('.typo3-adminPanel-table th, .typo3-adminPanel-table td')
        .forEach((elm: HTMLElement) => {
          elm.addEventListener('click', ()  => {
            elm.focus();
            try {
              document.execCommand('copy');
            } catch (err) {
              // nothing here
            }
          });
        });
    }

    private switchTab(event: MouseEvent): void {
      event.preventDefault();

      const activeTabClass = AdminPanelClasses.activeTab;
      const activePaneClass = AdminPanelClasses.activePane;
      const currentTab = event.currentTarget as HTMLElement;
      const currentContent = currentTab.closest(AdminPanelSelectors.currentContentRole);
      const contentTabs = this.querySelectorAll(AdminPanelSelectors.contentTabRole, currentContent);
      const contentPanes = this.querySelectorAll(AdminPanelSelectors.contentPaneRole, currentContent);

      contentTabs.forEach((element: HTMLElement) => element.classList.remove(activeTabClass));
      currentTab.classList.add(activeTabClass);
      contentPanes.forEach((element: HTMLElement) => element.classList.remove(activePaneClass));

      const activePane = document.querySelector('[data-typo3-tab-id=' + currentTab.dataset.typo3TabTarget + ']');
      activePane.classList.add(activePaneClass);
    }

    private openZoom(event: MouseEvent): void {
      event.preventDefault();
      const trigger = event.currentTarget as HTMLElement;
      const targetId = trigger.getAttribute('data-typo3-zoom-target');
      const target = document.querySelector('[data-typo3-zoom-id=' + targetId + ']');
      target.classList.add(AdminPanelClasses.zoomShow);
    }

    private closeZoom(event: MouseEvent): void {
      event.preventDefault();
      const trigger = event.currentTarget as HTMLElement;
      const target = trigger.closest('[data-typo3-zoom-id]');
      target.classList.remove(AdminPanelClasses.zoomShow);
    }

    private sendAdminPanelForm(event: MouseEvent): void {
      event.preventDefault();
      const formData = new FormData(this.adminPanel);
      const request = new XMLHttpRequest();
      request.open('POST', this.adminPanel.dataset.typo3AjaxUrl);
      request.send(formData);
      request.onload = () => location.reload();
    }

    private toggleAdminPanelState(): void {
      const request = new XMLHttpRequest();
      request.open('GET', this.trigger.dataset.typo3AjaxUrl);
      request.send();
      request.onload = () => location.reload();
    }

    private addBackdropListener(): void {
      this.querySelectorAll('.' + AdminPanelClasses.backdrop)
        .forEach((elm: HTMLElement) =>  {
        elm.addEventListener('click', () => {
          this.removeBackdrop();
          this
            .querySelectorAll(AdminPanelSelectors.moduleTriggerRole)
            .forEach((innerElm: HTMLElement) => {
              innerElm.closest(AdminPanelSelectors.moduleParentClass)
                .classList.remove(AdminPanelClasses.activeModule);
            });
        });
      });
    }
  }

  interface AdminPanelSwitchable {
    isActive(): boolean;
    enable(): void;
    disable(): void;
  }

  class AdminPanelPopup implements AdminPanelSwitchable {
    private readonly adminPanel: AdminPanel;
    private readonly element: Element;

    constructor(adminPanel: AdminPanel, element: Element) {
      this.adminPanel = adminPanel;
      this.element = element;
      this.initializeEvents();
    }

    isActive(): boolean {
      return this.element.classList.contains(AdminPanelClasses.active);
    }

    enable(): void {
      this.element.classList.add(AdminPanelClasses.active);
    }

    disable(): void {
      this.element.classList.remove(AdminPanelClasses.active);
    }

    private initializeEvents(): void {
      this.element.addEventListener('click', () => {
        if (this.isActive()) {
          this.disable();
        } else {
          this.adminPanel.disablePopups();
          this.enable();
        }

      });
    }
  }

  class AdminPanelPanel implements AdminPanelSwitchable {
    private readonly element: Element;
    private readonly trigger: Element;

    constructor(element: Element, trigger: Element) {
      this.element = element;
      this.trigger = trigger;
      this.initializeEvents();
    }

    isActive(): boolean {
      return this.element.classList.contains(AdminPanelClasses.active);
    }

    enable(): void {
      this.element.classList.add(AdminPanelClasses.active);
    }

    disable(): void {
      this.element.classList.remove(AdminPanelClasses.active);
    }

    private initializeEvents(): void {
      this.trigger.addEventListener('click', () => {
        if (this.isActive()) {
          this.disable();
        } else {
          this.enable();
        }
      });
    }
  }

  class AdminPanelContentSetting implements AdminPanelSwitchable {
    private readonly element: Element;
    private readonly trigger: Element;

    constructor(element: Element, trigger: Element) {
      this.element = element;
      this.trigger = trigger;
      this.initializeEvents();
    }

    isActive(): boolean {
      return this.element.classList.contains(AdminPanelClasses.activeContentSetting);
    }

    enable(): void {
      this.element.classList.add(AdminPanelClasses.activeContentSetting);
    }

    disable(): void {
      this.element.classList.remove(AdminPanelClasses.activeContentSetting);
    }

    private initializeEvents(): void {
      this.trigger.addEventListener('click', () => {
        if (this.isActive()) {
          this.disable();
        } else {
          this.enable();
        }
      });
    }
  }

  class AdminPanelModule implements AdminPanelSwitchable {
    private readonly adminPanel: AdminPanel;
    private readonly element: Element;
    private readonly trigger: Element;

    constructor(adminPanel: AdminPanel, element: Element, trigger: Element) {
      this.adminPanel = adminPanel;
      this.element = element;
      this.trigger = trigger;
      this.initializeEvents();
    }

    isActive(): boolean {
      return this.element.classList.contains(AdminPanelClasses.activeModule);
    }

    enable(): void {
      this.element.classList.add(AdminPanelClasses.activeModule);
    }

    disable(): void {
      this.element.classList.remove(AdminPanelClasses.activeModule);
    }

    private initializeEvents(): void {
      this.trigger.addEventListener('click', (event: MouseEvent) => {
        this.adminPanel.removeBackdrop();
        if (this.isActive()) {
          this.disable();
        } else {
          this.adminPanel.disableModules();
          this.adminPanel.renderBackdrop();
          this.enable();
        }
      });
    }
  }
}

(function(): void {
  window.addEventListener(
    'load',
    () => new TYPO3.AdminPanel(),
    false,
  );
})();
