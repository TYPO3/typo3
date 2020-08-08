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

import 'bootstrap';
import $ from 'jquery';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {AbstractAction} from './ActionButton/AbstractAction';
import {ModalResponseEvent} from 'TYPO3/CMS/Backend/ModalInterface';
import {SeverityEnum} from './Enum/Severity';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');
import Icons = require('./Icons');
import Severity = require('./Severity');

enum Identifiers {
  modal = '.t3js-modal',
  content = '.t3js-modal-content',
  title = '.t3js-modal-title',
  close = '.t3js-modal-close',
  body = '.t3js-modal-body',
  footer = '.t3js-modal-footer',
  iframe = '.t3js-modal-iframe',
  iconPlaceholder = '.t3js-modal-icon-placeholder',
}

enum Sizes {
  small = 'small',
  default = 'default',
  medium = 'medium',
  large = 'large',
  full = 'full',
}

enum Styles {
  default = 'default',
  light = 'light',
  dark = 'dark',
}

enum Types {
  default = 'default',
  ajax = 'ajax',
  iframe = 'iframe',
}

interface Button {
  text: string;
  active: boolean;
  btnClass: string;
  name: string;
  trigger: (e: JQueryEventObject) => {};
  dataAttributes: { [key: string]: string };
  icon: string;
  action: AbstractAction;
}

interface Configuration {
  type: Types;
  title: string;
  content: string | JQuery;
  severity: SeverityEnum;
  buttons: Array<Button>;
  style: string;
  size: string;
  additionalCssClasses: Array<string>;
  callback: Function;
  ajaxCallback: Function;
  ajaxTarget: string;
}

/**
 * Module: TYPO3/CMS/Backend/Modal
 * API for modal windows powered by Twitter Bootstrap.
 */
class Modal {
  public readonly sizes: any = Sizes;
  public readonly styles: any = Styles;
  public readonly types: any = Types;
  public currentModal: JQuery = null;
  private instances: Array<JQuery> = [];
  private readonly $template: JQuery = $(
    '<div class="t3js-modal modal fade">' +
    '<div class="modal-dialog">' +
    '<div class="t3js-modal-content modal-content">' +
    '<div class="modal-header">' +
    '<button class="t3js-modal-close close">' +
    '<span aria-hidden="true">' +
    '<span class="t3js-modal-icon-placeholder" data-icon="actions-close"></span>' +
    '</span>' +
    '<span class="sr-only"></span>' +
    '</button>' +
    '<h4 class="t3js-modal-title modal-title"></h4>' +
    '</div>' +
    '<div class="t3js-modal-body modal-body"></div>' +
    '<div class="t3js-modal-footer modal-footer"></div>' +
    '</div>' +
    '</div>' +
    '</div>',
  );

  private defaultConfiguration: Configuration = {
    type: Types.default,
    title: 'Information',
    content: 'No content provided, please check your <code>Modal</code> configuration.',
    severity: SeverityEnum.notice,
    buttons: [],
    style: Styles.default,
    size: Sizes.default,
    additionalCssClasses: [],
    callback: $.noop(),
    ajaxCallback: $.noop(),
    ajaxTarget: null,
  };

  private readonly securityUtility: SecurityUtility;

  private static resolveEventNameTargetElement(evt: Event): HTMLElement | null {
    const target = evt.target as HTMLElement;
    const currentTarget = evt.currentTarget as HTMLElement;
    if (target.dataset && target.dataset.eventName) {
      return target;
    } else if (currentTarget.dataset && currentTarget.dataset.eventName) {
      return currentTarget;
    }
    return null;
  }

  private static createModalResponseEventFromElement(element: HTMLElement, result: boolean): ModalResponseEvent | null {
    if (!element || !element.dataset.eventName) {
      return null;
    }
    return new CustomEvent(
      element.dataset.eventName, {
        bubbles: true,
        detail: { result, payload: element.dataset.eventPayload || null }
      });
  }

  constructor(securityUtility: SecurityUtility) {
    this.securityUtility = securityUtility;
    $(document).on('modal-dismiss', this.dismiss);
    this.initializeMarkupTrigger(document);
  }

  /**
   * Close the current open modal
   */
  public dismiss(): void {
    if (this.currentModal) {
      this.currentModal.modal('hide');
    }
  }

