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

import { customElement, property } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import IconHelper from '@typo3/workspaces/utility/icon-helper';
import { classMap } from 'lit/directives/class-map.js';
import { ifDefined } from 'lit/directives/if-defined.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import { repeat } from 'lit/directives/repeat.js';
import coreLabels from '~labels/core.core';
import labels from '~labels/workspaces.messages';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/element/qrcode-modal-button';
import 'bootstrap'; // for data-bs-toggle="dropdown"

export type AdditionalColumnValue = {
  label?: string,
  value?: string,
  icon?: string,
  title?: string,
  url?: string
};

export type ActionData = {
  group?: string,
  enabled?: boolean,
  visible?: boolean,
  icon?: string,
  title?: string,
  url?: string
};

export type RecordData = {
  table: string,
  id: string,
  uid: number,
  Workspaces_Collection: number,
  Workspaces_CollectionLevel: number,
  Workspaces_CollectionParent: string,
  Workspaces_CollectionCurrent: string,
  Workspaces_CollectionChildren: number,
  label_Workspace: string,
  label_Stage: string,
  value_nextStage: number,
  value_prevStage: number,
  path_Workspace: string,
  lastChanged: string,
  lastEditorId: number,
  lastEditorName: string,
  lastEditorRealName: string,
  lastEditorAvatar: string,
  t3ver_wsid: number,
  t3ver_oid: number,
  livepid: number,
  stage: number,
  icon_Workspace: string,
  icon_Workspace_Overlay: string,
  language: {
    icon: string,
    title: string,
    title_crop: string
  },
  allowedAction_publish: boolean,
  allowedAction_delete: boolean,
  allowedAction_view: boolean,
  previewUrl: string,
  allowedAction_edit: boolean,
  allowedAction_versionPageOpen: boolean,
  state_Workspace: string,
  hasChanges: boolean,
  urlToPage: string,
  expanded: boolean,
  integrity: {
    status: string,
    messages: string
  },
  additional?: Record<string, AdditionalColumnValue>,
  actions?: Record<string, ActionData>
};

@customElement('typo3-workspaces-record-table')
export class RecordTableElement extends LitElement {
  @property({ type: Array })
  public results: RecordData[] = [];

  /**
   * Labels of columns added by third party extensions, indexed by column identifier.
   */
  @property({ type: Object })
  public additionalColumns: Record<string, string> = {};

