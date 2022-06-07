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
var __decorate=this&&this.__decorate||function(t,e,l,a){var n,i=arguments.length,o=i<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,l):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(t,e,l,a);else for(var s=t.length-1;s>=0;s--)(n=t[s])&&(o=(i<3?n(o):i>3?n(e,l,o):n(e,l))||o);return i>3&&o&&Object.defineProperty(e,l,o),o};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/Severity","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Backend/Element/IconElement"],(function(t,e,l,a,n,i,o,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.TableWizardElement=void 0;let r=class extends l.LitElement{constructor(){super(),this.type="textarea",this.selectorData="",this.delimiter="|",this.enclosure="",this.appendRows=1,this.l10n={},this.table=[],this.selectorData=this.getAttribute("selector"),this.delimiter=this.getAttribute("delimiter"),this.enclosure=this.getAttribute("enclosure")||"",this.readTableFromTextarea()}get firstRow(){return this.table[0]||[]}createRenderRoot(){return this}render(){return this.renderTemplate()}provideMinimalTable(){0!==this.table.length&&0!==this.firstRow.length||(this.table=[[""]])}readTableFromTextarea(){let t=document.querySelector(this.selectorData),e=[];t.value.split("\n").forEach(t=>{if(""!==t){this.enclosure&&(t=t.replace(new RegExp(this.enclosure,"g"),""));let l=t.split(this.delimiter);e.push(l)}}),this.table=e}writeTableSyntaxToTextarea(){let t=document.querySelector(this.selectorData),e="";this.table.forEach(t=>{let l=t.length;e+=t.reduce((t,e,a)=>{let n=l-1===a?"":this.delimiter;return t+this.enclosure+e+this.enclosure+n},"")+"\n"}),t.value=e,t.dispatchEvent(new CustomEvent("change",{bubbles:!0}))}modifyTable(t,e,l){const a=t.target;this.table[e][l]=a.value,this.writeTableSyntaxToTextarea(),this.requestUpdate()}toggleType(t){this.type="input"===this.type?"textarea":"input"}moveColumn(t,e,l){this.table=this.table.map(t=>{const a=t.splice(e,1);return t.splice(l,0,...a),t}),this.writeTableSyntaxToTextarea(),this.requestUpdate()}appendColumn(t,e){this.table=this.table.map(t=>(t.splice(e+1,0,""),t)),this.writeTableSyntaxToTextarea(),this.requestUpdate()}removeColumn(t,e){this.table=this.table.map(t=>(t.splice(e,1),t)),this.writeTableSyntaxToTextarea(),this.requestUpdate()}moveRow(t,e,l){const a=this.table.splice(e,1);this.table.splice(l,0,...a),this.writeTableSyntaxToTextarea(),this.requestUpdate()}appendRow(t,e){let l=this.firstRow.concat().fill(""),a=new Array(this.appendRows).fill(l);this.table.splice(e+1,0,...a),this.writeTableSyntaxToTextarea(),this.requestUpdate()}removeRow(t,e){this.table.splice(e,1),this.writeTableSyntaxToTextarea(),this.requestUpdate()}renderTemplate(){this.provideMinimalTable();const t=Object.keys(this.firstRow).map(t=>parseInt(t,10)),e=t[t.length-1],a=this.table.length-1;return l.html`
      <style>
        :host, typo3-backend-table-wizard { display: inline-block; }
      </style>
      <div class="table-fit table-fit-inline-block">
        <table class="table table-center">
          <thead>
            <th>${this.renderTypeButton()}</th>
            ${t.map(t=>l.html`
            <th>${this.renderColButtons(t,e)}</th>
            `)}
          </thead>
          <tbody>
            ${this.table.map((t,e)=>l.html`
            <tr>
              <th>${this.renderRowButtons(e,a)}</th>
              ${t.map((t,a)=>l.html`
              <td>${this.renderDataElement(t,e,a)}</td>
              `)}
            </tr>
            `)}
          </tbody>
        </table>
      </div>
    `}renderDataElement(t,e,a){const n=t=>this.modifyTable(t,e,a);switch(this.type){case"input":return l.html`
          <input class="form-control" type="text" name="TABLE[c][${e}][${a}]"
            @change="${n}" .value="${t.replace(/\n/g,"<br>")}">
        `;case"textarea":default:return l.html`
          <textarea class="form-control" rows="6" name="TABLE[c][${e}][${a}]"
            @change="${n}" .value="${t.replace(/<br[ ]*\/?>/g,"\n")}"></textarea>
        `}}renderTypeButton(){return l.html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_smallFields")}"
                @click="${t=>this.toggleType(t)}">
          <typo3-backend-icon identifier="${"input"===this.type?"actions-chevron-expand":"actions-chevron-contract"}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_setCount")}"
                @click="${t=>this.showTableConfigurationModal(t)}">
          <typo3-backend-icon identifier="actions-add" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_showCode")}"
                @click="${t=>this.showTableSyntax(t)}">
          <typo3-backend-icon identifier="actions-code" size="small"></typo3-backend-icon>
        </button>
      </span>
    `}renderColButtons(t,e){const a={title:0===t?(0,n.lll)("table_end"):(0,n.lll)("table_left"),class:0===t?"bar-right":"left",target:0===t?e:t-1},i={title:t===e?(0,n.lll)("table_start"):(0,n.lll)("table_right"),class:t===e?"bar-left":"right",target:t===e?0:t+1};return l.html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${a.title}"
                @click="${e=>this.moveColumn(e,t,a.target)}">
          <typo3-backend-icon identifier="actions-chevron-${a.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${i.title}"
                @click="${e=>this.moveColumn(e,t,i.target)}">
          <typo3-backend-icon identifier="actions-chevron-${i.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_removeColumn")}"
                @click="${e=>this.removeColumn(e,t)}">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_addColumn")}"
                @click="${e=>this.appendColumn(e,t)}">
          <typo3-backend-icon identifier="actions-add" size="small"></typo3-backend-icon>
        </button>
      </span>
    `}renderRowButtons(t,e){const a={title:0===t?(0,n.lll)("table_bottom"):(0,n.lll)("table_up"),class:0===t?"bar-down":"up",target:0===t?e:t-1},i={title:t===e?(0,n.lll)("table_top"):(0,n.lll)("table_down"),class:t===e?"bar-up":"down",target:t===e?0:t+1};return l.html`
      <span class="btn-group${"input"===this.type?"":"-vertical"}">
        <button class="btn btn-default" type="button" title="${a.title}"
                @click="${e=>this.moveRow(e,t,a.target)}">
          <typo3-backend-icon identifier="actions-chevron-${a.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${i.title}"
                @click="${e=>this.moveRow(e,t,i.target)}">
          <typo3-backend-icon identifier="actions-chevron-${i.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_removeRow")}"
                @click="${e=>this.removeRow(e,t)}">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${(0,n.lll)("table_addRow")}"
                @click="${e=>this.appendRow(e,t)}">
          <typo3-backend-icon identifier="actions-add" size="small"></typo3-backend-icon>
        </button>
      </span>
    `}showTableConfigurationModal(t){const e=this.firstRow.length,a=this.table.length,r=a||1,c=e||1;o.advanced({content:"",title:(0,n.lll)("table_setCountHeadline"),severity:s.SeverityEnum.notice,size:o.sizes.small,buttons:[{text:(0,n.lll)("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>o.dismiss()},{text:(0,n.lll)("table_buttonApply")||"Apply",btnClass:"btn-"+i.getCssClass(s.SeverityEnum.info),name:"apply",trigger:()=>{const l=o.currentModal[0].querySelector("#t3js-expand-rows"),n=o.currentModal[0].querySelector("#t3js-expand-cols");if(null!==l&&null!==n)if(l.checkValidity()&&n.checkValidity()){const i=Number(l.value)-a,s=Number(n.value)-e;this.setColAndRowCount(t,s,i),o.dismiss()}else l.reportValidity(),n.reportValidity()}}],callback:t=>{(0,l.render)(l.html`
            <div class="form-group ">
              <label>${(0,n.lll)("table_rowCount")}</label>
              <input id="t3js-expand-rows" class="form-control" type="number" min="1" required value="${r}">
            </div>
            <div class="form-group ">
              <label>${(0,n.lll)("table_colCount")}</label>
              <input id="t3js-expand-cols" class="form-control" type="number" min="1" required value="${c}">
            </div>
          `,t[0].querySelector(".t3js-modal-body"))}})}showTableSyntax(t){o.advanced({content:"",title:(0,n.lll)("table_showCode"),severity:s.SeverityEnum.notice,size:o.sizes.small,buttons:[{text:(0,n.lll)("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>o.dismiss()},{text:(0,n.lll)("table_buttonApply")||"Apply",btnClass:"btn-"+i.getCssClass(s.SeverityEnum.info),name:"apply",trigger:()=>{document.querySelector(this.selectorData).value=o.currentModal[0].querySelector("textarea").value,this.readTableFromTextarea(),this.requestUpdate(),o.dismiss()}}],callback:t=>{let e=document.querySelector(this.selectorData);(0,l.render)(l.html`<textarea style="width: 100%;">${e.value}</textarea>`,t[0].querySelector(".t3js-modal-body"))}})}setColAndRowCount(t,e,l){const a=this.table.length;if(l>0)for(let e=0;e<l;e++)this.appendRow(t,a);else for(let e=0;e<Math.abs(l);e++)this.removeRow(t,this.table.length-1);if(e>0)for(let l=0;l<e;l++)this.appendColumn(t,e);else for(let l=0;l<Math.abs(e);l++)this.removeColumn(t,this.firstRow.length-1)}};__decorate([(0,a.property)({type:String})],r.prototype,"type",void 0),__decorate([(0,a.property)({type:String})],r.prototype,"selectorData",void 0),__decorate([(0,a.property)({type:String})],r.prototype,"delimiter",void 0),__decorate([(0,a.property)({type:String})],r.prototype,"enclosure",void 0),__decorate([(0,a.property)({type:Number,attribute:"append-rows"})],r.prototype,"appendRows",void 0),__decorate([(0,a.property)({type:Object})],r.prototype,"l10n",void 0),r=__decorate([(0,a.customElement)("typo3-backend-table-wizard")],r),e.TableWizardElement=r}));