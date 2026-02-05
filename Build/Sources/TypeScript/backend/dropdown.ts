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

import DocumentService from '@typo3/core/document-service';

class Dropdown {
  constructor() {
    // Add keyboard navigation bindings when a dropdown menu opens
    document.addEventListener('toggle', (e: ToggleEvent) => {
      const menu = e.target as HTMLElement;
      if (!menu.matches('.dropdown-menu[popover]')) {
        return;
      }

      if (e.newState === 'open') {
        menu.addEventListener('keydown', this.handleKeydown);
        menu.addEventListener('focusout', this.handleFocusout);
      } else {
        menu.removeEventListener('keydown', this.handleKeydown);
        menu.removeEventListener('focusout', this.handleFocusout);
      }
    }, true);

    // Allow opening dropdown and focusing items via arrow keys on top-level trigger buttons.
    // Triggers inside a dropdown menu are handled by handleKeydown (ArrowRight opens submenu).
    document.addEventListener('keydown', (e: KeyboardEvent) => {
      const trigger = (e.target as Element)?.closest<HTMLElement>('.dropdown-toggle[popovertarget]');
      if (!trigger) {
        return;
      }

      // Skip triggers inside an open dropdown menu — those are submenu triggers
      // and are handled by the menu's own keydown handler
      if (trigger.closest('.dropdown-menu')) {
        return;
      }

      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const menuId = trigger.getAttribute('popovertarget');
        const menu = menuId ? document.getElementById(menuId) : null;
        if (!menu?.matches('[popover]')) {
          return;
        }

        e.preventDefault();
        menu.showPopover();

        const items = this.getFocusableItems(menu);
        if (items.length > 0) {
          if (e.key === 'ArrowDown') {
            items[0].focus();
          } else {
            items[items.length - 1].focus();
          }
        }
      }
    });

    // Close all open dropdowns when the window loses focus (e.g. user clicks into an iframe)
    window.addEventListener('blur', () => {
      document.querySelectorAll<HTMLElement>('.dropdown-menu[popover]:popover-open').forEach((menu) => {
        menu.hidePopover();
      });
    });

    // Close dropdown when clicking on items (but not submenu triggers)
    document.addEventListener('click', (e: Event) => {
      const target = e.target as HTMLElement;
      const item = target.closest<HTMLElement>('.dropdown-item');
      if (!item) {
        return;
      }
      // Don't close when clicking a submenu trigger
      if (item.hasAttribute('popovertarget')) {
        return;
      }
      // Close all ancestor popover menus
      let menu = item.closest<HTMLElement>('.dropdown-menu[popover]');
      while (menu) {
        menu.hidePopover();
        menu = menu.closest<HTMLElement>('.dropdown-menu[popover]:popover-open');
      }
    });
  }

  private readonly handleFocusout = (e: FocusEvent): void => {
    const menu = e.currentTarget as HTMLElement;
    const relatedTarget = e.relatedTarget as Element | null;

    // Only close if focus explicitly moved to an element outside the menu.
    // If relatedTarget is null, focus was lost (e.g. element removed via AJAX), not moved intentionally.
    if (relatedTarget === null || menu.contains(relatedTarget)) {
      return;
    }

    // Don't close if focus moved to a child popover triggered from within this menu
    const childMenu = relatedTarget.closest<HTMLElement>('.dropdown-menu[popover]');
    if (childMenu) {
      const triggerId = childMenu.id;
      if (triggerId && menu.querySelector<HTMLElement>(`[popovertarget="${triggerId}"]`)) {
        return;
      }
    }

    menu.hidePopover();
  };

  private getFocusableItems(menu: HTMLElement): HTMLElement[] {
    return Array.from(
      menu.querySelectorAll<HTMLElement>('.dropdown-item:not(:disabled):not(.disabled)')
    ).filter((item) => item.closest('.dropdown-menu') === menu);
  }

  private isRtl(element: HTMLElement): boolean {
    return getComputedStyle(element).direction === 'rtl';
  }

  private readonly handleKeydown = (e: KeyboardEvent): void => {
    const menu = e.currentTarget as HTMLElement;
    const activeElement = document.activeElement as HTMLElement | null;

    // Ignore events bubbling up from a child submenu.
    // If the focused element's closest dropdown-menu is not this menu,
    // the event belongs to a nested submenu and should not be handled here.
    if (activeElement && activeElement.closest('.dropdown-menu') !== menu) {
      return;
    }

    const items = this.getFocusableItems(menu);
    if (items.length === 0) {
      return;
    }

    const currentIndex = items.findIndex(item => item === activeElement);

    // Determine RTL mode for directional navigation
    const isRtl = this.isRtl(menu);
    const shouldOpenSubmenu = (e.key === 'ArrowRight' && !isRtl) || (e.key === 'ArrowLeft' && isRtl);
    const shouldCloseSubmenu = (e.key === 'ArrowLeft' && !isRtl) || (e.key === 'ArrowRight' && isRtl);

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        items[(currentIndex + 1) % items.length].focus();
        break;
      case 'ArrowUp':
        e.preventDefault();
        items[(currentIndex - 1 + items.length) % items.length].focus();
        break;
      case 'ArrowRight':
      case 'ArrowLeft':
        if (shouldOpenSubmenu) {
          // Open submenu if the focused item is a submenu trigger
          if (activeElement?.hasAttribute('popovertarget')) {
            e.preventDefault();
            e.stopPropagation();
            const submenuId = activeElement.getAttribute('popovertarget');
            const submenu = submenuId ? document.getElementById(submenuId) : null;
            if (submenu?.matches('[popover]')) {
              submenu.showPopover();
              const submenuItems = this.getFocusableItems(submenu);
              if (submenuItems.length > 0) {
                submenuItems[0].focus();
              }
            }
          }
        } else if (shouldCloseSubmenu) {
          // Close submenu and return focus to its trigger (only for nested menus)
          const parentTrigger = document.querySelector<HTMLElement>(`[popovertarget="${menu.id}"]`);
          if (parentTrigger?.closest('.dropdown-menu')) {
            e.preventDefault();
            e.stopPropagation();
            menu.hidePopover();
            parentTrigger.focus();
          }
        }
        break;
      case 'Escape': {
        // Close the current menu and return focus to its trigger
        e.preventDefault();
        e.stopPropagation();
        const trigger = document.querySelector<HTMLElement>(`[popovertarget="${menu.id}"]`);
        menu.hidePopover();
        trigger?.focus();
        break;
      }
      case 'Home':
        e.preventDefault();
        items[0].focus();
        break;
      case 'End':
        e.preventDefault();
        items[items.length - 1].focus();
        break;
      default:
        break;
    }
  };
}