  private latestPath: string | null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="table-fit mb-0">
        <table class="table table-striped">
          <thead>
          <tr>
            <th>
              <div class="dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" aria-label="${coreLabels.get('labels.openSelectionOptions')}">
                  <typo3-backend-icon identifier="actions-selection" size="small"></typo3-backend-icon>
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                  <li>
                    <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-all" title=${coreLabels.get('labels.checkAll')}>
                      <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                          <typo3-backend-icon identifier="actions-selection-elements-all" size="small"></typo3-backend-icon>
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                          ${coreLabels.get('labels.checkAll')}
                        </span>
                      </span>
                    </button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-none" title=${coreLabels.get('labels.uncheckAll')}>
                      <span class="dropdown-item-columns">
                          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            <typo3-backend-icon identifier="actions-selection-elements-none" size="small"></typo3-backend-icon>
                          </span>
                          <span class="dropdown-item-column dropdown-item-column-title">
                            ${coreLabels.get('labels.uncheckAll')}
                          </span>
                      </span>
                    </button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" data-multi-record-selection-check-action="toggle" title=${coreLabels.get('labels.toggleSelection')}>
                      <span class="dropdown-item-columns">
                          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            <typo3-backend-icon identifier="actions-selection-elements-invert" size="small"></typo3-backend-icon>
                          </span>
                          <span class="dropdown-item-column dropdown-item-column-title">
                            ${coreLabels.get('labels.toggleSelection')}
                          </span>
                      </span>
                    </button>
                  </li>
                </ul>
              </div>
            </th>
            <th class="col-min">${labels.get('column.wsTitle')}</th>
            <th class="col-language">${coreLabels.get('labels._LOCALIZATION_')}</th>
            <th class="col-datetime">${labels.get('column.last_change')}</th>
            <th class="col-state">${labels.get('column.wsStateAction')}</th>
            <th class="col-state">${labels.get('column.integrity')}</th>
            <th>${labels.get('column.stage')}</th>
            ${Object.values(this.additionalColumns).map((label: string) => html`<th>${label}</th>`)}
            <th class="col-control nowrap">
              <span class="visually-hidden">${coreLabels.get('labels._CONTROL_')}</span>
            </th>
          </tr>
          </thead>
          <tbody data-multi-record-selection-row-selection="true">
            ${repeat(this.results, (result) => result.uid, (data: RecordData) => this.renderTableRow(data))}
          </tbody>
        </table>
      </div>
    `;
  }

  protected renderTableRow(data: RecordData): TemplateResult {
    let parentItem = null;
    let latestPathChanged = false;

    if (this.latestPath !== data.path_Workspace) {
      this.latestPath = data.path_Workspace;
      latestPathChanged = true;
    }

    if (data.Workspaces_CollectionParent !== '') {
      parentItem = this.results.find((element: any) => {
        return element.Workspaces_CollectionCurrent === data.Workspaces_CollectionParent;
      });
    }

    const wsState = data.state_Workspace;
    let wsStateActionClass: string;
    let wsStateActionLabel: string;

    switch (wsState) {
      case 'deleted':
        wsStateActionClass = 'danger';
        wsStateActionLabel = labels.get('column.wsStateAction.deleted');
        break;
      case 'hidden':
        wsStateActionClass = 'secondary';
        wsStateActionLabel = labels.get('column.wsStateAction.hidden');
        break;
      case 'modified':
        wsStateActionClass = 'warning';
        wsStateActionLabel = labels.get('column.wsStateAction.modified');
        break;
      case 'moved':
        wsStateActionClass = 'primary';
        wsStateActionLabel = labels.get('column.wsStateAction.moved');
        break;
      case 'new':
        wsStateActionClass = 'success';
        wsStateActionLabel = labels.get('column.wsStateAction.new');
        break;
      default:
        wsStateActionClass = 'secondary';
        wsStateActionLabel = labels.get('column.wsStateAction.unchanged');
    }

    return html`
      ${latestPathChanged ? html`
        <tr>
          <th colspan=${8 + Object.keys(this.additionalColumns).length} class="col-white-space-normal">
            <a href=${data.urlToPage}>${data.path_Workspace}</a>
          </th>
        </tr>
      ` : nothing}
      <tr
        class=${classMap({ collapse: data.Workspaces_CollectionParent !== '', show: parentItem?.expanded })}
        data-uid=${data.uid}
        data-pid=${data.livepid}
        data-t3ver_oid=${data.t3ver_oid}
        data-t3ver_wsid=${data.t3ver_wsid}
        data-table=${data.table}
        data-next-stage=${data.value_nextStage}
        data-prev-stage=${data.value_prevStage}
        data-stage=${data.stage}
        data-multi-record-selection-element="true"
        data-collection=${data.Workspaces_CollectionParent ? data.Workspaces_CollectionParent : nothing}
        data-collection-current=${ifDefined(data.Workspaces_CollectionCurrent)}
        >
        <td class="col-checkbox">
          <span class="form-check form-check-type-toggle">
            <input type="checkbox" class="form-check-input t3js-multi-record-selection-check"/>
          </span>
        </td>
        <td class="col-min t3js-title-workspace">
          ${ data.Workspaces_CollectionLevel > 0 ? this.renderIndent(data.Workspaces_CollectionLevel) : nothing}
          <span class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.icon_Workspace)} overlay=${IconHelper.getIconIdentifier(data.icon_Workspace_Overlay)} size="small"></typo3-backend-icon>
          </span>
          <a href="#" data-action="changes">
            ${data.label_Workspace}
          </a>
        </td>
        <td class="col-language">
          <span title="${data.language.title}" class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.language.icon)} size="small"></typo3-backend-icon>
          </span>
          ${data.language.title_crop}
        </td>
        <td class="col-datetime col-nowrap">
          <div class="d-flex flex-column flex-nowrap row-gap-1">
            ${data.lastEditorName ? html`
            <span class="d-inline-flex align-items-center gap-1">
              <span class="d-inline-flex align-items-center" aria-hidden="true">
                ${unsafeHTML((data.lastEditorAvatar || '').replace(/--avatar-size:\s*\d+px/g, '--avatar-size: 16px'))}
              </span>
              <span>${data.lastEditorRealName || data.lastEditorName}</span>
            </span>
          ` : html`
            <span class="text-variant">${labels.get('column.editor.unknown')}</span>
          `}
            ${data.lastChanged ? labels.get('column.last_change.value', { last_change: new Date(data.lastChanged) }) : nothing}
          </div>
        </td>
        <td class="col-state">
          <span class="badge badge-${wsStateActionClass}">${wsStateActionLabel}</span>
        </td>
        <td class="col-state">${ data.integrity.messages !== '' ? html`
          <span title="${data.integrity.messages}" class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.integrity.status)} size="small"></typo3-backend-icon>
          </span>
        ` : nothing}</td>
        <td>${data.label_Stage}</td>
        ${Object.keys(this.additionalColumns).map((identifier: string) => this.renderAdditionalColumn(data, identifier))}
        <td class="col-control nowrap">${this.renderActions(data)}</td>
      </tr>
    `;
  }

  protected renderAdditionalColumn(data: RecordData, identifier: string): TemplateResult {
    const column = data.additional?.[identifier];
    if (column === undefined) {
      return html`<td></td>`;
    }

    const content = html`
      ${column.icon ? html`
        <typo3-backend-icon identifier=${column.icon} size="small"></typo3-backend-icon>
      ` : nothing}
      ${column.value ?? ''}
    `;

    return html`
      <td title=${ifDefined(column.title || undefined)}>
        ${column.url ? html`<a href=${column.url}>${content}</a>` : content}
      </td>
    `;
  }

  /**
   * The action buttons of a row, rendered as one button group per group declared by the server.
   * Which actions exist is decided there, the module only knows how to present its own ones.
   */
  protected renderActions(data: RecordData): TemplateResult[] {
    const groups = new Map<string, TemplateResult[]>();

    for (const [ identifier, action ] of Object.entries(data.actions ?? {})) {
      if (action.visible === false) {
        continue;
      }
      const group = action.group ?? 'custom';
      if (!groups.has(group)) {
        groups.set(group, []);
      }
      groups.get(group).push(this.renderAction(identifier, action, data));
    }

    return Array.from(groups.values()).map((actions: TemplateResult[]) => html`
      <div class="btn-group">${actions}</div>
    `);
  }

  protected renderAction(identifier: string, action: ActionData, data: RecordData): TemplateResult {
    const enabled = action.enabled !== false;

    if (identifier === 'qrcode') {
      return this.getQrCodeAction(data, enabled);
    }

    const defaults = this.getActionDefaults(identifier, data, enabled);
    const attributes: Record<string, string> = { ...defaults.attributes };
    const title = action.title || defaults.title;
    if (title !== '') {
      attributes.title = title;
    }
    const icon = action.icon || defaults.icon;

    if (action.url && enabled) {
      return html`
        <a class="btn btn-default" href=${action.url} title=${ifDefined(attributes.title)}>
          <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(icon || 'empty-empty')} size="small"></typo3-backend-icon>
        </a>
      `;
    }

    return this.getAction(enabled, identifier, icon, attributes);
  }

  protected renderIndent(level: number) {
    return html`<span class="indent indent-inline-block" style="--indent-level: ${level}"></span>`;
  }

  /**
   * Icon, title and attributes of the actions the module handles itself. Actions of third party
   * extensions are unknown here and are presented with what their payload provides.
   */
  private getActionDefaults(identifier: string, data: RecordData, enabled: boolean): { icon: string, title: string, attributes: Record<string, string> } {
    switch (identifier) {
      case 'preview':
        return { icon: 'actions-version-workspace-preview', title: labels.get('tooltip.viewElementAction'), attributes: {} };
      case 'open':
        return { icon: 'actions-open', title: labels.get('tooltip.editElementAction'), attributes: {} };
      case 'version':
        return { icon: 'actions-version-page-open', title: labels.get('tooltip.openPage'), attributes: {} };
      case 'expand':
        return {
          icon: data.expanded ? 'actions-caret-down' : 'actions-caret-right',
          title: labels.get('tooltip.expand'),
          attributes: {
            'data-bs-target': '[data-collection="' + data.Workspaces_CollectionCurrent + '"]',
            'aria-expanded': !enabled || data.expanded ? 'true' : 'false',
            'data-bs-toggle': 'collapse',
          }
        };
      case 'changes':
        return { icon: 'actions-document-info', title: labels.get('tooltip.showChanges'), attributes: {} };
      case 'publish':
        return { icon: 'actions-version-swap-version', title: labels.get('tooltip.publish'), attributes: {} };
      case 'remove':
        return { icon: 'actions-delete', title: labels.get('tooltip.discardVersion'), attributes: {} };
      default:
        return { icon: '', title: '', attributes: {} };
    }
  }

  private getQrCodeAction(data: RecordData, enabled: boolean): TemplateResult {
    if (!enabled || !data.previewUrl) {
      return html`
        <button type="button" class="btn btn-default" disabled>
          <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
        </button>
      `;
    }
    return html`
      <typo3-qrcode-modal-button
        class="btn btn-default"
        content="${data.previewUrl}"
        modal-title="${labels.get('tooltip.qrCode') || 'QR Code'}"
        title="${labels.get('tooltip.qrCode') || 'QR Code'}">
        <typo3-backend-icon identifier="actions-qrcode" size="small"></typo3-backend-icon>
      </typo3-qrcode-modal-button>
    `;
  }

  /**
   * Renders the action button based on the user's permission.
   *
   * @param {string} condition
   * @param {string} action
   * @param {string} iconIdentifier
   * @param {object} additionalAttributes
   * @return {TemplateResult}
   */
  private getAction(condition: boolean, action: string, iconIdentifier: string, additionalAttributes?: Record<string, string>): TemplateResult {
    return html`
      <button
        type="button"
        class="btn btn-default"
        ?disabled=${condition ? nothing : ''}
        data-action="${condition ? action : nothing}"
        title=${ifDefined(additionalAttributes.title)}
        data-bs-target=${ifDefined(additionalAttributes['data-bs-target'])}
        data-bs-toggle=${ifDefined(additionalAttributes['data-bs-toggle'])}
        aria-expanded=${ifDefined(additionalAttributes['aria-expanded'])}>
        <typo3-backend-icon identifier=${IconHelper.getIconIdentifier((condition ? iconIdentifier : 'empty-empty'))} size="small"></typo3-backend-icon>
      </button>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-workspaces-record-table': RecordTableElement;
  }
}
