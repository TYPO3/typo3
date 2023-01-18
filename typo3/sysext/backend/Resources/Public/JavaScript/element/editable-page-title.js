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
var __decorate=function(t,e,i,a){var o,l=arguments.length,s=l<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,i):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,i,a);else for(var d=t.length-1;d>=0;d--)(o=t[d])&&(s=(l<3?o(s):l>3?o(e,i,s):o(e,i))||s);return l>3&&s&&Object.defineProperty(e,i,s),s};import{lll}from"@typo3/core/lit-helper.js";import{html,LitElement}from"lit";import{customElement,property,state}from"lit/decorators.js";import"@typo3/backend/element/icon-element.js";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";let EditablePageTitle=class extends LitElement{constructor(){super(...arguments),this.pageTitle="",this.pageId=0,this.localizedPageId=0,this.editable=!1,this._isEditing=!1,this._isSubmitting=!1}createRenderRoot(){return this}render(){if(""===this.pageTitle)return html``;const t=html`<h1 @dblclick="${()=>{this.startEditing()}}">${this.pageTitle}</h1>`;if(!this.isEditable())return t;let e;return e=this._isEditing?this.composeEditForm():html`<div class="row">
        <div class="col-md-auto">
          ${t}
        </div>
        <div class="col">
          ${this.composeEditButton()}
        </div>
      </div>`,e}isEditable(){return this.editable&&this.pageId>0}startEditing(){this.isEditable()&&(this._isEditing=!0)}endEditing(){this.isEditable()&&(this._isEditing=!1)}updatePageTitle(t){t.preventDefault();const e=new FormData(t.target),i=Object.fromEntries(e).newPageTitle.toString();if(this.pageTitle===i)return void this.endEditing();this._isSubmitting=!0;let a,o={};a=this.localizedPageId>0?this.localizedPageId:this.pageId,o.data={pages:{[a]:{title:i}}},AjaxDataHandler.process(o).then((()=>{this.pageTitle=i,top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))})).finally((()=>{this.endEditing(),this._isSubmitting=!1}))}composeEditButton(){return html`<button type="button" class="btn btn-link" aria-label="${lll("editPageTitle")}" @click="${()=>{this.startEditing()}}">
      <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
    </button>`}composeEditForm(){return html`<form class="t3js-title-edit-form" @submit="${this.updatePageTitle}">
      <div class="form-group">
        <div class="input-group input-group-lg">
          <input class="form-control" name="newPageTitle" ?disabled="${this._isSubmitting}" value="${this.pageTitle}" @keydown="${t=>{"Escape"===t.key&&this.endEditing()}}">
          <button class="btn btn-default" type="submit" ?disabled="${this._isSubmitting}">
            <typo3-backend-icon identifier="actions-save" size="small"></typo3-backend-icon>
          </button>
          <button class="btn btn-default" type="button" ?disabled="${this._isSubmitting}" @click="${()=>{this.endEditing()}}">
            <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    </form>`}};__decorate([property({type:String})],EditablePageTitle.prototype,"pageTitle",void 0),__decorate([property({type:Number})],EditablePageTitle.prototype,"pageId",void 0),__decorate([property({type:Number})],EditablePageTitle.prototype,"localizedPageId",void 0),__decorate([property({type:Boolean})],EditablePageTitle.prototype,"editable",void 0),__decorate([state()],EditablePageTitle.prototype,"_isEditing",void 0),__decorate([state()],EditablePageTitle.prototype,"_isSubmitting",void 0),EditablePageTitle=__decorate([customElement("typo3-backend-editable-page-title")],EditablePageTitle);