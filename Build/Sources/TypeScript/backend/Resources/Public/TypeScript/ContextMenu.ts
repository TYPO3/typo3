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

import $ from 'jquery';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import ContextMenuActions = require('./ContextMenuActions');
import ThrottleEvent = require('TYPO3/CMS/Core/Event/ThrottleEvent');

interface MousePosition {
  X: number;
  Y: number;
}

interface ActiveRecord {
  uid: number|string;
  table: string;
}

interface MenuItem {
  type: string;
  icon: string;
  label: string;
  additionalAttributes?: { [key: string]: string };
  childItems?: MenuItems;
  callbackAction?: string;
}

interface MenuItems {
  [key: string]: MenuItem;
}

/**
 * Module: TYPO3/CMS/Backend/ContextMenu
 * Container used to load the context menu via AJAX to render the result in a layer next to the mouse cursor
 */
class ContextMenu {
  private mousePos: MousePosition = {X: null, Y: null};

  /**
   * If this.delayContextMenuHide is set to true, any parent context menu will stay visibile even if the cursor is out
   * of its boundaries.
   */
  private delayContextMenuHide: boolean = false;
  private record: ActiveRecord = {uid: null, table: null};
  private eventSources: Element[] = [];
  private closeMenuTimeout: { [key: string]: number } = {};

  /**
   * @param {MenuItem} item
   * @returns {string}
   */
  private static drawActionItem(item: MenuItem): string {
    const attributes: { [key: string]: string } = item.additionalAttributes || {};
    let attributesString = '';
    for (const attribute of Object.entries(attributes)) {
      const [k, v] = attribute;
      attributesString += ' ' + k + '="' + v + '"';
    }

    return '<li role="menuitem" class="list-group-item" tabindex="-1"'
      + ' data-callback-action="' + item.callbackAction + '"'
      + attributesString + '><span class="list-group-item-icon">' + item.icon + '</span> ' + item.label + '</li>';
  }

  /**
   * @param {JQuery} $element
   * @param {number} x
   * @param {number} y
   * @returns {boolean}
   */
  private static within($element: JQuery, x: number, y: number): boolean {
    const offset = $element.offset();
    return (
      y >= offset.top &&
      y < offset.top + $element.height() &&
      x >= offset.left &&
      x < offset.left + $element.width()
    );
  }

  /**
   * Manipulates the DOM to add the divs needed for context menu the bottom of the <body>-tag
   */
  private static initializeContextMenuContainer(): void {
    if ($('#contentMenu0').length === 0) {
      const code = '<div id="contentMenu0" class="context-menu" style="display: none;"></div>'
        + '<div id="contentMenu1" class="context-menu" style="display: none;"></div>';
      $('body').append(code);
    }
  }

  constructor() {
    $(document).on('click contextmenu', '.t3js-contextmenutrigger', (e: JQueryEventObject): void => {
      const $me = $(e.currentTarget);
      // if there is an other "inline" onclick setting, context menu is not triggered
      // usually this is the case for the foldertree
      if ($me.prop('onclick') && e.type === 'click') {
        return;
      }
      e.preventDefault();
      this.show(
        $me.data('table'),
        $me.data('uid'),
        $me.data('context'),
        $me.data('iteminfo'),
        $me.data('parameters'),
        e.target
      );
    });

    // register mouse movement inside the document
    new ThrottleEvent('mousemove', this.storeMousePositionEvent.bind(this), 50).bindTo(document);
  }

  /**
   * Main function, called from most context menu links
   *
   * @param {string} table Table from where info should be fetched
   * @param {number} uid The UID of the item
   * @param {string} context Context of the item
   * @param {string} enDisItems Items to disable / enable
   * @param {string} addParams Additional params
   * @param {Element} eventSource Source Element
   */
  public show(table: string, uid: number|string, context: string, enDisItems: string, addParams: string, eventSource: Element = null): void {
    this.hideAll();
    this.closeMenuTimeout = {};

    this.record = {table: table, uid: uid};
    // fix: [tabindex=-1] is not focusable!!!
    const focusableSource = eventSource.matches('a, button, [tabindex]') ? eventSource : eventSource.closest('a, button, [tabindex]');
    this.eventSources.push(focusableSource);

    let parameters = '';

    if (typeof table !== 'undefined') {
      parameters += 'table=' + encodeURIComponent(table);
    }
    if (typeof uid !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'uid=' + uid;
    }
    if (typeof context !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'context=' + context;
    }
    if (typeof enDisItems !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'enDisItems=' + enDisItems;
    }
    if (typeof addParams !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'addParams=' + addParams;
    }
    this.fetch(parameters);
  }

  /**
   * Make the AJAX request
   *
   * @param {string} parameters Parameters sent to the server
   */
  private fetch(parameters: string): void {
    const url = TYPO3.settings.ajaxUrls.contextmenu;
    (new AjaxRequest(url)).withQueryArguments(parameters).get().then(async (response: AjaxResponse): Promise<any> => {
      const data: MenuItems = await response.resolve();
      if (typeof response !== 'undefined' && Object.keys(response).length > 0) {
        this.populateData(data, 0);
      }
    });
  }

