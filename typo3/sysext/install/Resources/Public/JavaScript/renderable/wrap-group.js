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
var __decorate=function(t,e,r,o){var p,a=arguments.length,i=a<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(t,e,r,o);else for(var l=t.length-1;l>=0;l--)(p=t[l])&&(i=(a<3?p(i):a>3?p(e,r,i):p(e,r))||i);return a>3&&i&&Object.defineProperty(e,r,i),i};import{LitElement,html}from"lit";import{customElement,property}from"lit/decorators.js";let WrapGroupElement=class extends LitElement{constructor(){super(...arguments),this.wrapId=null,this.values=null}createRenderRoot(){return this}render(){return html`<div class="form-multigroup-wrap"><div class="form-multigroup-item"><div class="input-group"><input id="${this.wrapId}_wrap_start" class="form-control t3js-emconf-wrapfield" data-target="#${this.wrapId}" value="${this.values[0].trim()}"></div></div><div class="form-multigroup-item"><div class="input-group"><input id="${this.wrapId}_wrap_end" class="form-control t3js-emconf-wrapfield" data-target="#${this.wrapId}" value="${this.values[0].trim()}"></div></div></div>`}};__decorate([property({type:String})],WrapGroupElement.prototype,"wrapId",void 0),__decorate([property({type:Array})],WrapGroupElement.prototype,"values",void 0),WrapGroupElement=__decorate([customElement("typo3-install-wrap-group")],WrapGroupElement);