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
var __decorate=function(e,t,i,o){var r,a=arguments.length,n=a<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,i,o);else for(var s=e.length-1;s>=0;s--)(r=e[s])&&(n=(a<3?r(n):a>3?r(t,i,n):r(t,i))||n);return a>3&&n&&Object.defineProperty(t,i,n),n};import{html,css,LitElement,nothing}from"lit";import{customElement,property,state}from"lit/decorators.js";import"@typo3/backend/element/icon-element.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";let EditableGroupName=class extends LitElement{constructor(){super(...arguments),this.groupName="",this.groupId=0,this.editable=!1,this._isEditing=!1,this._isSubmitting=!1,this.labels={input:TYPO3?.lang?.["editableGroupName.input.field.label"]||"Field",edit:TYPO3?.lang?.["editableGroupName.button.edit.label"]||"Edit",save:TYPO3?.lang?.["editableGroupName.button.save.label"]||"Save",cancel:TYPO3?.lang?.["editableGroupName.button.cancel.label"]||"Cancel"}}async startEditing(){this.isEditable()&&(this._isEditing=!0,await this.updateComplete,this.shadowRoot.querySelector("input")?.focus())}render(){if(""===this.groupName)return nothing;if(!this.isEditable())return html`
        <div class="wrapper"><div class="label">${this.groupName}</div></div>`;let e;return e=this._isEditing?this.composeEditForm():html`
        <div class="wrapper">
          <div class="label" @dblclick="${()=>{this.startEditing()}}">${this.groupName}</div>
          ${this.composeEditButton()}
        </div>`,e}isEditable(){return this.editable&&this.groupId>0}endEditing(){this.isEditable()&&(this._isEditing=!1)}updateGroupName(e){e.preventDefault();const t=new FormData(e.target),i=Object.fromEntries(t).newGroupName.toString();if(this.groupName===i)return void this.endEditing();this._isSubmitting=!0;const o="&data[tx_scheduler_task_group]["+this.groupId+"][groupName]="+encodeURIComponent(i)+"&redirect="+encodeURIComponent(document.location.href);new AjaxRequest(TYPO3.settings.ajaxUrls.record_process).post(o,{headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"}}).then((async e=>await e.resolve())).then((e=>(e.messages.forEach((t=>{Notification.info(t.title,t.message),window.location.href=e.redirect})),e))).then((()=>{this.groupName=i})).finally((()=>{this.endEditing(),this._isSubmitting=!1}))}composeEditButton(){return html`
      <button
        data-action="edit"
        type="button"
        title="${this.labels.edit}"
        @click="${()=>{this.startEditing()}}"
      >
        <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
        <span class="screen-reader">${this.labels.edit}</span>
      </button>`}composeEditForm(){return html`
      <form class="wrapper" @submit="${this.updateGroupName}">
        <label class="screen-reader" for="input">${this.labels.input}</label>
        <input
          autocomplete="off"
          id="input"
          name="newGroupName"
          required
          value="${this.groupName}"
          ?disabled="${this._isSubmitting}"
          @keydown="${e=>{"Escape"===e.key&&this.endEditing()}}"
        >
        <button
          data-action="save"
          type="submit"
          title="${this.labels.save}"
          ?disabled="${this._isSubmitting}"
        >
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
          <span class="screen-reader">${this.labels.save}</span>
        </button>
        <button
          data-action="close"
          type="button"
          title="${this.labels.cancel}"
          ?disabled="${this._isSubmitting}"
          @click="${()=>{this.endEditing()}}"
        >
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          <span class="screen-reader">${this.labels.cancel}</span>
        </button>
      </form>`}};EditableGroupName.styles=css`
    :host {
      display: block;
      --border-color: #bebebe;
      --hover-bg: #cacaca;
      --hover-border-color: #bebebe;
      --focus-bg: #cacaca;
      --focus-border-color: #bebebe;
    }

    .label {
      display: block;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      padding: calc(1px + .16rem)  0;
      margin: 0;
    }

    input {
      outline: none;
      background: transparent;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      padding: .16rem 0;
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
      padding-inline-end: 2.5em;
    }

    form.wrapper {
      padding-inline-end: 5em;
    }

    button {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: inherit;
      line-height: inherit;
      border: 0;
      padding: 0;
      height: 100%;
      width: 2em;
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
      inset-inline-end: 0;
    }

    button[data-action="save"] {
      inset-inline-end: calc(2em + 2px);
    }

    button[data-action="close"] {
      inset-inline-end: 0;
    }

    .screen-reader {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0,0,0,0);
      white-space: nowrap;
      border: 0
    }
    `,__decorate([property({type:String})],EditableGroupName.prototype,"groupName",void 0),__decorate([property({type:Number})],EditableGroupName.prototype,"groupId",void 0),__decorate([property({type:Boolean})],EditableGroupName.prototype,"editable",void 0),__decorate([state()],EditableGroupName.prototype,"_isEditing",void 0),__decorate([state()],EditableGroupName.prototype,"_isSubmitting",void 0),EditableGroupName=__decorate([customElement("typo3-scheduler-editable-group-name")],EditableGroupName);export{EditableGroupName};