  /**
   * Fills the context menu with content and displays it correctly
   * depending on the mouse position
   *
   * @param {MenuItems} items The data that will be put in the menu
   * @param {number} level The depth of the context menu
   */
  private populateData(items: MenuItems, level: number): void {
    ContextMenu.initializeContextMenuContainer();

    const $obj = $('#contentMenu' + level);

    if ($obj.length && (level === 0 || $('#contentMenu' + (level - 1)).is(':visible'))) {
      const elements = this.drawMenu(items, level);
      $obj.html('<ul class="list-group" role="menu">' + elements + '</ul>');

      $('li.list-group-item', $obj).on('click', (event: JQueryEventObject): void => {
        event.preventDefault();
        const $me = $(event.currentTarget);

        if ($me.hasClass('list-group-item-submenu')) {
          this.openSubmenu(level, $me, false);
          return;
        }

        const callbackName = $me.data('callback-action');
        const callbackModule = $me.data('callback-module');
        if ($me.data('callback-module')) {
          require([callbackModule], (callbackModuleCallback: any): void => {
            callbackModuleCallback[callbackName].bind($me)(this.record.table, this.record.uid);
          });
        } else if (ContextMenuActions && typeof (ContextMenuActions as any)[callbackName] === 'function') {
          (ContextMenuActions as any)[callbackName].bind($me)(this.record.table, this.record.uid);
        } else {
          console.log('action: ' + callbackName + ' not found');
        }
        this.hideAll();
      });
      $('li.list-group-item', $obj).on('keydown', (event: JQueryEventObject): void => {
        const $currentItem = $(event.currentTarget);
        switch (event.key) {
          case 'Down': // IE/Edge specific value
          case 'ArrowDown':
            this.setFocusToNextItem($currentItem.get(0));
            break;
          case 'Up': // IE/Edge specific value
          case 'ArrowUp':
            this.setFocusToPreviousItem($currentItem.get(0));
            break;
          case 'Right': // IE/Edge specific value
          case 'ArrowRight':
            if ($currentItem.hasClass('list-group-item-submenu')) {
              this.openSubmenu(level, $currentItem, true);
            } else {
              return; // allow default behaviour of right key
            }
            break;
          case 'Home':
            this.setFocusToFirstItem($currentItem.get(0));
            break;
          case 'End':
            this.setFocusToLastItem($currentItem.get(0));
            break;
          case 'Enter':
          case 'Space':
            $currentItem.click();
            break;
          case 'Esc': // IE/Edge specific value
          case 'Escape':
          case 'Left': // IE/Edge specific value
          case 'ArrowLeft':
            this.hide('#' + $currentItem.parents('.context-menu').first().attr('id'));
            break;
          case 'Tab':
            this.hideAll();
            break;
          default:
            return; // return to allow default keypress behaviour
        }
        // if not returned yet, prevent the default action of the event.
        event.preventDefault();
      });
      $obj.css(this.getPosition($obj, false)).show();
      // focus the first element on creation to enable keyboard shortcuts
      $('li.list-group-item[tabindex=-1]', $obj).first().focus();
    }
  }

  private setFocusToPreviousItem(currentItem: HTMLElement): void {
    let previousItem = this.getItemBackward(currentItem.previousElementSibling);
    if (!previousItem) {
      previousItem = this.getLastItem(currentItem);
    }
    previousItem.focus();
  }

  private setFocusToNextItem(currentItem: HTMLElement): void {
    let nextItem = this.getItemForward(currentItem.nextElementSibling);
    if (!nextItem) {
      nextItem = this.getFirstItem(currentItem);
    }
    nextItem.focus();
  }

  private setFocusToFirstItem(currentItem: HTMLElement): void {
    let firstItem = this.getFirstItem(currentItem);
    if (firstItem) {
      firstItem.focus();
    }
  }

  private setFocusToLastItem(currentItem: HTMLElement): void {
    let lastItem = this.getLastItem(currentItem);
    if (lastItem) {
      lastItem.focus();
    }
  }

  /**
   * Returns passed element if it is a menu item, if not checks the previous elements until one is found.
   */
  private getItemBackward(element: Element): HTMLElement | null {
    while (element &&
      (!element.classList.contains('list-group-item') || (element.getAttribute('tabindex') !== '-1'))) {
      element = element.previousElementSibling;
    }
    return <HTMLElement>element;
  }

  /**
   * Returns passed element if it is a menu item, if not checks the previous elements until one is found.
   */
  private getItemForward(item: Element): HTMLElement | null {
    while (item &&
      (!item.classList.contains('list-group-item') || (item.getAttribute('tabindex') !== '-1'))) {
      item = item.nextElementSibling;
    }
    return <HTMLElement>item;
  }

  private getFirstItem(item: Element): HTMLElement | null {
    return this.getItemForward(item.parentElement.firstElementChild);
  }

  private getLastItem(item: Element): HTMLElement | null {
    return this.getItemBackward(item.parentElement.lastElementChild);
  }

