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
var __decorate=this&&this.__decorate||function(t,e,l,a){var s,n=arguments.length,o=n<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,l):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(t,e,l,a);else for(var r=t.length-1;r>=0;r--)(s=t[r])&&(o=(n<3?s(o):n>3?s(e,l,o):s(e,l))||o);return n>3&&o&&Object.defineProperty(e,l,o),o};define(["require","exports","lit-element","TYPO3/CMS/Core/lit-helper"],(function(t,e,l,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.TableWizardElement=void 0;let s=class extends l.LitElement{constructor(){super(...arguments),this.type="textarea",this.table=[],this.appendRows=1,this.l10n={}}get firstRow(){return this.table[0]||[]}createRenderRoot(){return this}render(){return this.renderTemplate()}provideMinimalTable(){0!==this.table.length&&0!==this.firstRow.length||(this.table=[[""]])}modifyTable(t,e,l){const a=t.target;this.table[e][l]=a.value,this.requestUpdate()}toggleType(t){this.type="input"===this.type?"textarea":"input"}moveColumn(t,e,l){this.table=this.table.map(t=>{const a=t.splice(e,1);return t.splice(l,0,...a),t}),this.requestUpdate()}appendColumn(t,e){this.table=this.table.map(t=>(t.splice(e+1,0,""),t)),this.requestUpdate()}removeColumn(t,e){this.table=this.table.map(t=>(t.splice(e,1),t)),this.requestUpdate()}moveRow(t,e,l){const a=this.table.splice(e,1);this.table.splice(l,0,...a),this.requestUpdate()}appendRow(t,e){let l=this.firstRow.concat().fill(""),a=new Array(this.appendRows).fill(l);this.table.splice(e+1,0,...a),this.requestUpdate()}removeRow(t,e){this.table.splice(e,1),this.requestUpdate()}renderTemplate(){const t=Object.keys(this.firstRow).map(t=>parseInt(t,10)),e=t[t.length-1],a=this.table.length-1;return l.html`
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
        <button class="btn btn-default" type="button" title="${a.lll("table_smallFields")}"
          @click="${t=>this.toggleType(t)}">
          ${a.icon("input"===this.type?"actions-chevron-expand":"actions-chevron-contract")}
        </button>
      </span>
    `}renderColButtons(t,e){const s={title:0===t?a.lll("table_end"):a.lll("table_left"),class:0===t?"double-right":"left",target:0===t?e:t-1},n={title:t===e?a.lll("table_start"):a.lll("table_right"),class:t===e?"double-left":"right",target:t===e?0:t+1};return l.html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${s.title}"
                @click="${e=>this.moveColumn(e,t,s.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${s.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${n.title}"
                @click="${e=>this.moveColumn(e,t,n.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${n.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${a.lll("table_removeColumn")}"
                @click="${e=>this.removeColumn(e,t)}">
          <span class="t3-icon fa fa-fw fa-trash"></span>
        </button>
        <button class="btn btn-default" type="button" title="${a.lll("table_addColumn")}"
                @click="${e=>this.appendColumn(e,t)}">
          <span class="t3-icon fa fa-fw fa-plus"></span>
        </button>
      </span>
    `}renderRowButtons(t,e){const s={title:0===t?a.lll("table_bottom"):a.lll("table_up"),class:0===t?"double-down":"up",target:0===t?e:t-1},n={title:t===e?a.lll("table_top"):a.lll("table_down"),class:t===e?"double-up":"down",target:t===e?0:t+1};return l.html`
      <span class="btn-group${"input"===this.type?"":"-vertical"}">
        <button class="btn btn-default" type="button" title="${s.title}"
                @click="${e=>this.moveRow(e,t,s.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${s.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${n.title}"
                @click="${e=>this.moveRow(e,t,n.target)}">
          <span class="t3-icon fa fa-fw fa-angle-${n.class}"></span>
        </button>
        <button class="btn btn-default" type="button" title="${a.lll("table_removeRow")}"
                @click="${e=>this.removeRow(e,t)}">
          <span class="t3-icon fa fa-fw fa-trash"></span>
        </button>
        <button class="btn btn-default" type="button" title="${a.lll("table_addRow")}"
                @click="${e=>this.appendRow(e,t)}">
          <span class="t3-icon fa fa-fw fa-plus"></span>
        </button>
      </span>
    `}};__decorate([l.property({type:String})],s.prototype,"type",void 0),__decorate([l.property({type:Array})],s.prototype,"table",void 0),__decorate([l.property({type:Number,attribute:"append-rows"})],s.prototype,"appendRows",void 0),__decorate([l.property({type:Object})],s.prototype,"l10n",void 0),s=__decorate([l.customElement("typo3-backend-table-wizard")],s),e.TableWizardElement=s}));