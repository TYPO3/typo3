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
var ClipboardPanel_1,CopyMode,__decorate=function(t,e,o,a){var i,n=arguments.length,l=n<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,o):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(t,e,o,a);else for(var r=t.length-1;r>=0;r--)(i=t[r])&&(l=(n<3?i(l):n>3?i(e,o,l):i(e,o))||l);return n>3&&l&&Object.defineProperty(e,o,l),l};import{html,LitElement,nothing}from"lit";import{customElement,property}from"lit/decorators.js";import{until}from"lit/directives/until.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{classMap}from"lit/directives/class-map.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/element/icon-element.js";!function(t){t.cut="cut",t.copy="copy"}(CopyMode||(CopyMode={}));let ClipboardPanel=ClipboardPanel_1=class extends LitElement{constructor(){super(...arguments),this.returnUrl="",this.table=""}static renderLoader(){return html`
      <div class="panel panel-default">
        <div class="panel-loader">
          <typo3-backend-spinner size="small" variant="dark"></typo3-backend-spinner>
        </div>
      </div>
    `}createRenderRoot(){return this}render(){return html`
      ${until(this.renderPanel(),ClipboardPanel_1.renderLoader())}
    `}renderPanel(){return new AjaxRequest(top.TYPO3.settings.Clipboard.moduleUrl).withQueryArguments({action:"getClipboardData"}).post({table:this.table}).then((async t=>{const e=await t.resolve();if(!0===e.success&&e.data){const t=e.data;return html`
            <div class="panel panel-default" data-clipboard-panel>
              <div class="panel-heading">
                ${t.labels.clipboard}
              </div>
              <div class="table-fit">
                <table class="table">
                  <tbody>
                    ${t.tabs.map((e=>this.renderTab(e,t)))}
                  </tbody>
                </tabel>
              </div>
            </div>
          `}return html`
            <div class="alert alert-danger">Clipboard data could not be fetched</div>
          `})).catch((()=>html`
          <div class="alert alert-danger">An error occurred while fetching clipboard data</div>
        `))}renderTab(t,e){return html`
      <tr>
        <td colspan="2" class="nowrap">
          <button type="button" class="btn btn-link" title="${t.description}" data-action="setP" @click="${e=>this.updateClipboard(e,{CB:{setP:t.identifier}})}">
            ${e.current===t.identifier?html`
              <typo3-backend-icon identifier="actions-check-circle-alt" size="small"></typo3-backend-icon>
              ${t.title}
              ${t.info}`:html`
              <typo3-backend-icon identifier="actions-circle" size="small"></typo3-backend-icon>
              <span class="text-body-secondary">
                ${t.title}
                ${t.info}
              </span>
            `}
          </button>
        </td>
        <td class="col-control nowrap">
          ${e.current!==t.identifier?nothing:html`
            <div class="btn-group">
              <input type="radio" class="btn-check" id="clipboard-copymode-copy" data-action="setCopyMode" ?checked=${e.copyMode===CopyMode.copy} @click="${t=>this.updateClipboard(t,{CB:{setCopyMode:"1"}})}">
              <label class="btn btn-default btn-sm" for="clipboard-copymode-copy">
                <typo3-backend-icon identifier="actions-edit-copy" size="small"></typo3-backend-icon>
                ${e.labels.copyElements}
              </label>
              <input type="radio" class="btn-check" id="clipboard-copymode-move" data-action="setCopyMode" ?checked=${e.copyMode!==CopyMode.copy} @click="${t=>this.updateClipboard(t,{CB:{setCopyMode:"0"}})}">
              <label class="btn btn-default btn-sm" for="clipboard-copymode-move">
                <typo3-backend-icon identifier="actions-cut" size="small"></typo3-backend-icon>
                ${e.labels.moveElements}
              </label>
            </div>
            ${e.elementCount?html`
              <button type="button" class="btn btn-default btn-sm" title="${e.labels.removeAll}" data-action="removeAll" @click="${e=>this.updateClipboard(e,{CB:{removeAll:t.identifier}})}">
                <typo3-backend-icon identifier="actions-minus" size="small"></typo3-backend-icon>
                ${e.labels.removeAll}
              </button>`:nothing}
          `}
        </td>
      </tr>
      ${e.current===t.identifier&&t.items?t.items.map((o=>this.renderTabItem(o,t.identifier,e))):nothing}
    `}renderTabItem(t,e,o){return html`
      <tr>
        <td class="col-icon nowrap ${classMap({"ps-4":!t.identifier})}">
          ${unsafeHTML(t.icon)}
        </td>
        <td class="nowrap" style="width: 95%">
          ${unsafeHTML(t.title)}
          ${"normal"===e?html`<strong>(${o.copyMode===CopyMode.copy?html`${o.labels.copy}`:html`${o.labels.cut}`})</strong>`:nothing}
          ${t.thumb?html`<div class="d-block">${unsafeHTML(t.thumb)}</div>`:nothing}
        </td>
        <td class="col-control nowrap">
          <div class="btn-group">
            ${t.infoDataDispatch?html`
              <button type="button" class="btn btn-default btn-sm" data-dispatch-action="${t.infoDataDispatch.action}" data-dispatch-args="${t.infoDataDispatch.args}" title="${o.labels.info}">
                <typo3-backend-icon identifier="actions-document-info" size="small"></typo3-backend-icon>
              </button>
            `:nothing}
            ${t.identifier?html`
              <button type="button" class="btn btn-default btn-sm" title="${o.labels.removeItem}" data-action="remove" @click="${e=>this.updateClipboard(e,{CB:{remove:t.identifier}})}">
                <typo3-backend-icon identifier="actions-minus" size="small"></typo3-backend-icon>
                ${o.labels.removeItem}
              </button>
            `:nothing}
          </div>
        </td>
      </tr>`}updateClipboard(t,e){t.preventDefault();const o=t.currentTarget;new AjaxRequest(top.TYPO3.settings.Clipboard.moduleUrl).post(e).then((async t=>{const a=await t.resolve();!0===a.success?(o.dataset.action&&o.dispatchEvent(new CustomEvent("typo3:clipboard:"+o.dataset.action,{detail:{payload:e,response:a},bubbles:!0,cancelable:!1})),this.reloadModule()):Notification.error("Clipboard data could not be updated")})).catch((()=>{Notification.error("An error occurred while updating clipboard data")}))}reloadModule(){this.returnUrl?this.ownerDocument.location.href=this.returnUrl:this.ownerDocument.location.reload()}};__decorate([property({type:String,attribute:"return-url"})],ClipboardPanel.prototype,"returnUrl",void 0),__decorate([property({type:String})],ClipboardPanel.prototype,"table",void 0),ClipboardPanel=ClipboardPanel_1=__decorate([customElement("typo3-backend-clipboard-panel")],ClipboardPanel);export{ClipboardPanel};