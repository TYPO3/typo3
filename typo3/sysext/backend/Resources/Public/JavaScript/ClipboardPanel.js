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
var __decorate=this&&this.__decorate||function(e,t,a,i){var n,l=arguments.length,o=l<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,a):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,a,i);else for(var r=e.length-1;r>=0;r--)(n=e[r])&&(o=(l<3?n(o):l>3?n(t,a,o):n(t,a))||o);return l>3&&o&&Object.defineProperty(t,a,o),o};define(["require","exports","lit","lit/decorators","lit/directives/until","lit/directives/unsafe-html","lit/directives/class-map","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Backend/Element/SpinnerElement","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,a,i,n,l,o,r,c){"use strict";var s,d;Object.defineProperty(t,"__esModule",{value:!0}),t.ClipboardPanel=void 0,function(e){e.cut="cut",e.copy="copy"}(d||(d={}));let p=s=class extends a.LitElement{constructor(){super(...arguments),this.returnUrl="",this.table=""}static renderLoader(){return a.html`
      <div class="panel-loader">
        <typo3-backend-spinner size="small" variant="dark"></typo3-backend-spinner>
      </div>
    `}createRenderRoot(){return this}render(){return a.html`
      <div class="clipboard-panel">
        ${(0,n.until)(this.renderPanel(),s.renderLoader())}
      </div>
    `}renderPanel(){return new r(top.TYPO3.settings.Clipboard.moduleUrl).withQueryArguments({action:"getClipboardData"}).post({table:this.table}).then(async e=>{const t=await e.resolve();if(!0===t.success&&t.data){const e=t.data;return a.html`
            <div class="panel panel-default">
              <div class="panel-heading">
                ${e.labels.clipboard}
              </div>
              <table class="table">
                <tbody>
                  ${e.tabs.map(t=>this.renderTab(t,e))}
                </tbody>
              </tabel>
            </div>
          `}return c.error("Clipboard data could not be fetched"),a.html``}).catch(()=>(c.error("An error occured while fetching clipboard data"),a.html``))}renderTab(e,t){return a.html`
      <tr>
        <td colspan="2" class="nowrap">
          <button type="button" class="btn btn-link p-0" title="${e.description}" data-action="setP" @click="${t=>this.updateClipboard(t,{CB:{setP:e.identifier}})}">
            ${t.current===e.identifier?a.html`
              <typo3-backend-icon identifier="actions-check-circle-alt" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
              ${e.title}
              ${e.info}`:a.html`
              <typo3-backend-icon identifier="actions-circle" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
              <span class="text-muted">
                ${e.title}
                ${e.info}
              </span>
            `}
          </button>
        </td>
        <td class="col-control nowrap">
          ${t.current!==e.identifier?a.html``:a.html`
            <div class="btn-group">
              <input type="radio" class="btn-check" id="clipboard-copymode-copy" data-action="setCopyMode" ?checked=${t.copyMode===d.copy} @click="${e=>this.updateClipboard(e,{CB:{setCopyMode:"1"}})}">
              <label class="btn btn-default btn-sm" for="clipboard-copymode-copy">
                <typo3-backend-icon identifier="actions-edit-copy" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                ${t.labels.copyElements}
              </label>
              <input type="radio" class="btn-check" id="clipboard-copymode-move" data-action="setCopyMode" ?checked=${t.copyMode!==d.copy} @click="${e=>this.updateClipboard(e,{CB:{setCopyMode:"0"}})}">
              <label class="btn btn-default btn-sm" for="clipboard-copymode-move">
                <typo3-backend-icon identifier="actions-cut" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                ${t.labels.moveElements}
              </label>
            </div>
            ${t.elementCount?a.html`
              <button type="button" class="btn btn-default btn-sm" title="${t.labels.removeAll}" data-action="removeAll" @click="${t=>this.updateClipboard(t,{CB:{removeAll:e.identifier}})}">
                <typo3-backend-icon identifier="actions-remove" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                ${t.labels.removeAll}
              </button>`:a.html``}
          `}
        </td>
      </tr>
      ${t.current===e.identifier&&e.items?e.items.map(a=>this.renderTabItem(a,e.identifier,t)):a.html``}
    `}renderTabItem(e,t,i){return a.html`
      <tr>
        <td class="col-icon nowrap ${(0,o.classMap)({"ps-4":!e.identifier})}">
          ${(0,l.unsafeHTML)(e.icon)}
        </td>
        <td class="nowrap" style="width: 95%">
          ${(0,l.unsafeHTML)(e.title)}
          ${"normal"===t?a.html`<strong>(${i.copyMode===d.copy?a.html`${i.labels.copy}`:a.html`${i.labels.cut}`})</strong>`:a.html``}
          ${e.thumb?a.html`<div class="d-block">${(0,l.unsafeHTML)(e.thumb)}</div>`:a.html``}
        </td>
        <td class="col-control nowrap">
          <div class="btn-group">
            ${e.infoDataDispatch?a.html`
              <button type="button" class="btn btn-default btn-sm" data-dispatch-action="${e.infoDataDispatch.action}" data-dispatch-args="${e.infoDataDispatch.args}" title="${i.labels.info}">
                <span>
                  <typo3-backend-icon identifier="actions-document-info" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                </span>
              </button>
            `:a.html``}
            ${e.identifier?a.html`
              <button type="button" class="btn btn-default btn-sm" title="${i.labels.removeItem}" data-action="remove" @click="${t=>this.updateClipboard(t,{CB:{remove:e.identifier}})}">
                <span>
                    <typo3-backend-icon identifier="actions-remove" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                    ${i.labels.removeItem}
                </span>
              </button>
            `:a.html``}
          </div>
        </td>
      </tr>`}updateClipboard(e,t){e.preventDefault();const a=e.currentTarget;new r(top.TYPO3.settings.Clipboard.moduleUrl).post(t).then(async e=>{const i=await e.resolve();!0===i.success?(a.dataset.action&&a.dispatchEvent(new CustomEvent("typo3:clipboard:"+a.dataset.action,{detail:{payload:t,response:i},bubbles:!0,cancelable:!1})),this.reloadModule()):c.error("Clipboard data could not be updated")}).catch(()=>{c.error("An error occured while updating clipboard data")})}reloadModule(){this.returnUrl?this.ownerDocument.location.href=this.returnUrl:this.ownerDocument.location.reload()}};__decorate([(0,i.property)({type:String,attribute:"return-url"})],p.prototype,"returnUrl",void 0),__decorate([(0,i.property)({type:String})],p.prototype,"table",void 0),p=s=__decorate([(0,i.customElement)("typo3-backend-clipboard-panel")],p),t.ClipboardPanel=p}));