  /**
   * Shows a confirmation dialog
   * Events:
   * - button.clicked
   * - confirm.button.cancel
   * - confirm.button.ok
   *
   * @param {string} title The title for the confirm modal
   * @param {string | JQuery} content The content for the conform modal, e.g. the main question
   * @param {SeverityEnum} severity Default SeverityEnum.warning
   * @param {Array<Button>} buttons An array with buttons, default no buttons
   * @param {Array<string>} additionalCssClasses Additional css classes to add to the modal
   * @returns {JQuery}
   */
  public confirm(
    title: string,
    content: string | JQuery,
    severity: SeverityEnum = SeverityEnum.warning,
    buttons: Array<Object> = [],
    additionalCssClasses?: Array<string>,
  ): JQuery {
    if (buttons.length === 0) {
      buttons.push(
        {
          text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(severity),
          name: 'ok',
        },
      );
    }

    return this.advanced({
      title,
      content,
      severity,
      buttons,
      additionalCssClasses,
      callback: (currentModal: JQuery): void => {
        currentModal.on('button.clicked', (e: JQueryEventObject): void => {
          if (e.target.getAttribute('name') === 'cancel') {
            $(e.currentTarget).trigger('confirm.button.cancel');
          } else if (e.target.getAttribute('name') === 'ok') {
            $(e.currentTarget).trigger('confirm.button.ok');
          }
        });
      },
    });
  }

  /**
   * Load URL with AJAX, append the content to the modal-body
   * and trigger the callback
   *
   * @param {string} title
   * @param {SeverityEnum} severity
   * @param {Array<Button>} buttons
   * @param {string} url
   * @param {Function} callback
   * @param {string} target
   * @returns {JQuery}
   */
  public loadUrl(
    title: string,
    severity: SeverityEnum = SeverityEnum.info,
    buttons: Array<Object>,
    url: string,
    callback?: Function,
    target?: string,
  ): JQuery {
    return this.advanced({
      type: Types.ajax,
      title,
      severity,
      buttons,
      ajaxCallback: callback,
      ajaxTarget: target,
      content: url,
    });
  }

  /**
   * Shows a dialog
   *
   * @param {string} title
   * @param {string | JQuery} content
   * @param {number} severity
   * @param {Array<Object>} buttons
   * @param {Array<string>} additionalCssClasses
   * @returns {JQuery}
   */
  public show(
    title: string,
    content: string | JQuery,
    severity: SeverityEnum = SeverityEnum.info,
    buttons?: Array<Object>,
    additionalCssClasses?: Array<string>,
  ): JQuery {
    return this.advanced({
      type: Types.default,
      title,
      content,
      severity,
      buttons,
      additionalCssClasses,
    });
  }

  /**
   * Loads modal by configuration
   *
   * @param {object} configuration configuration for the modal
   */
  public advanced(configuration: { [key: string]: any }): JQuery {
    // Validation of configuration
    configuration.type = typeof configuration.type === 'string' && configuration.type in Types
      ? configuration.type
      : this.defaultConfiguration.type;
    configuration.title = typeof configuration.title === 'string'
      ? configuration.title
      : this.defaultConfiguration.title;
    configuration.content = typeof configuration.content === 'string' || typeof configuration.content === 'object'
      ? configuration.content
      : this.defaultConfiguration.content;
    configuration.severity = typeof configuration.severity !== 'undefined'
      ? configuration.severity
      : this.defaultConfiguration.severity;
    configuration.buttons = <Array<Button>>configuration.buttons || this.defaultConfiguration.buttons;
    configuration.size = typeof configuration.size === 'string' && configuration.size in Sizes
      ? configuration.size
      : this.defaultConfiguration.size;
    configuration.style = typeof configuration.style === 'string' && configuration.style in Styles
      ? configuration.style
      : this.defaultConfiguration.style;
    configuration.additionalCssClasses = configuration.additionalCssClasses || this.defaultConfiguration.additionalCssClasses;
    configuration.callback = typeof configuration.callback === 'function' ? configuration.callback : this.defaultConfiguration.callback;
    configuration.ajaxCallback = typeof configuration.ajaxCallback === 'function'
      ? configuration.ajaxCallback
      : this.defaultConfiguration.ajaxCallback;
    configuration.ajaxTarget = typeof configuration.ajaxTarget === 'string'
      ? configuration.ajaxTarget
      : this.defaultConfiguration.ajaxTarget;

    return this.generate(<Configuration>configuration);
  }

