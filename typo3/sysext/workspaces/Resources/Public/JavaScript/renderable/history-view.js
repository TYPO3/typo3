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
var __decorate=function(e,t,i,r){var s,d=arguments.length,o=d<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,i,r);else for(var l=e.length-1;l>=0;l--)(s=e[l])&&(o=(d<3?s(o):d>3?s(t,i,o):s(t,i))||o);return d>3&&o&&Object.defineProperty(t,i,o),o};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import{repeat}from"lit/directives/repeat.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";let HistoryViewElement=class extends LitElement{constructor(){super(...arguments),this.historyItems=[]}createRenderRoot(){return this}render(){return html`<div>${repeat(this.historyItems,(e=>e.datetime),(e=>this.renderHistoryItem(e)))}</div>`}renderHistoryItem(e){return"object"==typeof e.differences&&0===e.differences.length?nothing:html`<div class="media"><div class="media-left text-center"><div>${unsafeHTML(e.user_avatar)}</div>${e.user}</div><div class="media-body"><div class="panel panel-default">${"object"==typeof e.differences?html`<div><div class="diff">${repeat(e.differences,(e=>e),(e=>html`<div class="diff-item"><div class="diff-item-title">${e.label}</div><div class="diff-item-result diff-item-result-inline">${unsafeHTML(e.html)}</div></div>`))}</div></div>`:html`<div class="panel-body">${e.differences}</div>`}<div class="panel-footer"><span class="badge badge-info">${e.datetime}</span></div></div></div></div>`}};__decorate([property({type:Array})],HistoryViewElement.prototype,"historyItems",void 0),HistoryViewElement=__decorate([customElement("typo3-workspaces-history-view")],HistoryViewElement);export{HistoryViewElement};