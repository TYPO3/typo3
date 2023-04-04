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
var CspReportAttribute,__decorate=function(t,e,i,o){var s,l=arguments.length,n=l<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,i,o);else for(var r=t.length-1;r>=0;r--)(s=t[r])&&(n=(l<3?s(n):l>3?s(e,i,n):s(e,i))||n);return l>3&&n&&Object.defineProperty(e,i,n),n};import{customElement,property,state}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import{classMap}from"lit/directives/class-map.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{styleTag,lll}from"@typo3/core/lit-helper.js";!function(t){t.fixable="fixable",t.irrelevant="irrelevant",t.suspicious="suspicious"}(CspReportAttribute||(CspReportAttribute={}));let CspReports=class extends LitElement{constructor(){super(...arguments),this.selectedScope=null,this.reports=[],this.selectedReport=null,this.suggestions=[]}createRenderRoot(){return this}connectedCallback(){super.connectedCallback(),this.fetchReports()}render(){return html`
      ${styleTag`
        .infolist-container {
          container-type: inline-size;
        }
        .infolist {
          display: flex;
          gap: var(--typo3-spacing);
          flex-direction: column;
        }
        .infolist-info {
          display: none;
        }
        .infolist-overlay {
          position: relative;
          z-index: 10;
        }

        @container (max-width: 899px) {
          .infolist-info-showrecord {
            display: block;
            background: rgba(0,0,0,.5);
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
          }
        }

        @container (min-width: 900px) {
          .infolist {
            display: grid;
            grid-template:
              "header header"
              "content info";
            grid-template-columns: auto 400px;
          }
          .infolist-header {
            grid-area: header;
          }
          .infolist-content {
            grid-area: content;
          }
          .infolist-info {
            display: block;
            grid-area: info;
          }
          .infolist-info-norecord,
          .infolist-info-record {
            position: sticky;
            top: calc(var(--module-docheader-height) + var(--typo3-spacing));
          }
        }
      `}
      <div class="infolist-container infolist-overlay">
        <div class="infolist">
          <div class="infolist-header">
            ${this.renderNavigation()}
          </div>
        </div>
      </div>

      <div class="infolist-container">
        <div class="infolist">
          <div class="infolist-content">
            <div class="table-fit mb-0">
              <table class="table table-striped">
                <thead>
                <tr>
                  <th>${lll("label.created")||"Created"}</th>
                  <th>${lll("label.scope")||"Scope"}</th>
                  <th>${lll("label.violation")||"Violation"}</th>
                  <th>${lll("label.uri")||"URI"}</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                ${0===this.reports.length?html`
                  <tr><td colspan="5">${lll("label.label.noEntriesAvailable")||"No entries available."}</td></tr>
                `:nothing}
                ${this.reports.map((t=>html`
                  <tr class=${classMap({"table-info":this.selectedReport===t})} data-mutation-group=${t.mutationHashes.join("-")}
                      @click=${()=>this.selectReport(t)}>
                    <td>${t.created}</td>
                    <td>${t.scope}</td>
                    <td>
                      <span class="badge bg-warning">${t.count}</span>
                      ${t.details.violatedDirective}
                    </td>
                    <td>${this.shortenUri(t.details.blockedUri)}</td>
                    <td>${t.attributes.join(", ")}</td>
                  </tr>
                `))}
                </tbody>
              </table>
            </div>
          </div>
          <div class="infolist-info${this.selectedReport?" infolist-info-showrecord":""}">
            ${this.renderGuide()}
            ${this.renderSelectedReport()}
          </div>
        </div>
      </div>
    `}renderNavigation(){return html`
      <div class="btn-group">
        <button type="button" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="${lll("scope")}}">
          ${null===this.selectedScope?lll("all")||"ALL":this.selectedScope}
        </button>
        <ul class="dropdown-menu">
          <button class="dropdown-item dropdown-item-spaced" title="${lll("all")||"ALL"}" @click="${()=>{this.selectScope(null)}}">
            <span class="${null===this.selectedScope?"text-primary":""}">
              <typo3-backend-icon identifier="${null===this.selectedScope?"actions-dot":"empty-empty"}" size="small"></typo3-backend-icon>
            </span>
            ${lll("all")||"ALL"}
          </button>
          ${this.scopes.map((t=>html`
            <li>
              <button class="dropdown-item dropdown-item-spaced" title="${t}" @click="${()=>{this.selectScope(t)}}">
                <span class="${t===this.selectedScope?"text-primary":""}">
                  <typo3-backend-icon identifier="${t===this.selectedScope?"actions-dot":"empty-empty"}" size="small"></typo3-backend-icon>
                </span>
                ${t}
              </button>
            </li>`))}
        </ul>
      </div>`}renderGuide(){return html`${this.selectedReport?nothing:html`
      <div class="infolist-info-norecord">
        <div class="card mb-0">
          <div class="card-body">
            <p>${lll("label.guide.no_record_selected")||"Select a row to see more information."}</p>
          </div>
        </div>
      </div>
    `}`}renderSelectedReport(){const t=this.selectedReport;return html`${t?html`
      <div class="infolist-info-record">
        <div class="card mb-0">
          <div class="card-header">
            <h3>${lll("label.details")||"Details"}</h3>
          </div>
          <div class="card-body">
            <dl>
              <dt>${lll("label.directive")||"Directive"} / ${lll("label.disposition")||"Disposition"}</dt>
              <dd>${t.details.effectiveDirective} / ${t.details.disposition}</dd>

              <dt>${lll("label.blocked_uri")||"Blocked URI"}</dt>
              <dd>${t.details.blockedUri}</dd>

              <dt>${lll("label.document_uri")||"Document URI"}</dt>
              <dd>
                ${t.details.documentUri}
                ${t.details.lineNumber?html`
                  (${t.details.lineNumber}:${t.details.columnNumber})
                `:nothing}</dd>

              ${t.details.scriptSample?html`
                <dt>${lll("label.sample")||"Sample"}</dt>
                <dd><code>
                  <pre>${t.details.scriptSample}</pre>
                </code></dd>
              `:nothing}

              <dt>${lll("label.uuid")||"UUID"}</dt>
              <dd><code>${t.uuid}</code></dd>

              <dt>${lll("label.summary")||"Summary"}</dt>
              <dd><code>${t.summary}</code></dd>
            </dl>
          </div>
          ${this.suggestions.length>0?html`
            <div class="card-header">
              <h3>${lll("label.suggestions")||"Suggestions"}</h3>
            </div>
          `:nothing}
          ${this.suggestions.map((e=>html`
            <div class="card-body">
              <h4>${e.label||e.identifier}</h4>
              ${e.collection.mutations.map((t=>html`
                <p>
                  <i>${t.mode}</i>
                  <code>${t.directive}: ${t.sources.join(" ")}</code>
                </p>
              `))}
              <button class="btn btn-primary" @click=${()=>this.invokeMutateReportAction(t,e)}>
                <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
                ${lll("button.apply")||"Apply"}
              </button>
            </div>
          `))}

          <div class="card-footer">
            <button class="btn btn-default" @click=${()=>this.selectReport(null)}>
              <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
              ${lll("button.close")||"Close"}
            </button>
            <button class="btn btn-default" @click=${()=>this.invokeMuteReportAction(t)}>
              <typo3-backend-icon identifier="actions-ban" size="small"></typo3-backend-icon>
              ${lll("button.mute")||"Mute"}
            </button>
            <button class="btn btn-default" @click=${()=>this.invokeDeleteReportAction(t)}>
              <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
              ${lll("button.delete")||"Delete"}
            </button>
          </div>
        </div>
      </div>
    `:nothing}`}selectReport(t){this.suggestions=[],null!==t&&this.selectedReport!==t?(this.selectedReport=t,this.invokeHandleReportAction(t).then((t=>this.suggestions=t))):this.selectedReport=null}selectScope(t){this.selectedScope=t,this.fetchReports()}fetchReports(){this.invokeFetchReportsAction().then((t=>this.reports=t))}filterReports(...t){t.includes(this.selectedReport?.uuid)&&(this.selectedReport=null),this.reports=this.reports.filter((e=>!t.includes(e.uuid)))}invokeFetchReportsAction(){return new AjaxRequest(this.controlUri).post({action:"fetchReports",scope:this.selectedScope||""}).then((t=>t.resolve("application/json")))}invokeHandleReportAction(t){return new AjaxRequest(this.controlUri).post({action:"handleReport",uuid:t.uuid}).then((t=>t.resolve("application/json")))}invokeMutateReportAction(t,e){const i=this.reports.filter((t=>t.mutationHashes.includes(e.hash))).map((t=>t.summary));return new AjaxRequest(this.controlUri).post({action:"mutateReport",scope:t.scope,hmac:e.hmac,suggestion:e,summaries:i}).then((t=>t.resolve("application/json"))).then((t=>this.filterReports(...t.uuids)))}invokeMuteReportAction(t){new AjaxRequest(this.controlUri).post({action:"muteReport",summary:t.summary}).then((t=>t.resolve("application/json"))).then((t=>this.filterReports(...t.uuids)))}invokeDeleteReportAction(t){new AjaxRequest(this.controlUri).post({action:"deleteReport",summary:t.summary}).then((t=>t.resolve("application/json"))).then((t=>this.filterReports(...t.uuids)))}shortenUri(t){if("inline"===t)return t;try{return new URL(t).hostname}catch(e){return t}}};__decorate([property({type:Array})],CspReports.prototype,"scopes",void 0),__decorate([property({type:String})],CspReports.prototype,"controlUri",void 0),__decorate([state()],CspReports.prototype,"selectedScope",void 0),__decorate([state()],CspReports.prototype,"reports",void 0),__decorate([state()],CspReports.prototype,"selectedReport",void 0),__decorate([state()],CspReports.prototype,"suggestions",void 0),CspReports=__decorate([customElement("typo3-backend-security-csp-reports")],CspReports);export{CspReports};