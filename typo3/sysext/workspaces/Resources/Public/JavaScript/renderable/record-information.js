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
var __decorate=function(e,t,r,o){var s,n=arguments.length,a=n<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)a=Reflect.decorate(e,t,r,o);else for(var i=e.length-1;i>=0;i--)(s=e[i])&&(a=(n<3?s(a):n>3?s(t,r,a):s(t,r))||a);return n>3&&a&&Object.defineProperty(t,r,a),a};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import{unsafeHTML}from"lit/directives/unsafe-html.js";import"@typo3/workspaces/renderable/diff-view.js";import"@typo3/workspaces/renderable/comment-view.js";import"@typo3/workspaces/renderable/history-view.js";let RecordInformationElement=class extends LitElement{constructor(){super(...arguments),this.TYPO3lang=null}createRenderRoot(){return this}render(){return html`
      <div>
        <p>${unsafeHTML(this.TYPO3lang.path.replace("{0}",this.record.path_Live))}</p>
        <p>${unsafeHTML(this.TYPO3lang.current_step.replace("{0}",this.record.label_Stage).replace("{1}",this.record.stage_position).replace("{2}",this.record.stage_count))}</p>
        <ul class="nav nav-tabs" role="tablist">
          ${this.record.diff.length>0?this.renderNavLink(this.TYPO3lang["window.recordChanges.tabs.changeSummary"],"#workspace-changes"):nothing}
          ${this.record.comments.length>0?this.renderNavLink(this.TYPO3lang["window.recordChanges.tabs.changeSummary"],"#workspace-comments",this.record.comments.length):nothing}
          ${this.record.history.data.length>0?this.renderNavLink(this.TYPO3lang["window.recordChanges.tabs.history"],"#workspace-history"):nothing}
        </ul>
        <div class="tab-content">
          ${this.record.diff.length>0?html`
            <div class="tab-pane" id="workspace-changes" role="tabpanel">
              <div class="form-section">
                <typo3-workspaces-diff-view .diffs=${this.record.diff}></typo3-workspaces-diff-view>
              </div>
            </div>
          `:nothing}
          ${this.record.comments.length>0?html`
            <div class="tab-pane" id="workspace-comments" role="tabpanel">
              <div class="form-section">
                <typo3-workspaces-comment-view .comments=${this.record.comments}></typo3-workspaces-comment-view>
              </div>
            </div>
          `:nothing}
          ${this.record.history.data.length>0?html`
            <div class="tab-pane" id="workspace-history" role="tabpanel">
              <div class="form-section">
                <typo3-workspaces-history-view .historyItems=${this.record.history.data}></typo3-workspaces-history-view>
              </div>
            </div>
          `:nothing}
        </div>
      </div>
    `}renderNavLink(e,t,r=0){return html`
      <li class="nav-item" role="presentation">
        <button
          type="button"
          class="nav-link"
          data-bs-toggle="tab"
          data-bs-target="${t}"
          aria-controls="${t}"
          role="tab"
        >
          ${e}
          ${r>0?html`<span class="badge">${r}</span>`:nothing}
        </button>
      </li>
    `}firstUpdated(){this.renderRoot.querySelector(".nav-link").classList.add("active"),this.renderRoot.querySelector(".tab-pane").classList.add("active")}};__decorate([property({type:Object})],RecordInformationElement.prototype,"record",void 0),__decorate([property({type:Object})],RecordInformationElement.prototype,"TYPO3lang",void 0),RecordInformationElement=__decorate([customElement("typo3-workspaces-record-information")],RecordInformationElement);export{RecordInformationElement};