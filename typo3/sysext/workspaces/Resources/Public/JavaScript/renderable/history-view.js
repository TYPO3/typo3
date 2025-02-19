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
import{property as p,customElement as m}from"lit/decorators.js";import{LitElement as u,html as r,nothing as b}from"lit";import{repeat as a}from"lit/directives/repeat.js";import{unsafeHTML as c}from"lit/directives/unsafe-html.js";var v=function(d,e,i,s){var n=arguments.length,t=n<3?e:s===null?s=Object.getOwnPropertyDescriptor(e,i):s,f;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(d,e,i,s);else for(var o=d.length-1;o>=0;o--)(f=d[o])&&(t=(n<3?f(t):n>3?f(e,i,t):f(e,i))||t);return n>3&&t&&Object.defineProperty(e,i,t),t};let l=class extends u{constructor(){super(...arguments),this.historyItems=[]}createRenderRoot(){return this}render(){return r`<div>${a(this.historyItems,e=>e.datetime,e=>this.renderHistoryItem(e))}</div>`}renderHistoryItem(e){return typeof e.differences=="object"&&e.differences.length===0?b:r`<div class=media><div class="media-left text-center"><div>${c(e.user_avatar)}</div>${e.user}</div><div class=media-body><div class="panel panel-default">${typeof e.differences=="object"?r`<div><div class=diff>${a(e.differences,i=>i,i=>r`<div class=diff-item><div class=diff-item-title>${i.label}</div><div class="diff-item-result diff-item-result-inline">${c(i.html)}</div></div>`)}</div></div>`:r`<div class=panel-body>${e.differences}</div>`}<div class=panel-footer><span class="badge badge-info"> ${e.datetime} </span></div></div></div></div>`}};v([p({type:Array})],l.prototype,"historyItems",void 0),l=v([m("typo3-workspaces-history-view")],l);export{l as HistoryViewElement};
