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
var __decorate=function(e,t,n,o){var a,l=arguments.length,i=l<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,n):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,n,o);else for(var s=e.length-1;s>=0;s--)(a=e[s])&&(i=(l<3?a(i):l>3?a(t,n,i):a(t,n))||i);return l>3&&i&&Object.defineProperty(t,n,i),i};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import IconHelper from"@typo3/workspaces/utility/icon-helper.js";import{classMap}from"lit/directives/class-map.js";import{ifDefined}from"lit/directives/if-defined.js";import{repeat}from"lit/directives/repeat.js";import"@typo3/backend/element/icon-element.js";let RecordTableElement=class extends LitElement{constructor(){super(...arguments),this.results=[],this.latestPath=null}createRenderRoot(){return this}render(){return html`
      <div class="table-fit mb-0">
        <table class="table table-striped">
          <thead>
          <tr>
            <th>
              <div class="btn-group dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" aria-label="${TYPO3.lang["labels.openSelectionOptions"]}">
                  <typo3-backend-icon identifier="actions-selection" size="small"></typo3-backend-icon>
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                  <li>
                    <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-all" title=${TYPO3.lang["labels.checkAll"]}>
                      <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                          <typo3-backend-icon identifier="actions-selection-elements-all" size="small"></typo3-backend-icon>
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                          ${TYPO3.lang["labels.checkAll"]}
                        </span>
                      </span>
                    </button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-none" title=${TYPO3.lang["labels.uncheckAll"]}>
                      <span class="dropdown-item-columns">
                          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            <typo3-backend-icon identifier="actions-selection-elements-none" size="small"></typo3-backend-icon>
                          </span>
                          <span class="dropdown-item-column dropdown-item-column-title">
                            ${TYPO3.lang["labels.uncheckAll"]}
                          </span>
                      </span>
                    </button>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" data-multi-record-selection-check-action="toggle" title=${TYPO3.lang["labels.toggleSelection"]}>
                      <span class="dropdown-item-columns">
                          <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            <typo3-backend-icon identifier="actions-selection-elements-invert" size="small"></typo3-backend-icon>
                          </span>
                          <span class="dropdown-item-column dropdown-item-column-title">
                            ${TYPO3.lang["labels.toggleSelection"]}
                          </span>
                      </span>
                    </button>
                  </li>
                </ul>
              </div>
            </th>
            <th class="col-min">${TYPO3.lang["column.wsTitle"]}</th>
            <th class="col-language">${TYPO3.lang["labels._LOCALIZATION_"]}</th>
            <th class="col-datetime">${TYPO3.lang["column.lastChangeOn"]}</th>
            <th class="col-state">${TYPO3.lang["column.wsStateAction"]}</th>
            <th class="col-state">${TYPO3.lang["column.integrity"]}</th>
            <th>${TYPO3.lang["column.stage"]}</th>
            <th class="col-control nowrap">
              <span class="visually-hidden">${TYPO3.lang["labels._CONTROL_"]}</span>
            </th>
          </tr>
          </thead>
          <tbody data-multi-record-selection-row-selection="true">
            ${repeat(this.results,(e=>e.uid),(e=>this.renderTableRow(e)))}
          </tbody>
        </table>
      </div>
    `}renderTableRow(e){let t=null,n=!1;this.latestPath!==e.path_Workspace&&(this.latestPath=e.path_Workspace,n=!0),""!==e.Workspaces_CollectionParent&&(t=this.results.find((t=>t.Workspaces_CollectionCurrent===e.Workspaces_CollectionParent)));let o,a;switch(e.state_Workspace){case"deleted":o="danger",a=TYPO3.lang["column.wsStateAction.deleted"];break;case"hidden":o="secondary",a=TYPO3.lang["column.wsStateAction.hidden"];break;case"modified":o="warning",a=TYPO3.lang["column.wsStateAction.modified"];break;case"moved":o="primary",a=TYPO3.lang["column.wsStateAction.moved"];break;case"new":o="success",a=TYPO3.lang["column.wsStateAction.new"];break;default:o="secondary",a=TYPO3.lang["column.wsStateAction.unchanged"]}return html`
      ${n?html`
        <tr>
          <th></th>
          <th colspan="7">
            <span title=${e.path_Workspace}>
              ${e.path_Workspace_crop}
            </span>
          </th>
        </tr>
      `:nothing}
      <tr
        class=${classMap({collapse:""!==e.Workspaces_CollectionParent,show:t?.expanded})}
        data-uid=${e.uid}
        data-pid=${e.livepid}
        data-t3ver_oid=${e.t3ver_oid}
        data-t3ver_wsid=${e.t3ver_wsid}
        data-table=${e.table}
        data-next-stage=${e.value_nextStage}
        data-prev-stage=${e.value_prevStage}
        data-stage=${e.stage}
        data-multi-record-selection-element="true"
        data-collection=${e.Workspaces_CollectionParent?e.Workspaces_CollectionParent:nothing}
        data-collection-current=${ifDefined(e.Workspaces_CollectionCurrent)}
        >
        <td class="col-checkbox">
          <span class="form-check form-check-type-toggle">
            <input type="checkbox" class="form-check-input t3js-multi-record-selection-check"/>
          </span>
        </td>
        <td class="col-min t3js-title-workspace">
          <span class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(e.icon_Workspace)} overlay=${IconHelper.getIconIdentifier(e.icon_Workspace_Overlay)} size="small"></typo3-backend-icon>
          </span>
          <a href="#" data-action="changes">
            <span title=${e.label_Workspace}>
              ${e.label_Workspace_crop}
            </span>
          </a>
        </td>
        <td class="col-language">
          <span title="${e.language.title}" class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(e.language.icon)} size="small"></typo3-backend-icon>
          </span>
          ${e.language.title_crop}
        </td>
        <td class="col-datetime">${e.lastChangedFormatted}</td>
        <td class="col-state">
          <span class="badge badge-${o}">${a}</span>
        </td>
        <td class="col-state">${""!==e.integrity.messages?html`
          <span title="${e.integrity.messages}" class="icon icon-size-small">
            <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(e.integrity.status)} size="small"></typo3-backend-icon>
          </span>
        `:nothing}</td>
        <td>${e.label_Stage}</td>
        <td class="col-control nowrap">
          <div class="btn-group">${this.renderElementActions(e)}</div>
          <div class="btn-group">${this.renderVersioningActions(e)}</div>
        </td>
      </tr>
    `}renderElementActions(e){return[this.getAction(e.allowedAction_view,"preview","actions-version-workspace-preview",{title:TYPO3.lang["tooltip.viewElementAction"]}),this.getAction(e.allowedAction_edit&&"deleted"!==e.state_Workspace,"open","actions-open",{title:TYPO3.lang["tooltip.editElementAction"]}),this.getAction(e.allowedAction_versionPageOpen,"version","actions-version-page-open",{title:TYPO3.lang["tooltip.openPage"]})]}renderVersioningActions(e){const t=e.Workspaces_CollectionChildren>0&&""!==e.Workspaces_CollectionCurrent;return[this.getAction(t,"expand",e.expanded?"actions-caret-down":"actions-caret-right",{title:TYPO3.lang["tooltip.expand"],"data-bs-target":'[data-collection="'+e.Workspaces_CollectionCurrent+'"]',"aria-expanded":!t||e.expanded?"true":"false","data-bs-toggle":"collapse"}),this.getAction(e.hasChanges,"changes","actions-document-info",{title:TYPO3.lang["tooltip.showChanges"]}),this.getAction(e.allowedAction_publish&&""===e.Workspaces_CollectionParent,"publish","actions-version-swap-version",{title:TYPO3.lang["tooltip.publish"]}),this.getAction(e.allowedAction_delete,"remove","actions-delete",{title:TYPO3.lang["tooltip.discardVersion"]})]}getAction(e,t,n,o){return html`
      <button
        type="button"
        class="btn btn-default"
        disabled="${e?nothing:""}"
        data-action="${e?t:nothing}"
        title=${ifDefined(o.title)}
        data-bs-target=${ifDefined(o["data-bs-target"])}
        data-bs-toggle=${ifDefined(o["data-bs-toggle"])}
        aria-expanded=${ifDefined(o["aria-expanded"])}>
        <typo3-backend-icon identifier=${IconHelper.getIconIdentifier(e?n:"empty-empty")} size="small"></typo3-backend-icon>
      </button>
    `}};__decorate([property({type:Array})],RecordTableElement.prototype,"results",void 0),RecordTableElement=__decorate([customElement("typo3-workspaces-record-table")],RecordTableElement);export{RecordTableElement};