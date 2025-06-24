import{Command as d,Plugin as o}from"@ckeditor/ckeditor5-core";import{getCode as m,parseKeystroke as g}from"@ckeditor/ckeditor5-utils";import{IconSelectAll as f}from"@ckeditor/ckeditor5-icons";import{ButtonView as p,MenuBarMenuListItemButtonView as A}from"@ckeditor/ckeditor5-ui";/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class h extends d{constructor(e){super(e),this.affectsData=!1}execute(){const e=this.editor.model,t=e.document.selection;let i=e.schema.getLimitElement(t);if(t.containsEntireContent(i)||!r(e.schema,i))do if(i=i.parent,!i)return;while(!r(e.schema,i));e.change(n=>{n.setSelection(i,"in")})}}function r(l,e){return l.isLimit(e)&&(l.checkChild(e,"$text")||l.checkChild(e,"paragraph"))}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const S=g("Ctrl+A");class a extends o{static get pluginName(){return"SelectAllEditing"}static get isOfficialPlugin(){return!0}init(){const e=this.editor,t=e.t,n=e.editing.view.document;e.commands.add("selectAll",new h(e)),this.listenTo(n,"keydown",(c,s)=>{m(s)===S&&(e.execute("selectAll"),s.preventDefault())}),e.accessibility.addKeystrokeInfos({keystrokes:[{label:t("Select all"),keystroke:"CTRL+A"}]})}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class u extends o{static get pluginName(){return"SelectAllUI"}static get isOfficialPlugin(){return!0}init(){const e=this.editor;e.ui.componentFactory.add("selectAll",()=>{const t=this._createButton(p);return t.set({tooltip:!0}),t}),e.ui.componentFactory.add("menuBar:selectAll",()=>this._createButton(A))}_createButton(e){const t=this.editor,i=t.locale,n=t.commands.get("selectAll"),c=new e(t.locale),s=i.t;return c.set({label:s("Select all"),icon:f,keystroke:"Ctrl+A"}),c.bind("isEnabled").to(n,"isEnabled"),this.listenTo(c,"execute",()=>{t.execute("selectAll"),t.editing.view.focus()}),c}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class w extends o{static get requires(){return[a,u]}static get pluginName(){return"SelectAll"}static get isOfficialPlugin(){return!0}}export{w as SelectAll,a as SelectAllEditing,u as SelectAllUI};