  /**
   * @param {number} level
   * @param {JQuery} $item
   * @param {boolean} keyboard
   */
  private openSubmenu(level: number, $item: JQuery, keyboard: boolean): void {
    this.eventSources.push($item[0]);
    const $obj = $('#contentMenu' + (level + 1)).html('');
    $item.next().find('.list-group').clone(true).appendTo($obj);
    $obj.css(this.getPosition($obj, keyboard)).show();
    $('.list-group-item[tabindex=-1]',$obj).first().focus();
  }

  private getPosition($obj: JQuery, keyboard: boolean): {[key: string]: string} {
    let x = 0, y = 0;
    if (this.eventSources.length && (this.mousePos.X === null || keyboard)) {
      const boundingRect = this.eventSources[this.eventSources.length - 1].getBoundingClientRect();
      x = this.eventSources.length > 1 ? boundingRect.right : boundingRect.x;
      y = boundingRect.y;
    } else {
      x = this.mousePos.X;
      y = this.mousePos.Y;
    }
    const dimsWindow = {
      width: $(window).width() - 20, // saving margin for scrollbars
      height: $(window).height(),
    };

    // dimensions for the context menu
    const dims = {
      width: $obj.width(),
      height: $obj.height(),
    };

    const relative = {
      X: x - $(document).scrollLeft(),
      Y: y - $(document).scrollTop(),
    };

    // adjusting the Y position of the layer to fit it into the window frame
    // if there is enough space above then put it upwards,
    // otherwise adjust it to the bottom of the window
    if (dimsWindow.height - dims.height < relative.Y) {
      if (relative.Y > dims.height) {
        y -= (dims.height - 10);
      } else {
        y += (dimsWindow.height - dims.height - relative.Y);
      }
    }
    // adjusting the X position like Y above, but align it to the left side of the viewport if it does not fit completely
    if (dimsWindow.width - dims.width < relative.X) {
      if (relative.X > dims.width) {
        x -= (dims.width - 10);
      } else if ((dimsWindow.width - dims.width - relative.X) < $(document).scrollLeft()) {
        x = $(document).scrollLeft();
      } else {
        x += (dimsWindow.width - dims.width - relative.X);
      }
    }

    return {left: x + 'px', top: y + 'px'};
  }

  /**
   * fills the context menu with content and displays it correctly
   * depending on the mouse position
   *
   * @param {MenuItems} items The data that will be put in the menu
   * @param {Number} level The depth of the context menu
   * @return {string}
   */
  private drawMenu(items: MenuItems, level: number): string {
    let elements: string = '';
    for (const item of Object.values(items)) {
      if (item.type === 'item') {
        elements += ContextMenu.drawActionItem(item);
      } else if (item.type === 'divider') {
        elements += '<li role="separator" class="list-group-item list-group-item-divider"></li>';
      } else if (item.type === 'submenu' || item.childItems) {
        elements += '<li role="menuitem" aria-haspopup="true" class="list-group-item list-group-item-submenu" tabindex="-1">'
          + '<span class="list-group-item-icon">' + item.icon + '</span> '
          + item.label + '&nbsp;&nbsp;<span class="fa fa-caret-right"></span>'
          + '</li>';

        const childElements = this.drawMenu(item.childItems, 1);
        elements += '<div class="context-menu contentMenu' + (level + 1) + '" style="display:none;">'
          + '<ul role="menu" class="list-group">' + childElements + '</ul>'
          + '</div>';
      }
    }
    return elements;
  }

  /**
   * event handler function that saves the
   * actual position of the mouse
   * in the context menu object
   *
   * @param {JQueryEventObject} event The event object
   */
  private storeMousePositionEvent = (event: JQueryEventObject): void => {
    this.mousePos = {X: event.pageX, Y: event.pageY};
    this.mouseOutFromMenu('#contentMenu0');
    this.mouseOutFromMenu('#contentMenu1');
  }

  /**
   * hides a visible menu if the mouse has moved outside
   * of the object
   *
   * @param {string} obj The identifier of the object to hide
   */
  private mouseOutFromMenu(obj: string): void {
    const $element = $(obj);

    if ($element.length > 0 && $element.is(':visible') && !ContextMenu.within($element, this.mousePos.X, this.mousePos.Y)) {
      this.hide(obj);
    } else if ($element.length > 0 && $element.is(':visible')) {
      this.delayContextMenuHide = true;
      window.clearTimeout(this.closeMenuTimeout[obj]);
    }
  }

  /**
   * @param {string} obj
   * @param {boolean} withDelay
   */
  private hide(obj: string, withDelay: boolean = true): void {
    this.delayContextMenuHide = false;
    window.clearTimeout(this.closeMenuTimeout[obj]);

    const delayHandler = () => {
      if (!this.delayContextMenuHide) {
        $(obj).hide();
        const source = this.eventSources.pop();
        if (source) {
          $(source).focus();
        }
      }
    };

    if (withDelay) {
      this.closeMenuTimeout[obj] = window.setTimeout(delayHandler, 500);
    } else {
      delayHandler();
    }
  }

  /**
   * Hides all context menus
   */
  private hideAll(): void {
    this.hide('#contentMenu0', false);
    this.hide('#contentMenu1', false);
  }
}

export = new ContextMenu();
