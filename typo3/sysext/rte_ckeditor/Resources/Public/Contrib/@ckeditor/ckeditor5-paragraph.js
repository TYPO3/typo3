import{Command as e,Plugin as t,icons as r}from"@ckeditor/ckeditor5-core";import{first as a}from"@ckeditor/ckeditor5-utils";import{ButtonView as i}from"@ckeditor/ckeditor5-ui";
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class n extends e{constructor(e){super(e),this._isEnabledBasedOnSelection=!1}refresh(){const e=this.editor.model,t=e.document,r=a(t.selection.getSelectedBlocks());this.value=!!r&&r.is("element","paragraph"),this.isEnabled=!!r&&o(r,e.schema)}execute(e={}){const t=this.editor.model,r=t.document,a=e.selection||r.selection;t.canEditAt(a)&&t.change((e=>{const r=a.getSelectedBlocks();for(const a of r)!a.is("element","paragraph")&&o(a,t.schema)&&e.rename(a,"paragraph")}))}}function o(e,t){return t.checkChild(e.parent,"paragraph")&&!t.isObject(e)}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class s extends e{constructor(e){super(e),this._isEnabledBasedOnSelection=!1}execute(e){const t=this.editor.model,r=e.attributes;let a=e.position;t.canEditAt(a)&&t.change((e=>{if(a=this._findPositionToInsertParagraph(a,e),!a)return;const i=e.createElement("paragraph");r&&t.schema.setAllowedAttributes(i,r,e),t.insertContent(i,a),e.setSelection(i,"in")}))}_findPositionToInsertParagraph(e,t){const r=this.editor.model;if(r.schema.checkChild(e,"paragraph"))return e;const a=r.schema.findAllowedParent(e,"paragraph");if(!a)return null;const i=e.parent,n=r.schema.checkChild(i,"$text");return i.isEmpty||n&&e.isAtEnd?r.createPositionAfter(i):!i.isEmpty&&n&&e.isAtStart?r.createPositionBefore(i):t.split(e,a).position}}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class c extends t{static get pluginName(){return"Paragraph"}init(){const e=this.editor,t=e.model;e.commands.add("paragraph",new n(e)),e.commands.add("insertParagraph",new s(e)),t.schema.register("paragraph",{inheritAllFrom:"$block"}),e.conversion.elementToElement({model:"paragraph",view:"p"}),e.conversion.for("upcast").elementToElement({model:(e,{writer:t})=>c.paragraphLikeElements.has(e.name)?e.isEmpty?null:t.createElement("paragraph"):null,view:/.+/,converterPriority:"low"})}}c.paragraphLikeElements=new Set(["blockquote","dd","div","dt","h1","h2","h3","h4","h5","h6","li","p","td","th"]);
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
const l=r.paragraph;class d extends t{static get requires(){return[c]}init(){const e=this.editor,t=e.t;e.ui.componentFactory.add("paragraph",(r=>{const a=new i(r),n=e.commands.get("paragraph");return a.label=t("Paragraph"),a.icon=l,a.tooltip=!0,a.isToggleable=!0,a.bind("isEnabled").to(n),a.bind("isOn").to(n,"value"),a.on("execute",(()=>{e.execute("paragraph")})),a}))}}export{c as Paragraph,d as ParagraphButtonUI};