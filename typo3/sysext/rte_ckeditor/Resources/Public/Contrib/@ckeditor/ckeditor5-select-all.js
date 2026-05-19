import{Command as d,Plugin as c}from"@ckeditor/ckeditor5-core";import{getCode as m,parseKeystroke as g}from"@ckeditor/ckeditor5-utils";import{IconSelectAll as f}from"@ckeditor/ckeditor5-icons";import{ButtonView as p,MenuBarMenuListItemButtonView as A}from"@ckeditor/ckeditor5-ui";/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*//**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var s=class extends d{constructor(e){super(e),this.affectsData=!1}execute(){const e=this.editor.model,t=e.document.selection;let i=e.schema.getLimitElement(t);if(t.containsEntireContent(i)||!o(e.schema,i))do if(i=i.parent,!i)return;while(!o(e.schema,i));e.change(n=>{n.setSelection(i,"in")})}};function o(e,t){return e.isLimit(t)&&(e.checkChild(t,"$text")||e.checkChild(t,"paragraph"))}/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const h=g("Ctrl+A");var r=class extends c{static get pluginName(){return"SelectAllEditing"}static get isOfficialPlugin(){return!0}init(){const e=this.editor,t=e.t,i=e.editing.view.document;e.commands.add("selectAll",new s(e)),this.listenTo(i,"keydown",(n,l)=>{m(l)===h&&(e.execute("selectAll"),l.preventDefault())}),e.accessibility.addKeystrokeInfos({keystrokes:[{label:t("Select all"),keystroke:"CTRL+A"}]})}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var a=class extends c{static get pluginName(){return"SelectAllUI"}static get isOfficialPlugin(){return!0}init(){const e=this.editor;e.ui.componentFactory.add("selectAll",()=>{const t=this._createButton(p);return t.set({tooltip:!0}),t}),e.ui.componentFactory.add("menuBar:selectAll",()=>this._createButton(A))}_createButton(e){const t=this.editor,i=t.locale,n=t.commands.get("selectAll"),l=new e(t.locale),u=i.t;return l.set({label:u("Select all"),icon:f,keystroke:"Ctrl+A"}),l.bind("isEnabled").to(n,"isEnabled"),this.listenTo(l,"execute",()=>{t.execute("selectAll"),t.editing.view.focus()}),l}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var S=class extends c{static get requires(){return[r,a]}static get pluginName(){return"SelectAll"}static get isOfficialPlugin(){return!0}};export{S as SelectAll,s as SelectAllCommand,r as SelectAllEditing,a as SelectAllUI};
