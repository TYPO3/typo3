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
var CspReportAttribute,__decorate=function(e,t,l,o){var i,s=arguments.length,n=s<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,l):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,l,o);else for(var r=e.length-1;r>=0;r--)(i=e[r])&&(n=(s<3?i(n):s>3?i(t,l,n):i(t,l))||n);return s>3&&n&&Object.defineProperty(t,l,n),n};import{customElement,property,state}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import{classMap}from"lit/directives/class-map.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{lll}from"@typo3/core/lit-helper.js";!function(e){e.fixable="fixable",e.irrelevant="irrelevant",e.suspicious="suspicious"}(CspReportAttribute||(CspReportAttribute={}));let CspReports=class extends LitElement{constructor(){super(...arguments),this.selectedScope=null,this.reports=[],this.selectedReport=null,this.suggestions=[]}connectedCallback(){super.connectedCallback(),this.fetchReports()}createRenderRoot(){return this}render(){return html`
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
                ${this.reports.map((e=>html`
                  <tr class=${classMap({"table-info":this.selectedReport===e})} data-mutation-group=${e.mutationHashes.join("-")}
                      @click=${()=>this.selectReport(e)}>
                    <td>${e.created}</td>
                    <td>${e.scope}</td>
                    <td>
                      <span class="badge bg-warning">${e.count}</span>
                      ${e.details.effectiveDirective}
                    </td>
                    <td>${this.shortenUri(e.details.blockedUri)}</td>
                    <td>${e.attributes.join(", ")}</td>
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
        <button type="button" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="${lll("label.scope")||"Scope"}">
          ${null===this.selectedScope?lll("label.all")||"ALL":this.selectedScope}
        </button>
        <ul class="dropdown-menu">
          <button class="dropdown-item dropdown-item-spaced" title="${lll("label.all")||"ALL"}" @click=${()=>this.selectScope(null)}>
            <span class="${null===this.selectedScope?"text-primary":""}">
              <typo3-backend-icon identifier="${null===this.selectedScope?"actions-dot":"empty-empty"}" size="small"></typo3-backend-icon>
            </span>
            ${lll("label.all")||"ALL"}
          </button>
          ${this.scopes.map((e=>html`
            <li>
              <button class="dropdown-item dropdown-item-spaced" title="${e}" @click=${()=>this.selectScope(e)}>
                <span class="${e===this.selectedScope?"text-primary":""}">
                  <typo3-backend-icon identifier="${e===this.selectedScope?"actions-dot":"empty-empty"}" size="small"></typo3-backend-icon>
                </span>
                ${e}
              </button>
            </li>`))}
        </ul>
        <button type="button" class="btn btn-danger mx-3" title="${lll("label.removeAll")||"Remove all"}" @click=${()=>this.invokeDeleteReportsAction()}>
          ${lll("label.removeAll")||"Remove all"}
          ${null!==this.selectedScope?html`"${this.selectedScope}"`:nothing}
        </button>
      </div>`}renderGuide(){return html`${this.selectedReport?nothing:html`
      <div class="infolist-info-norecord">
        <div class="card mb-0">
          <div class="card-body">
            <p>${lll("label.guide.no_record_selected")||"Select a row to see more information."}</p>
          </div>
        </div>
      </div>
    `}`}renderSelectedReport(){const e=this.selectedReport;return html`${e?html`
      <div class="infolist-info-record">
        <div class="card mb-0">
          <div class="card-header">
            <h3>${lll("label.details")||"Details"}</h3>
          </div>
          <div class="card-body">
            <dl>
              <dt>${lll("label.directive")||"Directive"} / ${lll("label.disposition")||"Disposition"}</dt>
              <dd>${e.details.effectiveDirective} / ${e.details.disposition}</dd>

              <dt>${lll("label.document_uri")||"Document URI"}</dt>
              <dd>${e.details.documentUri} ${this.renderCodeLocation(e)}</dd>

              ${e.details.sourceFile&&e.details.sourceFile!==e.details.documentUri?html`
                <dt>${lll("label.source_file")||"Source File"}</dt>
                <dd>${e.details.sourceFile}</dd>
              `:nothing}

              <dt>${lll("label.blocked_uri")||"Blocked URI"}</dt>
              <dd>${e.details.blockedUri}</dd>

              ${e.details.scriptSample?html`
                <dt>${lll("label.sample")||"Sample"}</dt>
                <dd><code>${e.details.scriptSample}</code></dd>
              `:nothing}

              ${e.meta.agent?html`
                <dt>${lll("label.user_agent")||"User Agent"}</dt>
                <dd><code>${e.meta.agent}</code></dd>
              `:nothing}

              <dt>${lll("label.uuid")||"UUID"}</dt>
              <dd><code>${e.uuid}</code></dd>

              <dt>${lll("label.summary")||"Summary"}</dt>
              <dd><code>${e.summary}</code></dd>
            </dl>
          </div>
          ${this.suggestions.length>0?html`
            <div class="card-header">
              <h3>${lll("label.suggestions")||"Suggestions"}</h3>
            </div>
          `:nothing}
          ${this.suggestions.map((t=>html`
            <div class="card-body">
              <h4>${t.label||t.identifier}</h4>
              ${t.collection.mutations.map((e=>html`
                <p>
                  <i>${e.mode}</i>
                  <code>${e.directive}: ${e.sources.join(" ")}</code>
                </p>
              `))}
              <button class="btn btn-primary" @click=${()=>this.invokeMutateReportAction(e,t)}>
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
            <button class="btn btn-default" @click=${()=>this.invokeMuteReportAction(e)}>
              <typo3-backend-icon identifier="actions-ban" size="small"></typo3-backend-icon>
              ${lll("button.mute")||"Mute"}
            </button>
            <button class="btn btn-default" @click=${()=>this.invokeDeleteReportAction(e)}>
              <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
              ${lll("button.delete")||"Delete"}
            </button>
          </div>
        </div>
      </div>
    `:nothing}`}renderCodeLocation(e){if(!e.details.lineNumber)return nothing;const t=[e.details.lineNumber];return e.details.columnNumber&&t.push(e.details.columnNumber),html`(${t.join(":")})`}selectReport(e){this.suggestions=[],null!==e&&this.selectedReport!==e?(this.selectedReport=e,this.invokeHandleReportAction(e).then((e=>this.suggestions=e))):this.selectedReport=null}selectScope(e){this.selectedScope=e,this.fetchReports()}fetchReports(){this.invokeFetchReportsAction().then((e=>this.reports=e))}filterReports(...e){e.includes(this.selectedReport?.uuid)&&(this.selectedReport=null),this.reports=this.reports.filter((t=>!e.includes(t.uuid)))}invokeFetchReportsAction(){return new AjaxRequest(this.controlUri).post({action:"fetchReports",scope:this.selectedScope||""}).then((e=>e.resolve("application/json")))}invokeHandleReportAction(e){return new AjaxRequest(this.controlUri).post({action:"handleReport",uuid:e.uuid}).then((e=>e.resolve("application/json")))}invokeMutateReportAction(e,t){const l=this.reports.filter((e=>e.mutationHashes.includes(t.hash))).map((e=>e.summary));return new AjaxRequest(this.controlUri).post({action:"mutateReport",scope:e.scope,hmac:t.hmac,suggestion:t,summaries:l}).then((e=>e.resolve("application/json"))).then((e=>this.filterReports(...e.uuids)))}invokeMuteReportAction(e){new AjaxRequest(this.controlUri).post({action:"muteReport",summaries:[e.summary]}).then((e=>e.resolve("application/json"))).then((e=>this.filterReports(...e.uuids)))}invokeDeleteReportAction(e){new AjaxRequest(this.controlUri).post({action:"deleteReport",summaries:[e.summary]}).then((e=>e.resolve("application/json"))).then((e=>this.filterReports(...e.uuids)))}invokeDeleteReportsAction(){new AjaxRequest(this.controlUri).post({action:"deleteReports",scope:this.selectedScope||""}).then((e=>e.resolve("application/json"))).then((()=>this.fetchReports())).then((()=>this.selectReport(null)))}shortenUri(e){if("inline"===e)return e;try{return new URL(e).hostname}catch(t){return e}}};__decorate([property({type:Array})],CspReports.prototype,"scopes",void 0),__decorate([property({type:String})],CspReports.prototype,"controlUri",void 0),__decorate([state()],CspReports.prototype,"selectedScope",void 0),__decorate([state()],CspReports.prototype,"reports",void 0),__decorate([state()],CspReports.prototype,"selectedReport",void 0),__decorate([state()],CspReports.prototype,"suggestions",void 0),CspReports=__decorate([customElement("typo3-backend-security-csp-reports")],CspReports);export{CspReports};