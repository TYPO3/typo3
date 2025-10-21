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

import { html, nothing, LitElement, type TemplateResult, type PropertyValues } from 'lit';
import { customElement, property, state, query } from 'lit/decorators';
import { unsafeHTML } from 'lit/directives/unsafe-html';
import { classMap, type ClassInfo } from 'lit/directives/class-map';
import { ifDefined } from 'lit/directives/if-defined';
import { classesArrayToClassInfo } from '@typo3/core/lit-helper';
import RegularEvent from '@typo3/core/event/regular-event';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { AbstractAction } from './action-button/abstract-action';
import type { ModalResponseEvent } from '@typo3/backend/modal-interface';
import { SeverityEnum } from './enum/severity';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Severity from './severity';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/element/spinner-element';

export enum Identifiers {
  modal = '.t3js-modal',
  header = '.t3js-modal-header',
  close = '.t3js-modal-close',
  body = '.t3js-modal-body',
  footer = '.t3js-modal-footer',
}

export enum Sizes {
  small = 'small',
  default = 'default',
  medium = 'medium',
  large = 'large',
  full = 'full',
}

export enum Styles {
  default = 'default',
  light = 'light',
  dark = 'dark',
}

export enum Types {
  default = 'default',
  template = 'template',
  ajax = 'ajax',
  iframe = 'iframe',
}

type ModalCallbackFunction = (modal: ModalElement) => void;

export interface Button {
  text: string;
  active?: boolean;
  btnClass: string;
  name?: string;
  form?: string;
  trigger?: (e: Event, modal: ModalElement) => void;
  icon?: string;
  action?: AbstractAction;
}

export interface Configuration {
  type: Types;
  title: string;
  // @todo remove support for JQuery based content
  content: TemplateResult | string | JQuery | Element | DocumentFragment;
  severity: SeverityEnum;
  buttons: Array<Button>;
  style: Styles;
  size: Sizes;
  additionalCssClasses: Array<string>;
  callback: ModalCallbackFunction | null;
  ajaxCallback: ModalCallbackFunction | null;
  staticBackdrop: boolean;
  hideCloseButton: boolean;
}

type PartialConfiguration = Partial<Omit<Configuration, 'buttons'> & { buttons: Array<Partial<Button>> }>;

let uniqueIdCounter = 0;

@customElement('typo3-backend-modal')
export class ModalElement extends LitElement {
  @property({ type: String, reflect: true }) modalTitle: string = '';
  @property({ type: String, reflect: true }) content: string = '';
  @property({ type: String, reflect: true }) type: Types = Types.default;
  @property({ type: String, reflect: true }) severity: SeverityEnum = SeverityEnum.notice;
  @property({ type: String, reflect: true }) variant: Styles = Styles.default;
  @property({ type: String, reflect: true }) size: Sizes = Sizes.default;
  @property({ type: Boolean }) staticBackdrop: boolean = false;
  @property({ type: Boolean }) hideCloseButton: boolean = false;
  @property({ type: Array }) additionalCssClasses: Array<string> = [];
  @property({ type: Array, attribute: false }) buttons: Array<Button> = [];

  @state() templateResultContent: TemplateResult | JQuery | Element | DocumentFragment = null;
  @state() activeButton: Button = null;
  @query('dialog', true) dialog: HTMLDialogElement;

  public callback: ModalCallbackFunction = null;
  public ajaxCallback: ModalCallbackFunction = null;

  public userData: { [key: string]: any } = {};

  private readonly uniqueId: number;

  constructor() {
    super();

    this.uniqueId = ++uniqueIdCounter;
  }

  public setContent(content: TemplateResult | JQuery | Element | DocumentFragment): void {
    this.templateResultContent = content;
  }

  public hideModal(): void {
    this.doHideModal();
  }

