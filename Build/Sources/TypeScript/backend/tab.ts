/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement } from 'lit/decorators.js';
import DocumentService from '@typo3/core/document-service';
import BrowserSession from '@typo3/backend/storage/browser-session';
import Client from '@typo3/backend/storage/client';
import '@typo3/backend/element/icon-element';

export interface TabEventDetail {
  relatedTarget: HTMLElement | null;
}

export class TabShowEvent extends CustomEvent<TabEventDetail> {
  static readonly eventName = 'typo3:tab:show';

  constructor(relatedTarget: HTMLElement | null) {
    super(TabShowEvent.eventName, {
      bubbles: true,
      cancelable: true,
      detail: { relatedTarget }
    });
  }
}

export class TabShownEvent extends CustomEvent<TabEventDetail> {
  static readonly eventName = 'typo3:tab:shown';

  constructor(relatedTarget: HTMLElement | null) {
    super(TabShownEvent.eventName, {
      bubbles: true,
      detail: { relatedTarget }
    });
  }
}

export class Tab {
  private static idCounter = 0;

  constructor() {
    document.addEventListener('click', (e: Event) => {
      const trigger = (e.target as Element)?.closest<HTMLElement>('[data-typo3-tab], [data-bs-toggle="tab"]');
      if (trigger) {
        e.preventDefault();
        Tab.show(trigger);
      }
    });

    document.addEventListener('keydown', (e: KeyboardEvent) => {
      const target = e.target as HTMLElement;
      if (!target.matches?.('[role="tab"]')) {
        return;
      }

      const tabList = target.closest('[role="tablist"]');
      if (!tabList) {
        return;
      }

      const tabs = Array.from(tabList.querySelectorAll<HTMLElement>('[role="tab"]:not([disabled])'));
      if (tabs.length < 2) {
        return;
      }

      const isRtl = getComputedStyle(tabList).direction === 'rtl';
      let nextTab: HTMLElement | null = null;

      switch (e.key) {
        case 'ArrowLeft':
          nextTab = tabs[(tabs.indexOf(target) - (isRtl ? -1 : 1) + tabs.length) % tabs.length];
          break;
        case 'ArrowRight':
          nextTab = tabs[(tabs.indexOf(target) + (isRtl ? -1 : 1) + tabs.length) % tabs.length];
          break;
        case 'Home':
          nextTab = tabs[0];
          break;
        case 'End':
          nextTab = tabs[tabs.length - 1];
          break;
        default:
          return;
      }

      e.preventDefault();
      nextTab.focus();
      nextTab.closest('.nav-item')?.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
    });

    DocumentService.ready().then(() => {
      Tab.initialize();
    });
  }

  public static initialize(scope: ParentNode = document): void {
    for (const tabList of scope.querySelectorAll<HTMLElement>('[role="tablist"]')) {
      for (const element of tabList.querySelectorAll<HTMLElement>('[role="tab"], [data-bs-toggle="tab"]')) {
        const tab = Tab.migrate(element);

        const isActive = tab.classList.contains('active');
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');

        const panel = Tab.getPanel(tab);

        if (!tab.id) {
          tab.id = panel ? panel.id + '-tab' : 'typo3-tab-' + (++Tab.idCounter);
        }

        if (panel) {
          tab.setAttribute('aria-controls', panel.id);
          panel.setAttribute('aria-labelledby', tab.id);
          panel.setAttribute('role', 'tabpanel');
        }
      }
    }
  }

  public static show(button: HTMLElement): void {
    const tabList = button.closest('[role="tablist"]') as HTMLElement | null;
    if (!tabList) {
      return;
    }

    Tab.initialize(tabList);

    const targetPane = Tab.getPanel(button);
    if (!targetPane) {
      return;
    }

    if (button.classList.contains('active')) {
      return;
    }

    const tabContent = targetPane.closest('.tab-content') as HTMLElement | null;
    const previousButton = tabList.querySelector<HTMLElement>(':scope .nav-link.active') ?? null;

    // Dispatch typo3:tab:show (before switch, cancelable)
    const showEvent = new TabShowEvent(previousButton);
    button.dispatchEvent(showEvent);
    if (showEvent.defaultPrevented) {
      return;
    }

    // Deactivate current active link in same tablist
    if (tabList) {
      for (const link of tabList.querySelectorAll<HTMLElement>('.nav-link.active')) {
        link.classList.remove('active');
        link.setAttribute('aria-selected', 'false');
        link.setAttribute('tabindex', '-1');
      }
    }

    // Deactivate current active pane in same tab-content
    if (tabContent) {
      for (const pane of tabContent.querySelectorAll<HTMLElement>(':scope > .tab-pane.active')) {
        pane.classList.remove('active');
      }
    }

    // Activate new
    button.classList.add('active');
    button.setAttribute('aria-selected', 'true');
    button.setAttribute('tabindex', '0');
    targetPane.classList.add('active');

    // Dispatch typo3:tab:shown (after switch)
    button.dispatchEvent(new TabShownEvent(previousButton));
  }

