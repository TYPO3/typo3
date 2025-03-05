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
var __decorate=function(e,t,i,r){var f,o=arguments.length,l=o<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,i,r);else for(var s=e.length-1;s>=0;s--)(f=e[s])&&(l=(o<3?f(l):o>3?f(t,i,l):f(t,i))||l);return o>3&&l&&Object.defineProperty(t,i,l),l};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import{repeat}from"lit/directives/repeat.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";let DiffViewElement=class extends LitElement{constructor(){super(...arguments),this.diffs=[]}createRenderRoot(){return this}render(){return html`<div class="diff">${repeat(this.diffs,(e=>e.field),(e=>this.renderDiffItem(e)))}</div>`}renderDiffItem(e){return html`<div class="diff-item"><div class="diff-item-title">${e.label}</div><div class="diff-item-result">${unsafeHTML(e.content)}</div></div>`}};__decorate([property({type:Array})],DiffViewElement.prototype,"diffs",void 0),DiffViewElement=__decorate([customElement("typo3-workspaces-diff-view")],DiffViewElement);export{DiffViewElement};