  protected async doHideModal(): Promise<void> {
    this.trigger('typo3-modal-hide');

    // Add closing class to trigger animation
    this.dialog.classList.add('modal-closing');

    const transitionend = new Promise(resolve => this.dialog.addEventListener('transitionend', resolve, { once: true }));
    // Fallback delay if transitionend is not invoked. Animation duration is 300ms (.3s in CSS) + 5ms gap
    const timeout = new Promise(resolve => setTimeout(resolve, 305));
    await Promise.race([transitionend, timeout]);

    this.dialog.classList.remove('modal-closing');
    this.dialog.close();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected async showModal(): Promise<void> {
    this.trigger('typo3-modal-show');
    this.dialog.showModal();

    const transitionend = new Promise(resolve => this.dialog.addEventListener('transitionend', resolve, { once: true }));
    // Fallback delay if transitionend is not invoked. Animation duration is 300ms (.3s in CSS) + 5ms gap
    const timeout = new Promise(resolve => setTimeout(resolve, 305));
    await Promise.race([transitionend, timeout]);

    this.trigger('typo3-modal-shown');
  }

  protected override firstUpdated(): void {
    this.showModal();
    if (this.callback) {
      this.callback(this);
    }
  }

  protected override updated(changedProperties: PropertyValues): void {
    if (changedProperties.has('templateResultContent')) {
      this.dispatchEvent(new CustomEvent('modal-updated', { bubbles: true }));
    }
  }

  protected override render(): TemplateResult {
    const classes: ClassInfo = classesArrayToClassInfo([
      'modal',
      't3js-modal',
      `modal-type-${this.type}`,
      `modal-style-${this.variant}`,
      `modal-severity-${Severity.getCssClass(this.severity)}`,
      `modal-size-${this.size}`,
      ...this.additionalCssClasses,
    ]);
    return html`
      <dialog
          class=${classMap(classes)}
          aria-labelledby="t3-modal-header-${this.uniqueId}"
          @close=${this.handleDialogClose}
          @cancel=${this.handleDialogCancel}
          @click=${this.handleDialogClick}
      >
        <div class="modal-header t3js-modal-header">
          <div class="modal-header-title t3js-modal-title" id="t3-modal-header-${this.uniqueId}">${this.modalTitle}</div>
          ${this.hideCloseButton ? nothing : html`
            <button class="modal-header-close t3js-modal-close" @click=${() => this.hideModal()}>
              <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
              <span class="visually-hidden">${TYPO3?.lang?.['button.close'] || 'Close'} ${this.modalTitle}</span>
            </button>
          `}
        </div>
        <div class="modal-body t3js-modal-body">${this.renderModalBody()}</div>
        ${this.buttons.length === 0 ? nothing : html`
          <div class="modal-footer t3js-modal-footer">
            ${this.buttons.map(button => this.renderModalButton(button))}
          </div>
        `}
      </dialog>
    `;
  }

  private handleDialogClose(): void {
    this.trigger('typo3-modal-hidden');
  }

  private handleDialogCancel(e: Event): void {
    // Intercept the cancel event (Escape key) to show animation
    e.preventDefault();
    this.hideModal();
  }

  private handleDialogClick(e: Event): void {
    if (e.target === this.dialog) {
      if (this.staticBackdrop) {
        e.preventDefault();
        this.shake();
      } else {
        this.hideModal();
      }
    }
  }

  private _buttonClick(event: Event, button: Button): void {
    const buttonElement = event.currentTarget as HTMLButtonElement;
    if (button.action) {
      this.activeButton = button;
      button.action.execute(buttonElement).then((): void => this.hideModal());
    } else if (button.trigger) {
      button.trigger(event, this);
    }
    buttonElement.dispatchEvent(new CustomEvent('button.clicked', { bubbles: true }));
  }

  private renderAjaxBody(): TemplateResult {
    if (this.templateResultContent === null) {
      new AjaxRequest(this.content as string).get()
        .then(async (response: AjaxResponse): Promise<void> => {
          const htmlResponse = await response.raw().text();
          this.templateResultContent = html`${unsafeHTML(htmlResponse)}`;
          this.updateComplete.then(() => {
            if (this.ajaxCallback) {
              this.ajaxCallback(this);
            }
            this.dispatchEvent(new CustomEvent('modal-loaded'));
          });
        })
        .catch(async (response: AjaxResponse): Promise<void> => {
          const htmlResponse = await response.raw().text();
          if (htmlResponse) {
            this.templateResultContent = html`${unsafeHTML(htmlResponse)}`;
          } else {
            this.templateResultContent = html`<p><strong>Oops, received a ${response.response.status} response from </strong> <span class="text-break">${this.content}</span>.</p>`;
          }
        });
      return html`<div class="modal-loading"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`;
    }

    return this.templateResultContent as TemplateResult;
  }

  private renderModalBody(): TemplateResult | JQuery | Element | DocumentFragment {
    if (this.type === Types.iframe) {
      const loadCallback = (e: Event) => {
        const iframe = e.currentTarget as HTMLIFrameElement;
        if (iframe.contentDocument.title) {
          this.modalTitle = iframe.contentDocument.title;
        }
      };
      return html`
        <iframe src="${this.content}" name="modal_frame" class="modal-iframe t3js-modal-iframe" @load=${loadCallback}></iframe>
      `;
    }

    if (this.type === Types.ajax) {
      return this.renderAjaxBody();
    }

    if (this.type === Types.template) {
      return this.templateResultContent;
    }

    return html`<p>${this.content}</p>`;
  }

  private renderModalButton(button: Button): TemplateResult {
    const btnClass = button.btnClass || 'btn-default';
    const classes: ClassInfo = {
      ['btn']: true,
      [btnClass]: true,
      ['t3js-active']: button.active,
      ['disabled']: this.activeButton && this.activeButton !== button,
    };
    return html`
      <button class=${classMap(classes)}
              name=${ifDefined(button.name || undefined)}
              form=${ifDefined(button.form || undefined)}
              @click=${(e: Event) => this._buttonClick(e, button)}>
          ${button.icon ? html`<typo3-backend-icon identifier="${button.icon}" size="small"></typo3-backend-icon>` : nothing}
          ${button.text}
      </button>
    `;
  }

  private trigger(event: string): void {
    this.dispatchEvent(new CustomEvent(event, { bubbles: true, composed: true }));
  }

  private shake(): void {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }
    this.dialog.animate([
      { transform: 'translateX(0px)' },
      { transform: 'translateX(-2px)' },
      { transform: 'translateX(0px)' },
      { transform: 'translateX(2px)' },
      { transform: 'translateX(0px)' },
    ], 150);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-modal': ModalElement;
  }
}