  private static getTargetIdentifier(button: HTMLElement): string | null {
    return button.dataset.typo3Tab?.replace('#', '') || null;
  }

  private static getPanel(button: HTMLElement): HTMLElement | null {
    const id = Tab.getTargetIdentifier(button);
    return id ? document.getElementById(id) : null;
  }

  private static migrate(trigger: HTMLElement): HTMLElement {
    if (trigger.dataset.typo3Tab) {
      return trigger;
    }

    const target = trigger.dataset.bsTarget
      || (trigger instanceof HTMLAnchorElement ? trigger.getAttribute('href') : '')
      || '';

    // Do not migrate anchors whose href is a real URL (not a #fragment).
    // These are navigation links (e.g. LinkBrowser tabs), not content-switching tabs.
    if (trigger instanceof HTMLAnchorElement && !target.startsWith('#')) {
      return trigger;
    }

    if (trigger instanceof HTMLAnchorElement) {
      const button = document.createElement('button');
      button.type = 'button';
      for (const attr of trigger.attributes) {
        if (!['href', 'data-bs-toggle', 'data-bs-target'].includes(attr.name)) {
          button.setAttribute(attr.name, attr.value);
        }
      }
      button.innerHTML = trigger.innerHTML;
      button.setAttribute('data-typo3-tab', target);
      trigger.replaceWith(button);
      return button;
    }

    trigger.setAttribute('data-typo3-tab', target);
    trigger.removeAttribute('data-bs-toggle');
    trigger.removeAttribute('data-bs-target');
    return trigger;
  }

}

/**
 * Wraps `.nav-tabs` in a scroller container with start/end scroll buttons.
 */
class TabScroller {
  constructor(navTabs: HTMLElement) {
    const wrapper = document.createElement('typo3-backend-tab-scroller');
    if (!(navTabs.parentNode instanceof TabScrollerElement)) {
      navTabs.parentNode!.insertBefore(wrapper, navTabs);
      wrapper.appendChild(navTabs);
    }
  }
}

/**
 * Scrollable nav-tabs with arrow indicators, inspired by Material UI.
 */
@customElement('typo3-backend-tab-scroller')
export class TabScrollerElement extends LitElement {
  private startButton: HTMLButtonElement;
  private endButton: HTMLButtonElement;
  private navTabs: HTMLElement|null = null;

  constructor() {
    super();

    this.addEventListener(TabShownEvent.eventName, (e: Event): void => {
      (e.target as HTMLElement).closest('.nav-item')?.scrollIntoView({ container: 'nearest', behavior: 'smooth', inline: 'nearest', block: 'nearest' });
    });

    new ResizeObserver((): void => {
      this.scrollActiveLinkIntoView();
      this.updateArrows();
    }).observe(this);
  }

  public override connectedCallback(): void {
    super.connectedCallback();
    this.createButtons();
    this.initializeTabs();
    this.updateArrows();
  }

  protected override render(): TemplateResult {
    return html`
      <slot name="start-button"></slot>
      <slot @slotchange=${this.initializeTabs}></slot>
      <slot name="end-button"></slot>
    `;
  }