  /**
   * Sets action buttons for the modal window or removed the footer, if no buttons are given.
   *
   * @param {Array<Button>} buttons
   */
  public setButtons(buttons: Array<Button>): JQuery {
    const modalFooter = this.currentModal.find(Identifiers.footer);
    if (buttons.length > 0) {
      modalFooter.empty();

      for (let i = 0; i < buttons.length; i++) {
        const button = buttons[i];
        const $button = $('<button />', {'class': 'btn'});
        $button.html('<span>' + this.securityUtility.encodeHtml(button.text, false) + '</span>');
        if (button.active) {
          $button.addClass('t3js-active');
        }
        if (button.btnClass !== '') {
          $button.addClass(button.btnClass);
        }
        if (button.name !== '') {
          $button.attr('name', button.name);
        }
        if (button.action) {
          $button.on('click', (): void => {
            modalFooter.find('button').not($button).addClass('disabled');
            button.action.execute($button.get(0)).then((): void => {
              this.currentModal.modal('hide');
            });
          });
        } else if (button.trigger) {
          $button.on('click', button.trigger);
        }
        if (button.dataAttributes) {
          if (Object.keys(button.dataAttributes).length > 0) {
            Object.keys(button.dataAttributes).map((value: string): any => {
              $button.attr('data-' + value, button.dataAttributes[value]);
            });
          }
        }
        if (button.icon) {
          $button.prepend('<span class="t3js-modal-icon-placeholder" data-icon="' + button.icon + '"></span>');
        }
        modalFooter.append($button);
      }
      modalFooter.show();
      modalFooter.find('button')
        .on('click', (e: JQueryEventObject): void => {
          $(e.currentTarget).trigger('button.clicked');
        });
    } else {
      modalFooter.hide();
    }

    return this.currentModal;
  }

  /**
   * Initialize markup with data attributes
   *
   * @param {HTMLDocument} theDocument
   */
  private initializeMarkupTrigger(theDocument: HTMLDocument): void {
    $(theDocument).on('click', '.t3js-modal-trigger', (evt: JQueryEventObject): void => {
      evt.preventDefault();
      const $element = $(evt.currentTarget);
      const content = $element.data('content') || 'Are you sure?';
      const severity = typeof SeverityEnum[$element.data('severity')] !== 'undefined'
        ? SeverityEnum[$element.data('severity')]
        : SeverityEnum.info;
      let url = $element.data('url') || null;
      if (url !== null) {
        const separator = url.includes('?') ? '&' : '?';
        const params = $.param({data: $element.data()});
        url = url + separator + params;
      }
      this.advanced({
        type: url !== null ? Types.ajax : Types.default,
        title: $element.data('title') || 'Alert',
        content: url !== null ? url : content,
        severity,
        buttons: [
          {
            text: $element.data('button-close-text') || TYPO3.lang['button.close'] || 'Close',
            active: true,
            btnClass: 'btn-default',
            trigger: (): void => {
              this.currentModal.trigger('modal-dismiss');
              const eventNameTarget = Modal.resolveEventNameTargetElement(evt);
              const event = Modal.createModalResponseEventFromElement(eventNameTarget, false);
              if (event !== null) {
                // dispatch event at the element having `data-event-name` declared
                eventNameTarget.dispatchEvent(event);
              }
            },
          },
          {
            text: $element.data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
            btnClass: 'btn-' + Severity.getCssClass(severity),
            trigger: (): void => {
              this.currentModal.trigger('modal-dismiss');
              const eventNameTarget = Modal.resolveEventNameTargetElement(evt);
              const event = Modal.createModalResponseEventFromElement(eventNameTarget, true);
              if (event !== null) {
                // dispatch event at the element having `data-event-name` declared
                eventNameTarget.dispatchEvent(event);
              }
              const href = $element.data('href') || $element.attr('href');
              if (href && href !== '#') {
                evt.target.ownerDocument.location.href = href;
              }
            },
          },
        ],
      });
    });
  }

