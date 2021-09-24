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
var __decorate=this&&this.__decorate||function(e,t,i,a){var n,l=arguments.length,o=l<3?t:null===a?a=Object.getOwnPropertyDescriptor(t,i):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,i,a);else for(var r=e.length-1;r>=0;r--)(n=e[r])&&(o=(l<3?n(o):l>3?n(t,i,o):n(t,i))||o);return l>3&&o&&Object.defineProperty(t,i,o),o};define(["require","exports","lit","lit/decorators","lit/directives/until","lit/directives/unsafe-html","lit/directives/class-map","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Backend/Element/SpinnerElement","TYPO3/CMS/Backend/Element/IconElement"],(function(e,t,i,a,n,l,o,r,c){"use strict";var s,d;Object.defineProperty(t,"__esModule",{value:!0}),t.ClipboardPanel=void 0,function(e){e.cut="cut",e.copy="copy"}(d||(d={}));let p=s=class extends i.LitElement{constructor(){super(...arguments),this.returnUrl="",this.table=""}static renderLoader(){return i.html`
      <div class="panel-loader">
        <typo3-backend-spinner size="small" variant="dark"></typo3-backend-spinner>
      </div>
    `}createRenderRoot(){return this}render(){return i.html`
      <div class="clipboard-panel">
        ${n.until(this.renderPanel(),s.renderLoader())}
      </div>
    `}renderPanel(){return new r(top.TYPO3.settings.Clipboard.moduleUrl).withQueryArguments({action:"getClipboardData"}).post({table:this.table}).then(async e=>{const t=await e.resolve();if(!0===t.success&&t.data){const e=t.data;return i.html`
            <div class="row">
              <div class="col-sm-12">
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
              </div>
            </div>
          `}return c.error("Clipboard data could not be fetched"),i.html``}).catch(()=>(c.error("An error occured while fetching clipboard data"),i.html``))}renderTab(e,t){return i.html`
      <tr>
        <td colspan="2" class="nowrap">
          <button type="button" class="btn btn-link p-0" title="${e.description}" data-action="setP" @click="${t=>this.updateClipboard(t,{CB:{setP:e.identifier}})}">
            ${t.current===e.identifier?i.html`
              <typo3-backend-icon identifier="actions-check-circle-alt" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
              ${e.title}
              ${e.info}`:i.html`
              <typo3-backend-icon identifier="actions-circle" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
              <span class="text-muted">
                ${e.title}
                ${e.info}
              </span>
            `}
          </button>
        </td>
        <td class="col-control nowrap">
          ${t.current!==e.identifier?i.html``:i.html`
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
            ${t.elementCount?i.html`
              <button type="button" class="btn btn-default btn-sm" title="${t.labels.removeAll}" data-action="removeAll" @click="${t=>this.updateClipboard(t,{CB:{removeAll:e.identifier}})}">
                <typo3-backend-icon identifier="actions-remove" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                ${t.labels.removeAll}
              </button>`:i.html``}
          `}
        </td>
      </tr>
      ${t.current===e.identifier&&e.items?e.items.map(i=>this.renderTabItem(i,e.identifier,t)):i.html``}
    `}renderTabItem(e,t,a){return i.html`
      <tr>
        <td class="col-icon nowrap ${o.classMap({"ps-4":!e.identifier})}">
          ${l.unsafeHTML(e.icon)}
        </td>
        <td class="nowrap" style="width: 95%">
          ${l.unsafeHTML(e.title)}
          ${"normal"===t?i.html`<strong>(${a.copyMode===d.copy?i.html`${a.labels.copy}`:i.html`${a.labels.cut}`})</strong>`:i.html``}
          ${e.thumb?i.html`<div class="d-block">${l.unsafeHTML(e.thumb)}</div>`:i.html``}
        </td>
        <td class="col-control nowrap">
          <div class="btn-group">
            ${e.infoDataDispatch?i.html`
              <button type="button" class="btn btn-default btn-sm" data-dispatch-action="${e.infoDataDispatch.action}" data-dispatch-args="${e.infoDataDispatch.args}" title="${a.labels.info}">
                <span>
                  <typo3-backend-icon identifier="actions-document-info" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                </span>
              </button>
            `:i.html``}
            ${e.identifier?i.html`
              <button type="button" class="btn btn-default btn-sm" title="${a.labels.removeItem}" data-action="remove" @click="${t=>this.updateClipboard(t,{CB:{remove:e.identifier}})}">
                <span>
                    <typo3-backend-icon identifier="actions-remove" alternativeMarkupIdentifier="inline" size="small" class="icon icon-size-small"></typo3-backend-icon>
                    ${a.labels.removeItem}
                </span>
              </button>
            `:i.html``}
          </div>
        </td>
      </tr>`}updateClipboard(e,t){e.preventDefault();const i=e.currentTarget;new r(top.TYPO3.settings.Clipboard.moduleUrl).post(t).then(async e=>{const a=await e.resolve();!0===a.success?(i.dataset.action&&i.dispatchEvent(new CustomEvent("typo3:clipboard:"+i.dataset.action,{detail:{payload:t,response:a},bubbles:!0,cancelable:!1})),this.reloadModule()):c.error("Clipboard data could not be updated")}).catch(()=>{c.error("An error occured while updating clipboard data")})}reloadModule(){this.returnUrl?this.ownerDocument.location.href=this.returnUrl:this.ownerDocument.location.reload(!0)}};__decorate([a.property({type:String,attribute:"return-url"})],p.prototype,"returnUrl",void 0),__decorate([a.property({type:String})],p.prototype,"table",void 0),p=s=__decorate([a.customElement("typo3-backend-clipboard-panel")],p),t.ClipboardPanel=p}));