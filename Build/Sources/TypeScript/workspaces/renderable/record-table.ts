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

import { customElement, property } from 'lit/decorators';
import { html, LitElement, nothing, TemplateResult } from 'lit';
import IconHelper from '@typo3/workspaces/utility/icon-helper';
import { classMap } from 'lit/directives/class-map';
import { ifDefined } from 'lit/directives/if-defined';
import { repeat } from 'lit/directives/repeat';
import '@typo3/backend/element/icon-element';

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
  label_Workspace_crop: string,
  label_Stage: string,
  label_nextStage: string,
  value_nextStage: number,
  label_prevStage: string,
  value_prevStage: number,
  path_Live: string,
  path_Workspace: string,
  path_Workspace_crop: string,
  workspace_Title: string,
  workspace_Tstamp: number,
  lastChangedFormatted: string,
  t3ver_wsid: number,
  t3ver_oid: number,
  livepid: number,
  stage: number,
  icon_Workspace: string,
  icon_Workspace_Overlay: string,
  languageValue: number,
  language: {
    icon: string,
    title: string,
    title_crop: string
  },
  allowedAction_nextStage: boolean,
  allowedAction_prevStage: boolean,
  allowedAction_publish: boolean,
  allowedAction_delete: boolean,
  allowedAction_view: boolean,
  allowedAction_edit: boolean,
  allowedAction_versionPageOpen: boolean,
  state_Workspace: string,
  hasChanges: boolean,
  expanded: boolean,
  integrity: {
    status: string,
    messages: string
  }
};

