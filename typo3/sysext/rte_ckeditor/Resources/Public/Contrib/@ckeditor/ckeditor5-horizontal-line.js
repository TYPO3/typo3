import{Command as e,Plugin as t,icons as n}from"@ckeditor/ckeditor5-core";import{findOptimalInsertionRange as o,toWidget as i,Widget as r}from"@ckeditor/ckeditor5-widget";import{ButtonView as l}from"@ckeditor/ckeditor5-ui";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class c extends e{refresh(){const e=this.editor.model,t=e.schema,n=e.document.selection;this.isEnabled=function(e,t,n){const i=function(e,t){const n=o(e,t),i=n.start.parent;if(i.isEmpty&&!i.is("element","$root"))return i.parent;return i}(e,n);return t.checkChild(i,"horizontalLine")}(n,t,e)}execute(){const e=this.editor.model;e.change((t=>{const n=t.createElement("horizontalLine");e.insertObject(n,null,null,{setSelection:"after"})}))}}!function(e,{insertAt:t}={}){if(!e||"undefined"==typeof document)return;const n=document.head||document.getElementsByTagName("head")[0],o=document.createElement("style");o.type="text/css",window.litNonce&&o.setAttribute("nonce",window.litNonce),"top"===t&&n.firstChild?n.insertBefore(o,n.firstChild):n.appendChild(o),o.styleSheet?o.styleSheet.cssText=e:o.appendChild(document.createTextNode(e))}(".ck-editor__editable .ck-horizontal-line{display:flow-root}.ck-content hr{background:#dedede;border:0;height:4px;margin:15px 0}");
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
class s extends t{static get pluginName(){return"HorizontalLineEditing"}init(){const e=this.editor,t=e.model.schema,n=e.t,o=e.conversion;t.register("horizontalLine",{inheritAllFrom:"$blockObject"}),o.for("dataDowncast").elementToElement({model:"horizontalLine",view:(e,{writer:t})=>t.createEmptyElement("hr")}),o.for("editingDowncast").elementToStructure({model:"horizontalLine",view:(e,{writer:t})=>{const o=n("Horizontal line"),r=t.createContainerElement("div",null,t.createEmptyElement("hr"));return t.addClass("ck-horizontal-line",r),t.setCustomProperty("hr",!0,r),function(e,t,n){return t.setCustomProperty("horizontalLine",!0,e),i(e,t,{label:n})}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(r,t,o)}}),o.for("upcast").elementToElement({view:"hr",model:"horizontalLine"}),e.commands.add("horizontalLine",new c(e))}}class a extends t{static get pluginName(){return"HorizontalLineUI"}init(){const e=this.editor,t=e.t;e.ui.componentFactory.add("horizontalLine",(o=>{const i=e.commands.get("horizontalLine"),r=new l(o);return r.set({label:t("Horizontal line"),icon:n.horizontalLine,tooltip:!0}),r.bind("isEnabled").to(i,"isEnabled"),this.listenTo(r,"execute",(()=>{e.execute("horizontalLine"),e.editing.view.focus()})),r}))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class d extends t{static get requires(){return[s,a,r]}static get pluginName(){return"HorizontalLine"}}export{d as HorizontalLine,s as HorizontalLineEditing,a as HorizontalLineUI};