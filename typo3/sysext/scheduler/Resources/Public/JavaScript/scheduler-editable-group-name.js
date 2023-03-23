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
var EditableGroupName_1,__decorate=function(t,e,o,i){var r,a=arguments.length,n=a<3?e:null===i?i=Object.getOwnPropertyDescriptor(e,o):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,o,i);else for(var s=t.length-1;s>=0;s--)(r=t[s])&&(n=(a<3?r(n):a>3?r(e,o,n):r(e,o))||n);return a>3&&n&&Object.defineProperty(e,o,n),n};import{lll}from"@typo3/core/lit-helper.js";import{html,css,LitElement,nothing}from"lit";import{customElement,property,state}from"lit/decorators.js";import"@typo3/backend/element/icon-element.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";let EditableGroupName=EditableGroupName_1=class extends LitElement{constructor(){super(...arguments),this.groupName="",this.groupId=0,this.editable=!1,this._isEditing=!1,this._isSubmitting=!1}static updateInputSize(t){const e=t;e.value.length<10?e.size=10:e.size=e.value.length+2}async startEditing(){this.isEditable()&&(this._isEditing=!0,await this.updateComplete,this.shadowRoot.querySelector("input")?.focus())}render(){if(""===this.groupName)return nothing;if(!this.isEditable())return html`
        <div class="wrapper">${this.groupName}</div>`;let t;return t=this._isEditing?this.composeEditForm():html`
        <div class="wrapper">
          <span @dblclick="${()=>{this.startEditing()}}">${this.groupName}</span>
          ${this.composeEditButton()}
        </div>`,t}isEditable(){return this.editable&&this.groupId>0}endEditing(){this.isEditable()&&(this._isEditing=!1)}updateGroupName(t){t.preventDefault();const e=new FormData(t.target),o=Object.fromEntries(e).newGroupName.toString();if(this.groupName===o)return void this.endEditing();this._isSubmitting=!0;const i="&data[tx_scheduler_task_group]["+this.groupId+"][groupName]="+encodeURIComponent(o)+"&redirect="+encodeURIComponent(document.location.href);new AjaxRequest(TYPO3.settings.ajaxUrls.record_process).post(i,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then((async t=>await t.resolve())).then((t=>(t.messages.forEach((e=>{Notification.info(e.title,e.message),window.location.href=t.redirect})),t))).then((()=>{this.groupName=o})).finally((()=>{this.endEditing(),this._isSubmitting=!1}))}composeEditButton(){return html`
      <button data-action="edit" type="button" aria-label="${lll("editGroupName")}" @click="${()=>{this.startEditing()}}">
        <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
      </button>`}composeEditForm(){return html`
      <form class="wrapper" @submit="${this.updateGroupName}">
        <input autocomplete="off" name="newGroupName" required size="${this.groupName.length+2}" ?disabled="${this._isSubmitting}" value="${this.groupName}" @keydown="${t=>{EditableGroupName_1.updateInputSize(t.target),"Escape"===t.key&&this.endEditing()}}">
        <button data-action="save" type="submit" ?disabled="${this._isSubmitting}">
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
        </button>
        <button data-action="close" type="button" ?disabled="${this._isSubmitting}" @click="${()=>{this.endEditing()}}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </button>
      </form>`}};EditableGroupName.styles=css`
    :host {
      display: inline-block;
      --border-color: #bebebe;
      --hover-bg: #cacaca;
      --hover-border-color: #bebebe;
      --focus-bg: #cacaca;
      --focus-border-color: #bebebe;
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

    button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: inherit;
      line-height: inherit;
      border: 0;
      padding: 10px;
      height: 1em;
      width: 1em;
      top: 0;
      border-radius: 2px;
      overflow: hidden;
      outline: none;
      border: 1px solid transparent;
      background: transparent;
      opacity: 1;
      transition: all .2s ease-in-out;
    }

    button:hover {
      background: var(--hover-bg);
      border-color: var(--hover-border-color);
      cursor: pointer;
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
      right: calc(1em + 10px);
    }

    button[data-action="close"] {
      right: 0;
    }
    `,__decorate([property({type:String})],EditableGroupName.prototype,"groupName",void 0),__decorate([property({type:Number})],EditableGroupName.prototype,"groupId",void 0),__decorate([property({type:Boolean})],EditableGroupName.prototype,"editable",void 0),__decorate([state()],EditableGroupName.prototype,"_isEditing",void 0),__decorate([state()],EditableGroupName.prototype,"_isSubmitting",void 0),EditableGroupName=EditableGroupName_1=__decorate([customElement("typo3-scheduler-editable-group-name")],EditableGroupName);export{EditableGroupName};