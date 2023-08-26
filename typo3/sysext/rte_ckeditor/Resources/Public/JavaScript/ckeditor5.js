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
var __decorate=function(t,e,o,i){var n,r=arguments.length,s=r<3?e:null===i?i=Object.getOwnPropertyDescriptor(e,o):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,o,i);else for(var l=t.length-1;l>=0;l--)(n=t[l])&&(s=(r<3?n(s):r>3?n(e,o,s):n(e,o))||s);return r>3&&s&&Object.defineProperty(e,o,s),s};import{html,LitElement}from"lit";import{customElement,property,query}from"lit/decorators.js";import{CKEditor5}from"@typo3/ckeditor5-bundle.js";let CKEditor5Element=class extends LitElement{constructor(){super(),this.options={},this.formEngine={}}firstUpdated(){if(!(this.target instanceof HTMLElement))throw new Error("No rich-text content target found.");const t=(this.options.importModules||[]).map((t=>import(t)));Promise.all(t).then((t=>{const e=t.filter((t=>t.default)).map((t=>t.default)),o=CKEditor5.builtinPlugins.concat(e),i=[].concat(...o.filter((t=>t.overrides?.length>0)).map((t=>t.overrides))),n=o.filter((t=>!i.includes(t))),r={toolbar:this.options.toolbar,plugins:n,typo3link:this.options.typo3link||null,removePlugins:this.options.removePlugins||[]};this.options.language&&(r.language=this.options.language),this.options.style&&(r.style=this.options.style),this.options.wordCount&&(r.wordCount=this.options.wordCount),this.options.table&&(r.table=this.options.table),this.options.heading&&(r.heading=this.options.heading),this.options.alignment&&(r.alignment=this.options.alignment),this.options.ui&&(r.ui=this.options.ui),CKEditor5.create(this.target,r).then((t=>{this.applyEditableElementStyles(t),this.handleWordCountPlugin(t),this.applyReadOnly(t);const e=t.plugins.get("SourceEditing");t.model.document.on("change:data",(()=>{e.isSourceEditingMode||t.updateSourceElement(),this.target.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0}))})),this.options.debug&&window.CKEditorInspector.attach(t,{isCollapsed:!0})}))}))}createRenderRoot(){return this}render(){return html`
      <textarea
        id="${this.formEngine.id}"
        name="${this.formEngine.name}"
        class="form-control"
        rows="18"
        data-formengine-validation-rules="${this.formEngine.validationRules}"
        >${this.formEngine.value}</textarea>
    `}applyEditableElementStyles(t){const e=t.editing.view,o={"min-height":this.options.height,"min-width":this.options.width};Object.keys(o).forEach((t=>{let i=o[t];i&&(isFinite(i)&&!Number.isNaN(parseFloat(i))&&(i+="px"),e.change((o=>{o.setStyle(t,i,e.document.getRoot())})))}))}handleWordCountPlugin(t){if(t.plugins.has("WordCount")&&(this.options?.wordCount?.displayWords||this.options?.wordCount?.displayCharacters)){const e=t.plugins.get("WordCount");this.renderRoot.appendChild(e.wordCountContainer)}}applyReadOnly(t){this.options.readOnly&&t.enableReadOnlyMode("typo3-lock")}};__decorate([property({type:Object})],CKEditor5Element.prototype,"options",void 0),__decorate([property({type:Object,attribute:"form-engine"})],CKEditor5Element.prototype,"formEngine",void 0),__decorate([query("textarea")],CKEditor5Element.prototype,"target",void 0),CKEditor5Element=__decorate([customElement("typo3-rte-ckeditor-ckeditor5")],CKEditor5Element);export{CKEditor5Element};