@customElement('typo3-workspaces-record-table')
export class RecordTableElement extends LitElement {
  @property({ type: Array })
  public results: RecordData[] = [];

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
              <div class="btn-group dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" aria-label="${TYPO3.lang['labels.openSelectionOptions']}">
                  <typo3-backend-icon identifier="actions-selection" size="small"></typo3-backend-icon>
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                  <li>
                    <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-all" title=${TYPO3.lang['labels.checkAll']}>
                      <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                          <typo3-backend-icon identifier="actions-selection-elements-all" size="small"></typo3-backend-icon>
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                          ${TYPO3.lang['labels.checkAll']}
                        </span>
                      </span>
                    </button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-none" title=${TYPO3.lang['labels.uncheckAll']}>
                      <span class="dropdown-item-columns">
                          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            <typo3-backend-icon identifier="actions-selection-elements-none" size="small"></typo3-backend-icon>
                          </span>
                          <span class="dropdown-item-column dropdown-item-column-title">
                            ${TYPO3.lang['labels.uncheckAll']}
                          </span>
                      </span>
                    </button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" data-multi-record-selection-check-action="toggle" title=${TYPO3.lang['labels.toggleSelection']}>
                      <span class="dropdown-item-columns">
                          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            <typo3-backend-icon identifier="actions-selection-elements-invert" size="small"></typo3-backend-icon>
                          </span>
                          <span class="dropdown-item-column dropdown-item-column-title">
                            ${TYPO3.lang['labels.toggleSelection']}
                          </span>
                      </span>
                    </button>
                  </li>
                </ul>
              </div>
            </th>
            <th class="col-min">${TYPO3.lang['column.wsTitle']}</th>
            <th class="col-language">${TYPO3.lang['labels._LOCALIZATION_']}</th>
            <th class="col-datetime">${TYPO3.lang['column.lastChangeOn']}</th>
            <th class="col-state">${TYPO3.lang['column.wsStateAction']}</th>
            <th class="col-state">${TYPO3.lang['column.integrity']}</th>
            <th>${TYPO3.lang['column.stage']}</th>
            <th class="col-control nowrap">
              <span class="visually-hidden">${TYPO3.lang['labels._CONTROL_']}</span>
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
        wsStateActionLabel = TYPO3.lang['column.wsStateAction.deleted'];
        break;
      case 'hidden':
        wsStateActionClass = 'secondary';
        wsStateActionLabel = TYPO3.lang['column.wsStateAction.hidden'];
        break;
      case 'modified':
        wsStateActionClass = 'warning';
        wsStateActionLabel = TYPO3.lang['column.wsStateAction.modified'];
        break;
      case 'moved':
        wsStateActionClass = 'primary';
        wsStateActionLabel = TYPO3.lang['column.wsStateAction.moved'];
        break;
      case 'new':
        wsStateActionClass = 'success';
        wsStateActionLabel = TYPO3.lang['column.wsStateAction.new'];
        break;
      default:
        wsStateActionClass = 'secondary';
        wsStateActionLabel = TYPO3.lang['column.wsStateAction.unchanged'];
    }

    return html`
      ${latestPathChanged ? html`
        <tr>
          <th></th>
          <th colspan="7">
            <span title=${data.path_Workspace}>
              ${data.path_Workspace_crop}
            </span>
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
          <span class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.icon_Workspace)} overlay=${IconHelper.getIconIdentifier(data.icon_Workspace_Overlay)} size="small"></typo3-backend-icon>
          </span>
          <a href="#" data-action="changes">
            <span title=${data.label_Workspace}>
              ${data.label_Workspace_crop}
            </span>
          </a>
        </td>
        <td class="col-language">
          <span title="${data.language.title}" class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.language.icon)} size="small"></typo3-backend-icon>
          </span>
          ${data.language.title_crop}
        </td>
        <td class="col-datetime">${data.lastChangedFormatted}</td>
        <td class="col-state">
          <span class="badge badge-${wsStateActionClass}">${wsStateActionLabel}</span>
        </td>
        <td class="col-state">${ data.integrity.messages !== '' ? html`
          <span title="${data.integrity.messages}" class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.integrity.status)} size="small"></typo3-backend-icon>
          </span>
        ` : nothing}</td>
        <td>${data.label_Stage}</td>
        <td class="col-control nowrap">
          <div class="btn-group">${this.renderElementActions(data)}</div>
          <div class="btn-group">${this.renderVersioningActions(data)}</div>
        </td>
      </tr>
    `;
  }

  private renderElementActions(data: RecordData): TemplateResult[] {
    return [
      this.getAction(
        data.allowedAction_view,
        'preview',
        'actions-version-workspace-preview',
        {
          'title': TYPO3.lang['tooltip.viewElementAction']
        }
      ),
      this.getAction(
        data.allowedAction_edit && data.state_Workspace !== 'deleted',
        'open',
        'actions-open',
        {
          'title': TYPO3.lang['tooltip.editElementAction']
        }
      ),
      this.getAction(
        data.allowedAction_versionPageOpen,
        'version',
        'actions-version-page-open',
        {
          'title': TYPO3.lang['tooltip.openPage']
        }
      )
    ];
  }

  private renderVersioningActions(data: RecordData): TemplateResult[] {
    const hasSubitems = data.Workspaces_CollectionChildren > 0 && data.Workspaces_CollectionCurrent !== '';

    return [
      this.getAction(
        hasSubitems,
        'expand',
        (data.expanded ? 'actions-caret-down' : 'actions-caret-right'),
        {
          'title': TYPO3.lang['tooltip.expand'],
          'data-bs-target': '[data-collection="' + data.Workspaces_CollectionCurrent + '"]',
          'aria-expanded': !hasSubitems || data.expanded ? 'true' : 'false',
          'data-bs-toggle': 'collapse',
        }
      ),
      this.getAction(
        data.hasChanges,
        'changes',
        'actions-document-info',
        {
          'title': TYPO3.lang['tooltip.showChanges']
        }
      ),
      this.getAction(
        data.allowedAction_publish && data.Workspaces_CollectionParent === '',
        'publish',
        'actions-version-swap-version',
        {
          'title': TYPO3.lang['tooltip.publish']
        }
      ),
      this.getAction(
        data.allowedAction_delete,
        'remove',
        'actions-delete',
        {
          'title': TYPO3.lang['tooltip.discardVersion']
        }
      )
    ];
  }

  /**
   * Renders the action button based on the user's permission.
   *
   * @param {string} condition
   * @param {string} action
   * @param {string} iconIdentifier
   * @param {object} additionalAttributes
   * @return {JQuery}
   */
  private getAction(condition: boolean, action: string, iconIdentifier: string, additionalAttributes?: Record<string, string>): TemplateResult {
    return html`
      <button
        type="button"
        class="btn btn-default"
        disabled="${condition ? nothing : ''}"
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
