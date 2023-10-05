import{Plugin as t,Command as e}from"@ckeditor/ckeditor5-core";import{ButtonView as o}from"@ckeditor/ckeditor5-ui";import{first as i}from"@ckeditor/ckeditor5-utils";
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
const s="removeFormat";class r extends t{static get pluginName(){return"RemoveFormatUI"}init(){const t=this.editor,e=t.t;t.ui.componentFactory.add(s,(i=>{const r=t.commands.get(s),n=new o(i);return n.set({label:e("Remove Format"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M8.69 14.915c.053.052.173.083.36.093a.366.366 0 0 1 .345.485l-.003.01a.738.738 0 0 1-.697.497h-2.67a.374.374 0 0 1-.353-.496l.013-.038a.681.681 0 0 1 .644-.458c.197-.012.325-.043.386-.093a.28.28 0 0 0 .072-.11L9.592 4.5H6.269c-.359-.017-.609.013-.75.09-.142.078-.289.265-.442.563-.192.29-.516.464-.864.464H4.17a.43.43 0 0 1-.407-.569L4.46 3h13.08l-.62 2.043a.81.81 0 0 1-.775.574h-.114a.486.486 0 0 1-.486-.486c.001-.284-.054-.464-.167-.54-.112-.076-.367-.106-.766-.091h-3.28l-2.68 10.257c-.006.074.007.127.038.158zM3 17h8a.5.5 0 1 1 0 1H3a.5.5 0 1 1 0-1zm11.299 1.17a.75.75 0 1 1-1.06-1.06l1.414-1.415-1.415-1.414a.75.75 0 0 1 1.06-1.06l1.415 1.414 1.414-1.415a.75.75 0 1 1 1.06 1.06l-1.413 1.415 1.414 1.415a.75.75 0 0 1-1.06 1.06l-1.415-1.414-1.414 1.414z"/></svg>',tooltip:!0}),n.bind("isOn","isEnabled").to(r,"value","isEnabled"),this.listenTo(n,"execute",(()=>{t.execute(s),t.editing.view.focus()})),n}))}}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class n extends e{refresh(){const t=this.editor.model;this.isEnabled=!!i(this._getFormattingItems(t.document.selection,t.schema))}execute(){const t=this.editor.model,e=t.schema;t.change((o=>{for(const i of this._getFormattingItems(t.document.selection,e))if(i.is("selection"))for(const t of this._getFormattingAttributes(i,e))o.removeSelectionAttribute(t);else{const t=o.createRangeOn(i);for(const s of this._getFormattingAttributes(i,e))o.removeAttribute(s,t)}}))}*_getFormattingItems(t,e){const o=t=>!!i(this._getFormattingAttributes(t,e));for(const i of t.getRanges())for(const t of i.getItems())!e.isBlock(t)&&o(t)&&(yield t);for(const e of t.getSelectedBlocks())o(e)&&(yield e);o(t)&&(yield t)}*_getFormattingAttributes(t,e){for(const[o]of t.getAttributes()){const t=e.getAttributeProperties(o);t&&t.isFormatting&&(yield o)}}}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class c extends t{static get pluginName(){return"RemoveFormatEditing"}init(){const t=this.editor;t.commands.add("removeFormat",new n(t))}}
/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class a extends t{static get requires(){return[c,r]}static get pluginName(){return"RemoveFormat"}}export{a as RemoveFormat,c as RemoveFormatEditing,r as RemoveFormatUI};