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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { until } from 'lit/directives/until';
import { unsafeHTML } from 'lit/directives/unsafe-html';
import { classMap } from 'lit/directives/class-map';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';

enum CopyMode {
  cut = 'cut',
  copy = 'copy',
}

interface ClipboardData {
  current: string;
  copyMode: CopyMode;
  elementCount: number;
  tabs: Array<ClipboardTab>;
  labels: Record<string, string>;
}

interface ClipboardTab {
  identifier: string;
  info: string;
  title: string;
  description: string;
  items: Array<ClipboardTabItem>;
}

interface ClipboardTabItem {
  identifier: string;
  title: string;
  icon: string;
  thumb: string;
  infoDataDispatch: DispatchArgs;
}

interface DispatchArgs {
  action: string,
  args: Array<any>
}

/**
 * Module: @typo3/backend/clipboard-panel
 *
 * @example
 * <typo3-backend-clipboard-panel return-url="/typo3/module" table="_FILE"></typo3-backend-clipboard-panel>
 */
@customElement('typo3-backend-clipboard-panel')
export class ClipboardPanel extends LitElement {
  @property({ type: String, attribute: 'return-url' }) returnUrl: string = '';
  @property({ type: String }) table: string = '';

  private static renderLoader(): TemplateResult {
    return html`
      <div class="panel panel-default">
        <div class="panel-loader">
          <typo3-backend-spinner size="small"></typo3-backend-spinner>
        </div>
      </div>
    `;
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    // const renderRoot = this.attachShadow({mode: 'open'});
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      ${until(this.renderPanel(), ClipboardPanel.renderLoader())}
    `;
  }

  private renderPanel(): Promise<TemplateResult> {
    return (new AjaxRequest(top.TYPO3.settings.Clipboard.moduleUrl))
      .withQueryArguments({ action: 'getClipboardData' })
      .post({ table: this.table })
      .then(async (response: AjaxResponse): Promise<TemplateResult> => {
        const resolvedBody = await response.resolve();
        if (resolvedBody.success === true && resolvedBody.data) {
          const clipboardData: ClipboardData = resolvedBody.data;
          return html`
            <div class="panel panel-default" data-clipboard-panel>
              <div class="panel-heading">
                ${clipboardData.labels.clipboard}
              </div>
              <div class="table-fit">
                <table class="table">
                  <tbody>
                    ${clipboardData.tabs.map((tab: ClipboardTab): TemplateResult => this.renderTab(tab, clipboardData))}
                  </tbody>
                </table>
              </div>
            </div>
          `;
        } else {
          return html`
            <div class="alert alert-danger">Clipboard data could not be fetched</div>
          `;
        }
      })
      .catch((): TemplateResult => {
        return html`
          <div class="alert alert-danger">An error occurred while fetching clipboard data</div>
        `;
      });
  }

  private renderTab(tab: ClipboardTab, clipboardData: ClipboardData): TemplateResult {
    return html`
      <tr>
        <td colspan="2" class="nowrap">
          <button type="button" class="btn btn-link" aria-checked="${clipboardData.current === tab.identifier}" title="${tab.description}" data-action="setP" @click="${(event: PointerEvent) => this.updateClipboard(event, { CB: { 'setP': tab.identifier } })}">
            ${clipboardData.current === tab.identifier ? html`
              <typo3-backend-icon identifier="actions-check-circle-alt" size="small"></typo3-backend-icon>
              ${tab.title}
              ${tab.info}` : html`
              <typo3-backend-icon identifier="actions-circle" size="small"></typo3-backend-icon>
              <span class="text-body-secondary">
                ${tab.title}
                ${tab.info}
              </span>
            `}
          </button>
        </td>
        <td class="col-control nowrap">
          ${clipboardData.current !== tab.identifier ? nothing : html`
            <div class="btn-group">
              <input type="radio" class="btn-check" id="clipboard-copymode-copy" data-action="setCopyMode" ?checked=${clipboardData.copyMode === CopyMode.copy} @click="${(event: PointerEvent) => this.updateClipboard(event, { CB: { 'setCopyMode': '1' } })}">
              <label class="btn btn-default btn-sm" for="clipboard-copymode-copy">
                <typo3-backend-icon identifier="actions-edit-copy" size="small"></typo3-backend-icon>
                ${clipboardData.labels.copyElements}
              </label>
              <input type="radio" class="btn-check" id="clipboard-copymode-move" data-action="setCopyMode" ?checked=${clipboardData.copyMode !== CopyMode.copy} @click="${(event: PointerEvent) => this.updateClipboard(event, { CB: { 'setCopyMode': '0' } })}">
              <label class="btn btn-default btn-sm" for="clipboard-copymode-move">
                <typo3-backend-icon identifier="actions-cut" size="small"></typo3-backend-icon>
                ${clipboardData.labels.moveElements}
              </label>
            </div>
            ${!clipboardData.elementCount ? nothing : html`
              <button type="button" class="btn btn-default btn-sm" title="${clipboardData.labels.removeAll}" data-action="removeAll" @click="${(event: PointerEvent) => this.updateClipboard(event, { CB: { 'removeAll': tab.identifier } })}">
                <typo3-backend-icon identifier="actions-minus" size="small"></typo3-backend-icon>
                ${clipboardData.labels.removeAll}
              </button>`}
          `}
        </td>
      </tr>
      ${clipboardData.current === tab.identifier && tab.items ? tab.items.map((tabItem: ClipboardTabItem): TemplateResult => this.renderTabItem(tabItem, tab.identifier, clipboardData)) : nothing}
    `;
  }

  private renderTabItem(tabItem: ClipboardTabItem, tabIdentifier: string, clipboardData: ClipboardData): TemplateResult {
    return html`
      <tr>
        <td class="col-icon nowrap ${classMap({ 'ps-4': !tabItem.identifier })}">
          ${unsafeHTML(tabItem.icon)}
        </td>
        <td class="nowrap" style="width: 95%">
          ${unsafeHTML(tabItem.title)}
          ${tabIdentifier === 'normal' ? html`<strong>(${clipboardData.copyMode === CopyMode.copy ? html`${clipboardData.labels.copy}` : html`${clipboardData.labels.cut}`})</strong>` : nothing}
          ${tabItem.thumb ? html`<div class="d-block">${unsafeHTML(tabItem.thumb)}</div>` : nothing}
        </td>
        <td class="col-control nowrap">
          <div class="btn-group">
            ${!tabItem.infoDataDispatch ? nothing : html`
              <button type="button" class="btn btn-default btn-sm" data-dispatch-action="${tabItem.infoDataDispatch.action}" data-dispatch-args="${tabItem.infoDataDispatch.args}" title="${clipboardData.labels.info}">
                <typo3-backend-icon identifier="actions-document-info" size="small"></typo3-backend-icon>
              </button>
            `}
            ${!tabItem.identifier ? nothing : html`
              <button type="button" class="btn btn-default btn-sm" title="${clipboardData.labels.removeItem}" data-action="remove" @click="${(event: PointerEvent) => this.updateClipboard(event,{ CB: { 'remove': tabItem.identifier } })}">
                <typo3-backend-icon identifier="actions-minus" size="small"></typo3-backend-icon>
                ${clipboardData.labels.removeItem}
              </button>
            `}
          </div>
        </td>
      </tr>`;
  }

  private updateClipboard(event: PointerEvent, payload: object): void {
    event.preventDefault();
    const target: HTMLElement = event.currentTarget as HTMLElement;
    (new AjaxRequest(top.TYPO3.settings.Clipboard.moduleUrl))
      .post(payload)
      .then(async (response: AjaxResponse): Promise<void> => {
        const resolvedBody = await response.resolve();
        if (resolvedBody.success === true) {
          // In case action is provided, dispatch an event to let
          // other components react on the updated clipboard state.
          if (target.dataset.action) {
            target.dispatchEvent(new CustomEvent('typo3:clipboard:' + target.dataset.action, {
              detail: { payload: payload, response: resolvedBody },
              bubbles: true,
              cancelable: false
            }));
          }
          // @todo Add possibility for a callback, e.g. to dispatch an event after clipboard data was updated
          this.reloadModule();
        } else {
          Notification.error('Clipboard data could not be updated');
        }
      })
      .catch((): void => {
        Notification.error('An error occurred while updating clipboard data');
      });
  }

  private reloadModule (): void {
    if (this.returnUrl) {
      this.ownerDocument.location.href = this.returnUrl;
    } else {
      this.ownerDocument.location.reload();
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-clipboard-panel': ClipboardPanel;
  }
}