/**
 * Module: @typo3/backend/modal
 * API for modal windows
 */
class Modal {
  // @todo: drop? available as named exports
  public readonly sizes: typeof Sizes = Sizes;
  public readonly styles: typeof Styles = Styles;
  public readonly types: typeof Types = Types;

  // @todo: currentModal could be a getter method for the last element in this.instances
  public currentModal: ModalElement = null;
  private readonly instances: Array<ModalElement> = [];

  private readonly defaultConfiguration: Configuration = {
    type: Types.default,
    title: 'Information',
    content: 'No content provided, please check your <code>Modal</code> configuration.',
    severity: SeverityEnum.notice,
    buttons: [],
    style: Styles.default,
    size: Sizes.default,
    additionalCssClasses: [],
    callback: null,
    ajaxCallback: null,
    staticBackdrop: false,
    hideCloseButton: false
  };

  constructor() {
    this.initializeMarkupTrigger(document);
  }

  private static createModalResponseEventFromElement(element: HTMLElement, result: boolean): ModalResponseEvent | null {
    if (!element.dataset.eventName) {
      return null;
    }
    return new CustomEvent(
      element.dataset.eventName, {
        bubbles: true,
        detail: { result, payload: element.dataset.eventPayload || null }
      });
  }

  /**
   * Close the current open modal
   */
  public dismiss(): void {
    if (this.currentModal) {
      this.currentModal.hideModal();
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
   * @param {TemplateResult | string | JQuery | Element | DocumentFragment} content The content for the conform modal, e.g. the main question
   * @param {SeverityEnum} severity Default SeverityEnum.warning
   * @param {Array<Button>} buttons An array with buttons, default no buttons
   * @param {Array<string>} additionalCssClasses Additional css classes to add to the modal
   * @returns {ModalElement}
   */
  public confirm(
    title: string,
    content: TemplateResult | string | JQuery | Element | DocumentFragment,
    severity: SeverityEnum = SeverityEnum.warning,
    buttons: Array<Button> = [],
    additionalCssClasses?: Array<string>,
  ): ModalElement {
    if (buttons.length === 0) {
      buttons.push(
        {
          text: TYPO3?.lang?.['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: TYPO3?.lang?.['button.ok'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(severity),
          name: 'ok',
        },
      );
    }

    const modal = this.advanced({
      title,
      content,
      severity,
      buttons,
      additionalCssClasses
    });

    modal.addEventListener('button.clicked', (e: Event): void => {
      const button = e.target as HTMLButtonElement;
      if (button.getAttribute('name') === 'cancel') {
        button.dispatchEvent(new CustomEvent('confirm.button.cancel', { bubbles: true }));
      } else if (button.getAttribute('name') === 'ok') {
        button.dispatchEvent(new CustomEvent('confirm.button.ok', { bubbles: true }));
      }
    });

    return modal;
  }

  /**
   * Load URL with AJAX, append the content to the modal-body
   * and trigger the callback
   *
   * @param {string} title
   * @param {SeverityEnum} severity
   * @param {Array<Button>} buttons
   * @param {string} url
   * @param {ModalCallbackFunction} callback
   * @returns {ModalElement}
   */
  public loadUrl(
    title: string,
    severity: SeverityEnum = SeverityEnum.info,
    buttons: Array<Button>,
    url: string,
    callback?: ModalCallbackFunction
  ): ModalElement {
    return this.advanced({
      type: Types.ajax,
      title,
      severity,
      buttons,
      ajaxCallback: callback,
      content: url,
    });
  }

  /**
   * Shows a dialog
   *
   * @param {string} title
   * @param {string | JQuery | Element | DocumentFragment} content
   * @param {number} severity
   * @param {Array<Button>} buttons
   * @param {Array<string>} additionalCssClasses
   * @returns {ModalElement}
   */
  public show(
    title: string,
    content: string | JQuery | Element | DocumentFragment,
    severity: SeverityEnum = SeverityEnum.info,
    buttons?: Array<Button>,
    additionalCssClasses?: Array<string>,
  ): ModalElement {
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
   */
  public advanced(configuration: PartialConfiguration): ModalElement {
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
    configuration.hideCloseButton = configuration.hideCloseButton || this.defaultConfiguration.hideCloseButton;
    configuration.staticBackdrop = configuration.staticBackdrop || this.defaultConfiguration.staticBackdrop;

    return this.generate(configuration);
  }

  public setButtons(buttons: Array<Button>): ModalElement {
    this.currentModal.buttons = buttons;
    return this.currentModal;
  }

  /**
   * Initialize markup with data attributes
   *
   * @param {HTMLDocument} theDocument
   * @internal
   */
  public initializeMarkupTrigger(theDocument: Document): void {
    const modalTrigger = (evt: Event, triggerElement: HTMLElement): void => {
      evt.preventDefault();
      if ('bsContent' in triggerElement.dataset && !('content' in triggerElement.dataset)) {
        console.error('TYPO3 v14 modal trigger dropped support for the legacy `data-bs-content` attribute. Use `data-content` instead. Affected element:', triggerElement);
      }
      const content = triggerElement.dataset.content || TYPO3?.lang?.['message.confirmation'] || 'Are you sure?';
      let severity = SeverityEnum.notice;
      if (triggerElement.dataset.severity in SeverityEnum) {
        const severityKey = triggerElement.dataset.severity as keyof typeof SeverityEnum;
        severity = SeverityEnum[severityKey];
      }
      let size = Sizes.default;
      if (triggerElement.dataset.size in Sizes) {
        const sizeKey = triggerElement.dataset.size as keyof typeof Sizes;
        size = Sizes[sizeKey];
      }
      let url = triggerElement.dataset.url || null;
      if (url !== null) {
        const separator = url.includes('?') ? '&' : '?';
        const params = new URLSearchParams(triggerElement.dataset).toString();
        url = url + separator + params;
      }
      this.advanced({
        type: url !== null ? Types.ajax : Types.default,
        title: triggerElement.dataset.title || 'Alert',
        content: url !== null ? url : content,
        size,
        severity,
        buttons: [
          {
            text: triggerElement.dataset.buttonCloseText || TYPO3?.lang?.['button.close'] || 'Close',
            active: true,
            btnClass: 'btn-default',
            trigger: (e: Event, modal: ModalElement): void => {
              modal.hideModal();
              const event = Modal.createModalResponseEventFromElement(triggerElement, false);
              if (event !== null) {
                triggerElement.dispatchEvent(event);
              }
            },
          },
          {
            text: triggerElement.dataset.buttonOkText || TYPO3?.lang?.['button.ok'] || 'OK',
            btnClass: 'btn-' + Severity.getCssClass(severity),
            trigger: (e: Event, modal: ModalElement): void => {
              modal.hideModal();
              const event = Modal.createModalResponseEventFromElement(triggerElement, true);
              if (event !== null) {
                triggerElement.dispatchEvent(event);
              }
              const targetLocation = triggerElement.dataset.uri || triggerElement.dataset.href || triggerElement.getAttribute('href');
              if (targetLocation && targetLocation !== '#') {
                triggerElement.ownerDocument.location.href = targetLocation;
              }
              if (triggerElement.getAttribute('type') === 'submit' && (
                triggerElement.tagName === 'BUTTON' || triggerElement.tagName === 'INPUT'
              )) {
                const submitter = triggerElement as HTMLButtonElement|HTMLInputElement;
                submitter.form?.requestSubmit(submitter);
              }
              if (triggerElement.dataset.targetForm) {
                // Submit a possible form in case the trigger has the data-target-form
                // attribute set to a valid form identifier in the ownerDocument.
                (triggerElement.ownerDocument.querySelector('form#' + triggerElement.dataset.targetForm) as HTMLFormElement)?.submit();
              }
            },
          },
        ],
      });
    };
    new RegularEvent('click', modalTrigger).delegateTo(theDocument, '.t3js-modal-trigger');
  }

  /**
   * @param {Configuration} configuration
   */
  private generate(configuration: PartialConfiguration): ModalElement {
    const currentModal = document.createElement('typo3-backend-modal') as ModalElement;

    currentModal.type = configuration.type;
    if (typeof configuration.content === 'string') {
      currentModal.content = configuration.content;
    } else if (configuration.type === Types.default) {
      currentModal.type = Types.template;
      currentModal.templateResultContent = configuration.content;
    }
    currentModal.severity = configuration.severity;
    currentModal.variant = configuration.style;
    currentModal.size = configuration.size;
    currentModal.modalTitle = configuration.title;
    currentModal.additionalCssClasses = configuration.additionalCssClasses;
    currentModal.buttons = <Array<Button>>configuration.buttons;
    currentModal.hideCloseButton = configuration.hideCloseButton;
    currentModal.staticBackdrop = configuration.staticBackdrop;
    if (configuration.callback) {
      currentModal.callback = configuration.callback;
    }
    if (configuration.ajaxCallback) {
      currentModal.ajaxCallback = configuration.ajaxCallback;
    }

    currentModal.addEventListener('typo3-modal-shown', (): void => {
      // focus the button which was configured as active button
      const activeButton = currentModal.querySelector(`${Identifiers.footer} .t3js-active`) as HTMLInputElement | null;
      if (activeButton !== null) {
        activeButton.focus();
      }
    });

    // Remove modal from Modal.instances when hidden
    currentModal.addEventListener('typo3-modal-hide', (): void => {
      if (this.instances.length > 0) {
        const lastIndex = this.instances.length - 1;
        this.instances.splice(lastIndex, 1);
        this.currentModal = this.instances[lastIndex - 1];
      }
    });

    currentModal.addEventListener('typo3-modal-hidden', (): void => {
      currentModal.remove();
    });

    // When modal is opened/shown add it to Modal.instances and make it Modal.currentModal
    currentModal.addEventListener('typo3-modal-show', (): void => {
      this.currentModal = currentModal;
      this.instances.push(currentModal);
    });

    document.body.appendChild(currentModal);

    return currentModal;
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
  modalObject = new Modal();

  if (typeof TYPO3 !== 'undefined') {
    // expose as global object
    TYPO3.Modal = modalObject;
  }
}

export default modalObject;