  private createButtons(): void {
    this.startButton = document.createElement('button');
    this.startButton.slot = 'start-button';
    this.startButton.type = 'button';
    this.startButton.className = 'nav-tabs-scroll nav-tabs-scroll-start';
    this.startButton.hidden = true;
    this.startButton.setAttribute('aria-hidden', 'true');
    this.startButton.setAttribute('tabindex', '-1');
    this.startButton.innerHTML = '<typo3-backend-icon identifier="actions-chevron-left" size="small"></typo3-backend-icon>';

    this.endButton = document.createElement('button');
    this.endButton.slot = 'end-button';
    this.endButton.type = 'button';
    this.endButton.className = 'nav-tabs-scroll nav-tabs-scroll-end';
    this.endButton.hidden = true;
    this.endButton.setAttribute('aria-hidden', 'true');
    this.endButton.setAttribute('tabindex', '-1');
    this.endButton.innerHTML = '<typo3-backend-icon identifier="actions-chevron-right" size="small"></typo3-backend-icon>';

    this.startButton.addEventListener('click', (): void => {
      const { navTabs } = this;
      if (navTabs === null) {
        return;
      }
      const rtl = getComputedStyle(navTabs).direction === 'rtl';
      navTabs.scrollBy({ left: (rtl ? 1 : -1) * navTabs.clientWidth * 0.75, behavior: 'smooth' });
    });

    this.endButton.addEventListener('click', (): void => {
      const { navTabs } = this;
      if (navTabs === null) {
        return;
      }
      const rtl = getComputedStyle(navTabs).direction === 'rtl';
      navTabs.scrollBy({ left: (rtl ? -1 : 1) * navTabs.clientWidth * 0.75, behavior: 'smooth' });
    });

    this.appendChild(this.startButton);
    this.appendChild(this.endButton);
  }

  private readonly handleScroll = (): void => {
    this.updateArrows();
  };

  private initializeTabs(): void {
    const navTabs = this.querySelector<HTMLElement>('.nav-tabs');
    if (navTabs === null) {
      this.navTabs?.removeEventListener('scroll', this.handleScroll);
      this.navTabs = null;
      return;
    }
    if (this.navTabs === navTabs) {
      return;
    }
    navTabs.addEventListener('scroll', this.handleScroll, { passive: true });
    this.scrollActiveLinkIntoView();
    this.navTabs = navTabs;
  }

  private scrollActiveLinkIntoView(): void {
    const activeLink = this.querySelector<HTMLElement>('.nav-link.active');
    if (activeLink) {
      activeLink.closest('.nav-item')?.scrollIntoView({ container: 'nearest', behavior: 'instant', inline: 'nearest', block: 'nearest' });
    }
  }

  private updateArrows(): void {
    const { navTabs } = this;
    if (navTabs === null) {
      return;
    }
    const { scrollLeft, scrollWidth, clientWidth } = navTabs;
    const isOverflowing = scrollWidth > clientWidth;
    const isRtl = getComputedStyle(navTabs).direction === 'rtl';

    const atStart = isRtl ? scrollLeft >= 0 : scrollLeft <= 0;
    const atEnd = isRtl
      ? scrollLeft <= -(scrollWidth - clientWidth)
      : scrollLeft >= scrollWidth - clientWidth - 1;

    this.startButton.hidden = !isOverflowing || atStart;
    this.endButton.hidden = !isOverflowing || atEnd;
  }
}

/**
 * Persists the last active tab per container in the browser session
 * and restores it on page load.
 */
class TabStorage {
  constructor() {
    DocumentService.ready().then(() => {
      document.querySelectorAll<HTMLElement>('[data-store-last-tab]').forEach((tabContainer) => {
        this.restore(tabContainer);
        tabContainer.addEventListener(TabShowEvent.eventName, (e: Event): void => {
          this.store(e.currentTarget as HTMLElement, e.target as HTMLElement);
        });
      });
    });

    Client.unsetByPrefix('tabs-');
  }

  private restore(tabContainer: HTMLElement): void {
    const storedTab = BrowserSession.get(tabContainer.id);
    if (storedTab) {
      const tabButton = tabContainer.querySelector<HTMLElement>('[data-typo3-tab="#' + storedTab + '"]');
      if (tabButton) {
        Tab.show(tabButton);
      }
    }
  }

  private store(tabContainer: HTMLElement, trigger: HTMLElement): void {
    const tabTarget = trigger.dataset.typo3Tab?.replace('#', '') ?? '';
    BrowserSession.set(tabContainer.id, tabTarget);
  }
}

new Tab();
new TabStorage();

DocumentService.ready().then(() => {
  document.querySelectorAll<HTMLElement>('.nav-tabs')
    .forEach((el) => new TabScroller(el));
});

declare global {
  interface ScrollIntoViewOptions extends ScrollOptions {
    container?: ScrollLogicalPosition;
  }
}
