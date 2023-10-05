import{Command as e,Plugin as t}from"@ckeditor/ckeditor5-core";import{findOptimalInsertionRange as n,toWidget as o,Widget as i}from"@ckeditor/ckeditor5-widget";import{ButtonView as r}from"@ckeditor/ckeditor5-ui";
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class l extends e{refresh(){const e=this.editor.model,t=e.schema,o=e.document.selection;this.isEnabled=function(e,t,o){const i=function(e,t){const o=n(e,t),i=o.start.parent;if(i.isEmpty&&!i.is("element","$root"))return i.parent;return i}(e,o);return t.checkChild(i,"horizontalLine")}(o,t,e)}execute(){const e=this.editor.model;e.change((t=>{const n=t.createElement("horizontalLine");e.insertObject(n,null,null,{setSelection:"after"})}))}}!function(e,{insertAt:t}={}){if(!e||"undefined"==typeof document)return;const n=document.head||document.getElementsByTagName("head")[0],o=document.createElement("style");o.type="text/css",window.litNonce&&o.setAttribute("nonce",window.litNonce),"top"===t&&n.firstChild?n.insertBefore(o,n.firstChild):n.appendChild(o),o.styleSheet?o.styleSheet.cssText=e:o.appendChild(document.createTextNode(e))}(".ck-editor__editable .ck-horizontal-line{display:flow-root}.ck-content hr{background:#dedede;border:0;height:4px;margin:15px 0}");
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
class s extends t{static get pluginName(){return"HorizontalLineEditing"}init(){const e=this.editor,t=e.model.schema,n=e.t,i=e.conversion;t.register("horizontalLine",{inheritAllFrom:"$blockObject"}),i.for("dataDowncast").elementToElement({model:"horizontalLine",view:(e,{writer:t})=>t.createEmptyElement("hr")}),i.for("editingDowncast").elementToStructure({model:"horizontalLine",view:(e,{writer:t})=>{const i=n("Horizontal line"),r=t.createContainerElement("div",null,t.createEmptyElement("hr"));return t.addClass("ck-horizontal-line",r),t.setCustomProperty("hr",!0,r),function(e,t,n){return t.setCustomProperty("horizontalLine",!0,e),o(e,t,{label:n})}(r,t,i)}}),i.for("upcast").elementToElement({view:"hr",model:"horizontalLine"}),e.commands.add("horizontalLine",new l(e))}}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
class c extends t{static get pluginName(){return"HorizontalLineUI"}init(){const e=this.editor,t=e.t;e.ui.componentFactory.add("horizontalLine",(n=>{const o=e.commands.get("horizontalLine"),i=new r(n);return i.set({label:t("Horizontal line"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 9h16v2H2z"/></svg>',tooltip:!0}),i.bind("isEnabled").to(o,"isEnabled"),this.listenTo(i,"execute",(()=>{e.execute("horizontalLine"),e.editing.view.focus()})),i}))}}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class a extends t{static get requires(){return[s,c,i]}static get pluginName(){return"HorizontalLine"}}export{a as HorizontalLine,s as HorizontalLineEditing,c as HorizontalLineUI};