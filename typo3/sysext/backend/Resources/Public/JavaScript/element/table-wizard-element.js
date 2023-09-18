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
var __decorate=function(t,e,l,a){var o,n=arguments.length,i=n<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,l):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(t,e,l,a);else for(var s=t.length-1;s>=0;s--)(o=t[s])&&(i=(n<3?o(i):n>3?o(e,l,i):o(e,l))||i);return n>3&&i&&Object.defineProperty(e,l,i),i};import{html,LitElement,render}from"lit";import{customElement,property}from"lit/decorators.js";import{lll}from"@typo3/core/lit-helper.js";import"@typo3/backend/element/icon-element.js";import Severity from"@typo3/backend/severity.js";import Modal from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";let TableWizardElement=class extends LitElement{constructor(){super(...arguments),this.type="textarea",this.selectorData="",this.delimiter="|",this.enclosure="",this.appendRows=1,this.table=[]}get firstRow(){return this.table[0]||[]}connectedCallback(){super.connectedCallback(),this.selectorData=this.getAttribute("selector"),this.delimiter=this.getAttribute("delimiter"),this.enclosure=this.getAttribute("enclosure")||"",this.readTableFromTextarea()}createRenderRoot(){return this}render(){return this.renderTemplate()}provideMinimalTable(){0!==this.table.length&&0!==this.firstRow.length||(this.table=[[""]])}readTableFromTextarea(){const t=document.querySelector(this.selectorData),e=[];t.value.split("\n").forEach((t=>{if(""!==t){this.enclosure&&(t=t.replace(new RegExp(this.enclosure,"g"),""));const l=t.split(this.delimiter);e.push(l)}})),this.table=e}writeTableSyntaxToTextarea(){const t=document.querySelector(this.selectorData);let e="";this.table.forEach((t=>{const l=t.length;e+=t.reduce(((t,e,a)=>{const o=l-1===a?"":this.delimiter;return t+this.enclosure+e+this.enclosure+o}),"")+"\n"})),t.value=e,t.dispatchEvent(new CustomEvent("change",{bubbles:!0}))}modifyTable(t,e,l){const a=t.target;this.table[e][l]=a.value,this.writeTableSyntaxToTextarea(),this.requestUpdate()}toggleType(){this.type="input"===this.type?"textarea":"input"}moveColumn(t,e){this.table=this.table.map((l=>{const a=l.splice(t,1);return l.splice(e,0,...a),l})),this.writeTableSyntaxToTextarea(),this.requestUpdate()}appendColumn(t,e){this.table=this.table.map((t=>(t.splice(e+1,0,""),t))),this.writeTableSyntaxToTextarea(),this.requestUpdate()}removeColumn(t,e){this.table=this.table.map((t=>(t.splice(e,1),t))),this.writeTableSyntaxToTextarea(),this.requestUpdate()}moveRow(t,e,l){const a=this.table.splice(e,1);this.table.splice(l,0,...a),this.writeTableSyntaxToTextarea(),this.requestUpdate()}appendRow(t,e){const l=this.firstRow.concat().fill(""),a=new Array(this.appendRows).fill(l);this.table.splice(e+1,0,...a),this.writeTableSyntaxToTextarea(),this.requestUpdate()}removeRow(t,e){this.table.splice(e,1),this.writeTableSyntaxToTextarea(),this.requestUpdate()}renderTemplate(){this.provideMinimalTable();const t=Object.keys(this.firstRow).map((t=>parseInt(t,10))),e=t[t.length-1],l=this.table.length-1;return html`
      <div class="table-fit table-fit-inline-block">
        <table class="table table-center">
          <thead>
            <th>${this.renderTypeButton()}</th>
            ${t.map((t=>html`
            <th>${this.renderColButtons(t,e)}</th>
            `))}
          </thead>
          <tbody>
            ${this.table.map(((t,e)=>html`
            <tr>
              <td>${this.renderRowButtons(e,l)}</td>
              ${t.map(((t,l)=>html`
              <td>${this.renderDataElement(t,e,l)}</td>
              `))}
            </tr>
            `))}
          </tbody>
        </table>
      </div>
    `}renderDataElement(t,e,l){const a=t=>this.modifyTable(t,e,l);return"input"===this.type?html`
          <input class="form-control" type="text" data-row="${e}" data-col="${l}"
            @change="${a}" .value="${t.replace(/\n/g,"<br>")}">
        `:html`
          <textarea class="form-control" rows="6" data-row="${e}" data-col="${l}"
            @change="${a}" .value="${t.replace(/<br[ ]*\/?>/g,"\n")}"></textarea>
        `}renderTypeButton(){return html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${lll("table_smallFields")}"
                @click="${()=>this.toggleType()}">
          <typo3-backend-icon identifier="${"input"===this.type?"actions-chevron-expand":"actions-chevron-contract"}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll("table_setCount")}"
                @click="${t=>this.showTableConfigurationModal(t)}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll("table_showCode")}"
                @click="${()=>this.showTableSyntax()}">
          <typo3-backend-icon identifier="actions-code" size="small"></typo3-backend-icon>
        </button>
      </span>
    `}renderColButtons(t,e){const l={title:lll(0===t?"table_end":"table_left"),class:0===t?"bar-right":"left",target:0===t?e:t-1},a={title:lll(t===e?"table_start":"table_right"),class:t===e?"bar-left":"right",target:t===e?0:t+1};return html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${l.title}"
                @click="${()=>this.moveColumn(t,l.target)}">
          <typo3-backend-icon identifier="actions-chevron-${l.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${a.title}"
                @click="${()=>this.moveColumn(t,a.target)}">
          <typo3-backend-icon identifier="actions-chevron-${a.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll("table_removeColumn")}"
                @click="${e=>this.removeColumn(e,t)}">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll("table_addColumn")}"
                @click="${e=>this.appendColumn(e,t)}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        </button>
      </span>
    `}renderRowButtons(t,e){const l={title:lll(0===t?"table_bottom":"table_up"),class:0===t?"bar-down":"up",target:0===t?e:t-1},a={title:lll(t===e?"table_top":"table_down"),class:t===e?"bar-up":"down",target:t===e?0:t+1};return html`
      <span class="btn-group${"input"===this.type?"":"-vertical"}">
        <button class="btn btn-default" type="button" title="${l.title}"
                @click="${e=>this.moveRow(e,t,l.target)}">
          <typo3-backend-icon identifier="actions-chevron-${l.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${a.title}"
                @click="${e=>this.moveRow(e,t,a.target)}">
          <typo3-backend-icon identifier="actions-chevron-${a.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll("table_removeRow")}"
                @click="${e=>this.removeRow(e,t)}">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll("table_addRow")}"
                @click="${e=>this.appendRow(e,t)}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        </button>
      </span>
    `}showTableConfigurationModal(t){const e=this.firstRow.length,l=this.table.length,a=l||1,o=e||1,n=Modal.advanced({content:"",title:lll("table_setCountHeadline"),severity:SeverityEnum.notice,size:Modal.sizes.small,buttons:[{text:lll("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>Modal.dismiss()},{text:lll("table_buttonApply")||"Apply",btnClass:"btn-"+Severity.getCssClass(SeverityEnum.info),name:"apply",trigger:()=>{const a=n.querySelector("#t3js-expand-rows"),o=n.querySelector("#t3js-expand-cols");if(null!==a&&null!==o)if(a.checkValidity()&&o.checkValidity()){const n=Number(a.value)-l,i=Number(o.value)-e;this.setColAndRowCount(t,i,n),Modal.dismiss()}else a.reportValidity(),o.reportValidity()}}],callback:t=>{render(html`
            <div class="form-group ">
              <label>${lll("table_rowCount")}</label>
              <input id="t3js-expand-rows" class="form-control" type="number" min="1" required value="${a}">
            </div>
            <div class="form-group ">
              <label>${lll("table_colCount")}</label>
              <input id="t3js-expand-cols" class="form-control" type="number" min="1" required value="${o}">
            </div>
          `,t.querySelector(".t3js-modal-body"))}})}showTableSyntax(){const t=Modal.advanced({content:"",title:lll("table_showCode"),severity:SeverityEnum.notice,size:Modal.sizes.small,buttons:[{text:lll("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>Modal.dismiss()},{text:lll("table_buttonApply")||"Apply",btnClass:"btn-"+Severity.getCssClass(SeverityEnum.info),name:"apply",trigger:()=>{document.querySelector(this.selectorData).value=t.querySelector("textarea").value,this.readTableFromTextarea(),this.requestUpdate(),Modal.dismiss()}}],callback:t=>{const e=document.querySelector(this.selectorData);render(html`<textarea style="width: 100%;">${e.value}</textarea>`,t.querySelector(".t3js-modal-body"))}})}setColAndRowCount(t,e,l){const a=this.table.length;if(l>0)for(let e=0;e<l;e++)this.appendRow(t,a);else for(let e=0;e<Math.abs(l);e++)this.removeRow(t,this.table.length-1);if(e>0)for(let l=0;l<e;l++)this.appendColumn(t,e);else for(let l=0;l<Math.abs(e);l++)this.removeColumn(t,this.firstRow.length-1)}};__decorate([property({type:String})],TableWizardElement.prototype,"type",void 0),__decorate([property({type:String})],TableWizardElement.prototype,"selectorData",void 0),__decorate([property({type:String})],TableWizardElement.prototype,"delimiter",void 0),__decorate([property({type:String})],TableWizardElement.prototype,"enclosure",void 0),__decorate([property({type:Number,attribute:"append-rows"})],TableWizardElement.prototype,"appendRows",void 0),TableWizardElement=__decorate([customElement("typo3-backend-table-wizard")],TableWizardElement);export{TableWizardElement};