  /**
   * @param {Configuration} configuration
   */
  private generate(configuration: Configuration): JQuery {
    const currentModal = this.$template.clone();
    if (configuration.additionalCssClasses.length > 0) {
      for (let additionalClass of configuration.additionalCssClasses) {
        currentModal.addClass(additionalClass);
      }
    }
    currentModal.addClass('modal-type-' + configuration.type);
    currentModal.addClass('modal-severity-' + Severity.getCssClass(configuration.severity));
    currentModal.addClass('modal-style-' + configuration.style);
    currentModal.addClass('modal-size-' + configuration.size);
    currentModal.attr('tabindex', '-1');
    currentModal.find(Identifiers.title).text(configuration.title);
    currentModal.find(Identifiers.close).on('click', (): void => {
      currentModal.modal('hide');
    });

    if (configuration.type === 'ajax') {
      const contentTarget = configuration.ajaxTarget ? configuration.ajaxTarget : Identifiers.body;
      const $loaderTarget = currentModal.find(contentTarget);
      Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
        $loaderTarget.html('<div class="modal-loading">' + icon + '</div>');
        new AjaxRequest(configuration.content as string).get().then(async (response: AjaxResponse): Promise<void> => {
          this.currentModal.find(contentTarget)
            .empty()
            .append(await response.raw().text());
          if (configuration.ajaxCallback) {
            configuration.ajaxCallback();
          }
          this.currentModal.trigger('modal-loaded');
        });
      });
    } else if (configuration.type === 'iframe') {
      currentModal.find(Identifiers.body).append(
        $('<iframe />', {
          src: configuration.content,
          'name': 'modal_frame',
          'class': 'modal-iframe t3js-modal-iframe',
        }),
      );
      currentModal.find(Identifiers.iframe).on('load', (): void => {
        currentModal.find(Identifiers.title).text(
          (<HTMLIFrameElement>currentModal.find(Identifiers.iframe).get(0)).contentDocument.title,
        );
      });
    } else {
      if (typeof configuration.content === 'string') {
        configuration.content = $('<p />').html(
          this.securityUtility.encodeHtml(configuration.content),
        );
      }
      currentModal.find(Identifiers.body).append(configuration.content);
    }

    currentModal.on('shown.bs.modal', (e: JQueryEventObject): void => {
      const $me = $(e.currentTarget);
      // focus the button which was configured as active button
      $me.find(Identifiers.footer).find('.t3js-active').first().focus();
      // Get Icons
      $me.find(Identifiers.iconPlaceholder).each((index: number, elem: Element): void => {
        Icons.getIcon($(elem).data('icon'), Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).then((icon: string): void => {
          this.currentModal.find(Identifiers.iconPlaceholder + '[data-icon=' + $(icon).data('identifier') + ']').replaceWith(icon);
        });
      });
    });

    // Remove modal from Modal.instances when hidden
    currentModal.on('hidden.bs.modal', (e: JQueryEventObject): void => {
      if (this.instances.length > 0) {
        const lastIndex = this.instances.length - 1;
        this.instances.splice(lastIndex, 1);
        this.currentModal = this.instances[lastIndex - 1];
      }
      currentModal.trigger('modal-destroyed');
      $(e.currentTarget).remove();
      // Keep class modal-open on body tag as long as open modals exist
      if (this.instances.length > 0) {
        $('body').addClass('modal-open');
      }
    });

    // When modal is opened/shown add it to Modal.instances and make it Modal.currentModal
    currentModal.on('show.bs.modal', (e: JQueryEventObject): void => {
      this.currentModal = $(e.currentTarget);
      // Add buttons
      this.setButtons(configuration.buttons);
      this.instances.push(this.currentModal);
    });
    currentModal.on('modal-dismiss', (e: JQueryEventObject): void => {
      // Hide modal, the bs.modal events will clean up Modal.instances
      $(e.currentTarget).modal('hide');
    });

    if (configuration.callback) {
      configuration.callback(currentModal);
    }

    return currentModal.modal();
  }
}

let modalObject: Modal = null;
try {
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.Modal) {
    // fetch from parent
    // we need to trigger the event capturing again, in order to make sure this works inside iframes
    parent.window.TYPO3.Modal.initializeMarkupTrigger(document);
    modalObject = parent.window.TYPO3.Modal;
  } else if (top && top.TYPO3.Modal) {
    // fetch object from outer frame
    // we need to trigger the event capturing again, in order to make sure this works inside iframes
    top.TYPO3.Modal.initializeMarkupTrigger(document);
    modalObject = top.TYPO3.Modal;
  }
} catch {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!modalObject) {
  modalObject = new Modal(new SecurityUtility());

  // expose as global object
  TYPO3.Modal = modalObject;
}

export = modalObject;
