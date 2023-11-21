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
  label_Live: string,
  label_Live_crop: string,
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
  icon_Live: string,
  icon_Live_Overlay: string,
  icon_Workspace: string,
  icon_Workspace_Overlay: string,
  languageValue: number,
  language: {
    icon: string
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

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div class="table-fit mb-0">
        <table class="table table-striped">
          <thead>
          <tr>
            <th>
              <div class="btn-group dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">
                  <typo3-backend-icon identifier="actions-selection" size="small"></typo3-backend-icon>
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                  <li>
                    <button type="button" class="dropdown-item disabled" data-multi-record-selection-check-action="check-all" title=${TYPO3.lang['labels.checkAll']}>
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
                    <button type="button" class="dropdown-item disabled" data-multi-record-selection-check-action="check-none" title=${TYPO3.lang['labels.uncheckAll']}>
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
            <th>${TYPO3.lang['column.wsTitle']}</th>
            <th>${TYPO3.lang['column.liveTitle']}</th>
            <th>${TYPO3.lang['column.stage']}</th>
            <th>${TYPO3.lang['column.lastChangeOn']}</th>
            <th>${TYPO3.lang['column.integrity']}</th>
            <th><typo3-backend-icon identifier="flags-multiple" size="small"></typo3-backend-icon></th>
            <th></th>
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
        <td class="t3js-title-workspace">
          <span class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.icon_Workspace)} overlay=${IconHelper.getIconIdentifier(data.icon_Workspace_Overlay)} size="small">
          </span>
          <a href="#" data-action="changes">
            <span class="workspace-state-${data.state_Workspace}" title=${data.label_Workspace}>
              ${data.label_Workspace_crop}
            </span>
          </a>
        </td>
        <td class="t3js-title-live">
          <span class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.icon_Live)} overlay=${IconHelper.getIconIdentifier(data.icon_Live_Overlay)} size="small">
          </span>
          <span class="workspace-live-title" title=${data.label_Live}>
            ${data.label_Live_crop}
          </span>
        </td>
        <td>${data.label_Stage}</td>
        <td>${data.lastChangedFormatted}</td>
        <td>${ data.integrity.messages !== '' ? html`
          <span>
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.integrity.status)} size="small">
          </span>
        ` : nothing}</td>
        <td><typo3-backend-icon identifier=${IconHelper.getIconIdentifier(data.language.icon)} size="small"></td>
        <td class="text-end nowrap">${this.renderActions(data)}</td>
      </tr>
    `;
  }

  private renderActions(data: RecordData): TemplateResult[] {
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
        data.allowedAction_view,
        'preview',
        'actions-version-workspace-preview',
        {
          'title': TYPO3.lang['tooltip.viewElementAction']
        }
      ),
      this.getAction(
        data.allowedAction_edit,
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
      ),
      this.getAction(
        data.allowedAction_delete,
        'remove',
        'actions-version-document-remove',
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
    if (condition) {
      return html`
        <button
          class="btn btn-default"
          data-action="${action}"
          title=${ifDefined(additionalAttributes.title)}
          data-bs-target=${ifDefined(additionalAttributes['data-bs-target'])}
          data-bs-toggle=${ifDefined(additionalAttributes['data-bs-toggle'])}
          aria-expanded=${ifDefined(additionalAttributes['aria-expanded'])}>
          <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(iconIdentifier)} size="small">
        </button>
      `;
    }
    return html`<span
      class="btn btn-default disabled"
      title=${ifDefined(additionalAttributes.title)}
      data-bs-target=${ifDefined(additionalAttributes['data-bs-target'])}
      data-bs-toggle=${ifDefined(additionalAttributes['data-bs-toggle'])}
      aria-expanded=${ifDefined(additionalAttributes['aria-expanded'])}>
      <typo3-backend-icon identifier=${IconHelper.getIconIdentifier('empty-empty')} size="small">
    </span>`;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-workspaces-record-table': RecordTableElement;
  }
}
