import{Command as _,Plugin as r}from"@ckeditor/ckeditor5-core";import{ModelDocumentSelection as M}from"@ckeditor/ckeditor5-engine";import{IconBold as G,IconCode as X,IconItalic as W,IconStrikethrough as j,IconSubscript as z,IconSuperscript as J,IconUnderline as Q}from"@ckeditor/ckeditor5-icons";import{MenuBarMenuListItemButtonView as a,ButtonView as l}from"@ckeditor/ckeditor5-ui";import{TwoStepCaretMovement as v,inlineHighlight as Y}from"@ckeditor/ckeditor5-typing";/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*//**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var c=class extends _{attributeKey;constructor(t,e){super(t),this.attributeKey=e}refresh(){const t=this.editor.model,e=t.document;this.value=this._getValueFromFirstAllowedNode(),this.isEnabled=t.schema.checkAttributeInSelection(e.selection,this.attributeKey)}execute(t={}){const e=this.editor.model,i=e.document.selection,n=t.forceValue===void 0?!this.value:t.forceValue;e.change(o=>{if(i.isCollapsed)n?o.setSelectionAttribute(this.attributeKey,!0):o.removeSelectionAttribute(this.attributeKey);else{const I=e.schema.getValidRanges(i.getRanges(),this.attributeKey,{includeEmptyRanges:!0});for(const h of I){let g=h,s=this.attributeKey;h.isCollapsed&&(g=h.start.parent,s=M._getStoreAttributeKey(this.attributeKey)),n?o.setAttribute(s,n,g):o.removeAttribute(s,g)}}})}_getValueFromFirstAllowedNode(){const t=this.editor.model,e=t.schema,i=t.document.selection;if(i.isCollapsed)return i.hasAttribute(this.attributeKey);for(const n of i.getRanges())for(const o of n.getItems())if(e.checkAttribute(o,this.attributeKey))return o.hasAttribute(this.attributeKey);return!1}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const m="bold";var S=class extends r{static get pluginName(){return"BoldEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:m}),t.model.schema.setAttributeProperties(m,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:m,view:"strong",upcastAlso:["b",i=>{const n=i.getStyle("font-weight");return n&&(n=="bold"||Number(n)>=600)?{name:!0,styles:["font-weight"]}:null}]}),t.commands.add(m,new c(t,m)),t.keystrokes.set("CTRL+B",m),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Bold text"),keystroke:"CTRL+B"}]})}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/function u({editor:t,commandName:e,plugin:i,icon:n,label:o,keystroke:I}){return h=>{const g=t.commands.get(e),s=new h(t.locale);return s.set({label:o,icon:n,keystroke:I,isToggleable:!0}),s.bind("isEnabled").to(g,"isEnabled"),s.bind("isOn").to(g,"value"),s instanceof a?s.set({role:"menuitemcheckbox"}):s.set({tooltip:!0}),i.listenTo(s,"execute",()=>{t.execute(e),t.editing.view.focus()}),s}}/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const x="bold";var E=class extends r{static get pluginName(){return"BoldUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:x,plugin:this,icon:G,label:e("Bold"),keystroke:"CTRL+B"});t.ui.componentFactory.add(x,()=>i(l)),t.ui.componentFactory.add("menuBar:bold",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var Z=class extends r{static get requires(){return[S,E]}static get pluginName(){return"Bold"}static get isOfficialPlugin(){return!0}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const d="code",tt="ck-code_selected";var C=class extends r{static get pluginName(){return"CodeEditing"}static get isOfficialPlugin(){return!0}static get requires(){return[v]}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:d}),t.model.schema.setAttributeProperties(d,{isFormatting:!0,copyOnEnter:!1}),t.conversion.attributeToElement({model:d,view:"code"}),t.commands.add(d,new c(t,d)),t.plugins.get(v).registerAttribute(d),Y(t,d,"code",tt),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Move out of an inline code style"),keystroke:[["arrowleft","arrowleft"],["arrowright","arrowright"]]}]})}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const A="code";var B=class extends r{static get pluginName(){return"CodeUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:A,plugin:this,icon:X,label:e("Code")});t.ui.componentFactory.add(A,()=>i(l)),t.ui.componentFactory.add("menuBar:code",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var et=class extends r{static get requires(){return[C,B]}static get pluginName(){return"Code"}static get isOfficialPlugin(){return!0}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const p="italic";var O=class extends r{static get pluginName(){return"ItalicEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:p}),t.model.schema.setAttributeProperties(p,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:p,view:"i",upcastAlso:["em",{styles:{"font-style":"italic"}}]}),t.commands.add(p,new c(t,p)),t.keystrokes.set("CTRL+I",p),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Italic text"),keystroke:"CTRL+I"}]})}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const T="italic";var P=class extends r{static get pluginName(){return"ItalicUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:T,plugin:this,icon:W,keystroke:"CTRL+I",label:e("Italic")});t.ui.componentFactory.add(T,()=>i(l)),t.ui.componentFactory.add("menuBar:italic",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var it=class extends r{static get requires(){return[O,P]}static get pluginName(){return"Italic"}static get isOfficialPlugin(){return!0}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const b="strikethrough";var N=class extends r{static get pluginName(){return"StrikethroughEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:b}),t.model.schema.setAttributeProperties(b,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:b,view:"s",upcastAlso:["del","strike",{styles:{"text-decoration":"line-through"}}]}),t.commands.add(b,new c(t,b)),t.keystrokes.set("CTRL+SHIFT+X","strikethrough"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Strikethrough text"),keystroke:"CTRL+SHIFT+X"}]})}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const U="strikethrough";var w=class extends r{static get pluginName(){return"StrikethroughUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:U,plugin:this,icon:j,keystroke:"CTRL+SHIFT+X",label:e("Strikethrough")});t.ui.componentFactory.add(U,()=>i(l)),t.ui.componentFactory.add("menuBar:strikethrough",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var rt=class extends r{static get requires(){return[N,w]}static get pluginName(){return"Strikethrough"}static get isOfficialPlugin(){return!0}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const f="subscript";var R=class extends r{static get pluginName(){return"SubscriptEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor;t.model.schema.extend("$text",{allowAttributes:f}),t.model.schema.setAttributeProperties(f,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:f,view:"sub",upcastAlso:[{styles:{"vertical-align":"sub"}}]}),t.commands.add(f,new c(t,f))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const F="subscript";var L=class extends r{static get pluginName(){return"SubscriptUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:F,plugin:this,icon:z,label:e("Subscript")});t.ui.componentFactory.add(F,()=>i(l)),t.ui.componentFactory.add("menuBar:subscript",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var st=class extends r{static get requires(){return[R,L]}static get pluginName(){return"Subscript"}static get isOfficialPlugin(){return!0}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const y="superscript";var K=class extends r{static get pluginName(){return"SuperscriptEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor;t.model.schema.extend("$text",{allowAttributes:y}),t.model.schema.setAttributeProperties(y,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:y,view:"sup",upcastAlso:[{styles:{"vertical-align":"super"}}]}),t.commands.add(y,new c(t,y))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const $="superscript";var H=class extends r{static get pluginName(){return"SuperscriptUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:$,plugin:this,icon:J,label:e("Superscript")});t.ui.componentFactory.add($,()=>i(l)),t.ui.componentFactory.add("menuBar:superscript",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var nt=class extends r{static get requires(){return[K,H]}static get pluginName(){return"Superscript"}static get isOfficialPlugin(){return!0}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const k="underline";var q=class extends r{static get pluginName(){return"UnderlineEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:k}),t.model.schema.setAttributeProperties(k,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:k,view:"u",upcastAlso:{styles:{"text-decoration":"underline"}}}),t.commands.add(k,new c(t,k)),t.keystrokes.set("CTRL+U","underline"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Underline text"),keystroke:"CTRL+U"}]})}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const D="underline";var V=class extends r{static get pluginName(){return"UnderlineUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:D,plugin:this,icon:Q,label:e("Underline"),keystroke:"CTRL+U"});t.ui.componentFactory.add(D,()=>i(l)),t.ui.componentFactory.add("menuBar:underline",()=>i(a))}};/**
* @license Copyright (c) 2003-2026, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/var ot=class extends r{static get requires(){return[q,V]}static get pluginName(){return"Underline"}static get isOfficialPlugin(){return!0}};export{c as AttributeCommand,Z as Bold,S as BoldEditing,E as BoldUI,et as Code,C as CodeEditing,B as CodeUI,it as Italic,O as ItalicEditing,P as ItalicUI,rt as Strikethrough,N as StrikethroughEditing,w as StrikethroughUI,st as Subscript,R as SubscriptEditing,L as SubscriptUI,nt as Superscript,K as SuperscriptEditing,H as SuperscriptUI,ot as Underline,q as UnderlineEditing,V as UnderlineUI,u as _getBasicStylesButtonCreator};