new Dropdown();

/**
 * Bootstrap dropdown compatibility layer.
 * Converts data-bs-toggle="dropdown" triggers to use the Popover API.
 */
class DropdownConverter {
  private dropdownIdCounter: number = 0;

  constructor() {
    document.addEventListener('click', (e: Event) => {
      const trigger = (e.target as Element)?.closest<HTMLElement>('[data-bs-toggle="dropdown"]');
      if (trigger) {
        e.preventDefault();
        const menu = this.getMenu(trigger);
        this.convert(trigger);
        menu?.togglePopover();
      }
    });

    DocumentService.ready().then(() => this.convertAll());
  }

  private convertAnchorToButton(anchor: HTMLAnchorElement): HTMLButtonElement {
    const button = document.createElement('button');
    button.type = 'button';

    for (const attr of anchor.attributes) {
      if (attr.name === 'href') {
        const href = attr.value;
        if (href.startsWith('#') && !anchor.hasAttribute('data-bs-target')) {
          button.setAttribute('data-bs-target', href);
        }
      } else {
        button.setAttribute(attr.name, attr.value);
      }
    }

    button.innerHTML = anchor.innerHTML;
    anchor.replaceWith(button);

    return button;
  }

  private getMenu(trigger: HTMLElement): HTMLElement | null {
    const target = trigger.dataset.bsTarget;
    if (target) {
      const selector = target.startsWith('#') ? target : '#' + target;
      return document.querySelector<HTMLElement>(selector);
    }

    const href = trigger.getAttribute('href');
    if (href?.startsWith('#')) {
      return document.querySelector<HTMLElement>(href);
    }

    if (trigger.nextElementSibling?.matches('.dropdown-menu')) {
      return trigger.nextElementSibling as HTMLElement;
    }

    return trigger.closest('.dropdown')?.querySelector<HTMLElement>('.dropdown-menu') ?? null;
  }

  private convert(trigger: HTMLElement): HTMLElement | null {
    if (trigger.hasAttribute('popovertarget')) {
      return null;
    }

    const menu = this.getMenu(trigger);
    if (!menu) {
      return null;
    }

    if (!menu.id) {
      menu.id = 'dropdown-menu-' + this.dropdownIdCounter++;
    }

    menu.setAttribute('popover', '');

    if (trigger.tagName === 'A') {
      trigger = this.convertAnchorToButton(trigger as HTMLAnchorElement);
    }

    if (!trigger.closest('.dropdown')) {
      trigger.parentElement?.classList.add('dropdown');
    }

    trigger.setAttribute('popovertarget', menu.id);
    trigger.removeAttribute('data-bs-toggle');
    trigger.removeAttribute('data-bs-target');
    trigger.removeAttribute('data-bs-offset');
    trigger.removeAttribute('data-bs-auto-close');
    trigger.removeAttribute('data-bs-reference');
    trigger.removeAttribute('data-bs-display');
    trigger.removeAttribute('data-bs-boundary');
    trigger.removeAttribute('aria-haspopup');
    trigger.removeAttribute('aria-expanded');

    return trigger;
  }

  private convertAll(): void {
    document.querySelectorAll<HTMLElement>('[data-bs-toggle="dropdown"]').forEach((trigger) => this.convert(trigger));
  }
}

new DropdownConverter();
