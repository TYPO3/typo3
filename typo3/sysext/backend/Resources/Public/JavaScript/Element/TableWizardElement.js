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
var __decorate=this&&this.__decorate||function(t,e,l,a){var s,n=arguments.length,o=n<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,l):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(t,e,l,a);else for(var i=t.length-1;i>=0;i--)(s=t[i])&&(o=(n<3?s(o):n>3?s(e,l,o):s(e,l))||o);return n>3&&o&&Object.defineProperty(e,l,o),o};define(["require","exports","lit","lit/decorators","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/Severity","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Backend/Element/IconElement"],(function(t,e,l,a,s,n,o,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.TableWizardElement=void 0;let r=class extends l.LitElement{constructor(){super(...arguments),this.type="textarea",this.table=[],this.appendRows=1,this.l10n={}}get firstRow(){return this.table[0]||[]}createRenderRoot(){return this}render(){return this.renderTemplate()}provideMinimalTable(){0!==this.table.length&&0!==this.firstRow.length||(this.table=[[""]])}modifyTable(t,e,l){const a=t.target;this.table[e][l]=a.value,this.requestUpdate()}toggleType(t){this.type="input"===this.type?"textarea":"input"}moveColumn(t,e,l){this.table=this.table.map(t=>{const a=t.splice(e,1);return t.splice(l,0,...a),t}),this.requestUpdate()}appendColumn(t,e){this.table=this.table.map(t=>(t.splice(e+1,0,""),t)),this.requestUpdate()}removeColumn(t,e){this.table=this.table.map(t=>(t.splice(e,1),t)),this.requestUpdate()}moveRow(t,e,l){const a=this.table.splice(e,1);this.table.splice(l,0,...a),this.requestUpdate()}appendRow(t,e){let l=this.firstRow.concat().fill(""),a=new Array(this.appendRows).fill(l);this.table.splice(e+1,0,...a),this.requestUpdate()}removeRow(t,e){this.table.splice(e,1),this.requestUpdate()}renderTemplate(){const t=Object.keys(this.firstRow).map(t=>parseInt(t,10)),e=t[t.length-1],a=this.table.length-1;return l.html`
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
    `}renderDataElement(t,e,a){const s=t=>this.modifyTable(t,e,a);switch(this.type){case"input":return l.html`
          <input class="form-control" type="text" name="TABLE[c][${e}][${a}]"
            @change="${s}" .value="${t.replace(/\n/g,"<br>")}">
        `;case"textarea":default:return l.html`
          <textarea class="form-control" rows="6" name="TABLE[c][${e}][${a}]"
            @change="${s}" .value="${t.replace(/<br[ ]*\/?>/g,"\n")}"></textarea>
        `}}renderTypeButton(){return l.html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${s.lll("table_smallFields")}"
          @click="${t=>this.toggleType(t)}">
          <typo3-backend-icon identifier="${"input"===this.type?"actions-chevron-expand":"actions-chevron-contract"}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${s.lll("table_setCount")}"
            @click="${t=>this.showTableConfigurationModal(t)}">
          <span class="t3-icon fa fa-fw fa-plus"></span>
        </button>
      </span>
    `}renderColButtons(t,e){const a={title:0===t?s.lll("table_end"):s.lll("table_left"),class:0===t?"double-right":"left",target:0===t?e:t-1},n={title:t===e?s.lll("table_start"):s.lll("table_right"),class:t===e?"double-left":"right",target:t===e?0:t+1};return l.html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${a.title}"
                @click="${e=>this.moveColumn(e,t,a.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${a.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${n.title}"
                @click="${e=>this.moveColumn(e,t,n.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${n.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${s.lll("table_removeColumn")}"
                @click="${e=>this.removeColumn(e,t)}">
          <span class="t3-icon fa fa-fw fa-trash"></span>
        </button>
        <button class="btn btn-default" type="button" title="${s.lll("table_addColumn")}"
                @click="${e=>this.appendColumn(e,t)}">
          <span class="t3-icon fa fa-fw fa-plus"></span>
        </button>
      </span>
    `}renderRowButtons(t,e){const a={title:0===t?s.lll("table_bottom"):s.lll("table_up"),class:0===t?"double-down":"up",target:0===t?e:t-1},n={title:t===e?s.lll("table_top"):s.lll("table_down"),class:t===e?"double-up":"down",target:t===e?0:t+1};return l.html`
      <span class="btn-group${"input"===this.type?"":"-vertical"}">
        <button class="btn btn-default" type="button" title="${a.title}"
                @click="${e=>this.moveRow(e,t,a.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${a.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${n.title}"
                @click="${e=>this.moveRow(e,t,n.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${n.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${s.lll("table_removeRow")}"
                @click="${e=>this.removeRow(e,t)}">
          <span class="t3-icon fa fa-fw fa-trash"></span>
        </button>
        <button class="btn btn-default" type="button" title="${s.lll("table_addRow")}"
                @click="${e=>this.appendRow(e,t)}">
          <span class="t3-icon fa fa-fw fa-plus"></span>
        </button>
      </span>
    `}showTableConfigurationModal(t){const e=this.firstRow.length,a=this.table.length,r=a||1,c=e||1;o.advanced({content:"",title:s.lll("table_setCountHeadline"),severity:i.SeverityEnum.notice,size:o.sizes.small,buttons:[{text:s.lll("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>o.dismiss()},{text:s.lll("table_buttonApply")||"Apply",btnClass:"btn-"+n.getCssClass(i.SeverityEnum.info),name:"apply",trigger:()=>{const l=o.currentModal[0].querySelector("#t3js-expand-rows"),s=o.currentModal[0].querySelector("#t3js-expand-cols");if(null!==l&&null!==s)if(l.checkValidity()&&s.checkValidity()){const n=Number(l.value)-a,i=Number(s.value)-e;this.setColAndRowCount(t,i,n),o.dismiss()}else l.reportValidity(),s.reportValidity()}}],callback:t=>{l.render(l.html`
            <div class="form-group ">
              <label>${s.lll("table_rowCount")}</label>
              <input id="t3js-expand-rows" class="form-control" type="number" min="1" required value="${r}">
            </div>
            <div class="form-group ">
              <label>${s.lll("table_colCount")}</label>
              <input id="t3js-expand-cols" class="form-control" type="number" min="1" required value="${c}">
            </div>
          `,t[0].querySelector(".t3js-modal-body"))}})}setColAndRowCount(t,e,l){const a=this.table.length;if(l>0)for(let e=0;e<l;e++)this.appendRow(t,a);else for(let e=0;e<Math.abs(l);e++)this.removeRow(t,this.table.length-1);if(e>0)for(let l=0;l<e;l++)this.appendColumn(t,e);else for(let l=0;l<Math.abs(e);l++)this.removeColumn(t,this.firstRow.length-1)}};__decorate([a.property({type:String})],r.prototype,"type",void 0),__decorate([a.property({type:Array})],r.prototype,"table",void 0),__decorate([a.property({type:Number,attribute:"append-rows"})],r.prototype,"appendRows",void 0),__decorate([a.property({type:Object})],r.prototype,"l10n",void 0),r=__decorate([a.customElement("typo3-backend-table-wizard")],r),e.TableWizardElement=r}));