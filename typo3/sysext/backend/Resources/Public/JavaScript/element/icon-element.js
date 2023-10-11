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
var __decorate=function(t,e,i,o){var r,n=arguments.length,s=n<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,i,o);else for(var a=t.length-1;a>=0;a--)(r=t[a])&&(s=(n<3?r(s):n>3?r(e,i,s):r(e,i))||s);return n>3&&s&&Object.defineProperty(e,i,s),s};import{html,LitElement,nothing}from"lit";import{Task}from"@lit/task";import{customElement,property}from"lit/decorators.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{Sizes,States,MarkupIdentifiers}from"@typo3/backend/enum/icon-types.js";import Icons,{IconStyles}from"@typo3/backend/icons.js";import"@typo3/backend/element/spinner-element.js";let IconElement=class extends LitElement{constructor(){super(...arguments),this.size=Sizes.default,this.state=States.default,this.overlay=null,this.markup=MarkupIdentifiers.inline,this.raw=null,this.iconTask=new Task(this,{task:async([t,e,i,o,r],{signal:n})=>await Icons.getIcon(t,e,i,o,r,n),args:()=>[this.identifier,this.size,this.overlay,this.state,this.markup]})}render(){return this.raw?html`${unsafeHTML(this.raw)}`:this.identifier?this.iconTask.render({pending:()=>html`<typo3-backend-spinner size=${this.size}></typo3-backend-size>`,complete:t=>html`<div class="icon-wrapper">${unsafeHTML(t)}</div>`,error:()=>html`
        <div class="icon-wrapper">
          <span class="t3js-icon icon icon-size-${this.size} icon-state-${this.state} icon-default-not-found" data-identifier="default-not-found" aria-hidden="true">
	          <span class="icon-markup">
              <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16"><g><path fill="#CD201F" d="m11 12 3-2v6H2v-6l3 2 3-2 3 2z"/><path fill="#212121" d="m8 10.3 2.86 1.91.14.09.14-.09 2.61-1.74v5.28H2.25v-5.28l2.61 1.74.14.09.14-.09L8 10.3m6-.3-3 2-3-2-3 2-3-2v6h12v-6z" opacity=".2"/><path fill="#CD201F" d="M14 4v4l-3 2-3-2-3 2-3-2V0h8l4 4z"/><path fill="#212121" d="M13.75 7.87 11 9.7 8.14 7.79 8 7.7l-.14.09L5 9.7 2.25 7.87V.25H10V0H2v8l3 2 3-2 3 2 3-2V4h-.25z" opacity=".2"/><path fill="#FFF" d="M14 4h-4V0l4 4z" opacity=".3"/><path fill="#212121" d="m14 8-4-4h4v4z" opacity=".3"/></g></svg>
	          </span>
          </span>
        </div>
      `}):nothing}};IconElement.styles=IconStyles.getStyles(),__decorate([property({type:String})],IconElement.prototype,"identifier",void 0),__decorate([property({type:String,reflect:!0})],IconElement.prototype,"size",void 0),__decorate([property({type:String})],IconElement.prototype,"state",void 0),__decorate([property({type:String})],IconElement.prototype,"overlay",void 0),__decorate([property({type:String})],IconElement.prototype,"markup",void 0),__decorate([property({type:String})],IconElement.prototype,"raw",void 0),IconElement=__decorate([customElement("typo3-backend-icon")],IconElement);export{IconElement};