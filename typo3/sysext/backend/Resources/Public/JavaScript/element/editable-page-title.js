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
var __decorate=function(t,e,i,o){var a,r=arguments.length,n=r<3?e:null===o?o=Object.getOwnPropertyDescriptor(e,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,i,o);else for(var d=t.length-1;d>=0;d--)(a=t[d])&&(n=(r<3?a(n):r>3?a(e,i,n):a(e,i))||n);return r>3&&n&&Object.defineProperty(e,i,n),n};import{lll}from"@typo3/core/lit-helper.js";import{html,css,LitElement}from"lit";import{customElement,property,state}from"lit/decorators.js";import"@typo3/backend/element/icon-element.js";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";let EditablePageTitle=class extends LitElement{constructor(){super(...arguments),this.pageTitle="",this.pageId=0,this.localizedPageId=0,this.editable=!1,this._isEditing=!1,this._isSubmitting=!1}async startEditing(){this.isEditable()&&(this._isEditing=!0,await this.updateComplete,this.shadowRoot.querySelector("input")?.focus())}render(){if(""===this.pageTitle)return html``;if(!this.isEditable())return html`<div class="wrapper"><h1>${this.pageTitle}</h1></div>`;let t;return t=this._isEditing?this.composeEditForm():html`
        <div class="wrapper">
          <h1 @dblclick="${()=>{this.startEditing()}}">${this.pageTitle}</h1>
          ${this.composeEditButton()}
        </div>`,t}isEditable(){return this.editable&&this.pageId>0}endEditing(){this.isEditable()&&(this._isEditing=!1)}updatePageTitle(t){t.preventDefault();const e=new FormData(t.target),i=Object.fromEntries(e).newPageTitle.toString();if(this.pageTitle===i)return void this.endEditing();this._isSubmitting=!0;let o,a={};o=this.localizedPageId>0?this.localizedPageId:this.pageId,a.data={pages:{[o]:{title:i}}},AjaxDataHandler.process(a).then((()=>{this.pageTitle=i,top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))})).finally((()=>{this.endEditing(),this._isSubmitting=!1}))}composeEditButton(){return html`
      <button data-action="edit" type="button" aria-label="${lll("editPageTitle")}" @click="${()=>{this.startEditing()}}">
        <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
      </button>`}composeEditForm(){return html`
      <form class="wrapper" @submit="${this.updatePageTitle}">
        <input autocomplete="off" name="newPageTitle" ?disabled="${this._isSubmitting}" value="${this.pageTitle}" @keydown="${t=>{"Escape"===t.key&&this.endEditing()}}">
        <button data-action="save" type="submit" ?disabled="${this._isSubmitting}">
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
        </button>
        <button data-action="close" type="button" ?disabled="${this._isSubmitting}" @click="${()=>{this.endEditing()}}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </button>
      </form>`}};EditablePageTitle.styles=css`
    :host {
      display: block;
      --border-color: #bebebe;
      --hover-bg: #cacaca;
      --hover-border-color: #bebebe;
      --focus-bg: #cacaca;
      --focus-border-color: #bebebe;
    }

    h1 {
      display: block;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      padding: 1px 0;
      margin: 0;
    }

    input {
      outline: none;
      background: transparent;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      padding: 0;
      border: 0;
      border-top: 1px solid transparent;
      border-bottom: 1px dashed var(--border-color);
      margin: 0;
      width: 100%;
    }

    input:hover {
      border-bottom: 1px dashed var(--hover-border-color);
    }

    input:focus {
      border-bottom: 1px dashed var(--focus-border-color);
    }

    .wrapper {
      position: relative;
      margin: -1px 0;
    }

    div.wrapper {
      padding-right: 1.5em;
    }

    form.wrapper {
      padding-right: 2.5em;
    }

    button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: inherit;
      line-height: inherit;
      border: 0;
      padding: 0;
      height: 100%;
      width: 1em;
      position: absolute;
      top: 0;
      border-radius: 2px;
      overflow: hidden;
      outline: none;
      border: 1px solid transparent;
      background: transparent;
      opacity: .3;
      transition: all .2s ease-in-out;
    }

    button:hover {
      opacity: 1;
      background: var(--hover-bg);
      border-color: var(--hover-border-color);
    }

    button:focus {
      opacity: 1;
      background: var(--focus-bg);
      border-color: var(--focus-border-color);
    }

    button[data-action="edit"] {
      right: 0;
    }

    button[data-action="save"] {
      right: calc(1em + 2px);
    }

    button[data-action="close"] {
      right: 0;
    }
    `,__decorate([property({type:String})],EditablePageTitle.prototype,"pageTitle",void 0),__decorate([property({type:Number})],EditablePageTitle.prototype,"pageId",void 0),__decorate([property({type:Number})],EditablePageTitle.prototype,"localizedPageId",void 0),__decorate([property({type:Boolean})],EditablePageTitle.prototype,"editable",void 0),__decorate([state()],EditablePageTitle.prototype,"_isEditing",void 0),__decorate([state()],EditablePageTitle.prototype,"_isSubmitting",void 0),EditablePageTitle=__decorate([customElement("typo3-backend-editable-page-title")],EditablePageTitle);