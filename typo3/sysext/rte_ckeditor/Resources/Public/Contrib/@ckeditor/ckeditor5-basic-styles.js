import{Command as D,Plugin as n}from"@ckeditor/ckeditor5-core";import{IconBold as G,IconCode as M,IconItalic as X,IconStrikethrough as j,IconSubscript as W,IconSuperscript as z,IconUnderline as J}from"@ckeditor/ckeditor5-icons";import{MenuBarMenuListItemButtonView as a,ButtonView as d}from"@ckeditor/ckeditor5-ui";import{TwoStepCaretMovement as w,inlineHighlight as Q}from"@ckeditor/ckeditor5-typing";/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class l extends D{attributeKey;constructor(t,e){super(t),this.attributeKey=e}refresh(){const t=this.editor.model,e=t.document;this.value=this._getValueFromFirstAllowedNode(),this.isEnabled=t.schema.checkAttributeInSelection(e.selection,this.attributeKey)}execute(t={}){const e=this.editor.model,o=e.document.selection,c=t.forceValue===void 0?!this.value:t.forceValue;e.change(g=>{if(o.isCollapsed)c?g.setSelectionAttribute(this.attributeKey,!0):g.removeSelectionAttribute(this.attributeKey);else{const I=e.schema.getValidRanges(o.getRanges(),this.attributeKey);for(const r of I)c?g.setAttribute(this.attributeKey,c,r):g.removeAttribute(this.attributeKey,r)}})}_getValueFromFirstAllowedNode(){const t=this.editor.model,e=t.schema,i=t.document.selection;if(i.isCollapsed)return i.hasAttribute(this.attributeKey);for(const o of i.getRanges())for(const c of o.getItems())if(e.checkAttribute(c,this.attributeKey))return c.hasAttribute(this.attributeKey);return!1}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const p="bold";class A extends n{static get pluginName(){return"BoldEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:p}),t.model.schema.setAttributeProperties(p,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:p,view:"strong",upcastAlso:["b",i=>{const o=i.getStyle("font-weight");return o&&(o=="bold"||Number(o)>=600)?{name:!0,styles:["font-weight"]}:null}]}),t.commands.add(p,new l(t,p)),t.keystrokes.set("CTRL+B",p),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Bold text"),keystroke:"CTRL+B"}]})}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/function u({editor:s,commandName:t,plugin:e,icon:i,label:o,keystroke:c}){return g=>{const I=s.commands.get(t),r=new g(s.locale);return r.set({label:o,icon:i,keystroke:c,isToggleable:!0}),r.bind("isEnabled").to(I,"isEnabled"),r.bind("isOn").to(I,"value"),r instanceof a?r.set({role:"menuitemcheckbox"}):r.set({tooltip:!0}),e.listenTo(r,"execute",()=>{s.execute(t),s.editing.view.focus()}),r}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const x="bold";class O extends n{static get pluginName(){return"BoldUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:x,plugin:this,icon:G,label:e("Bold"),keystroke:"CTRL+B"});t.ui.componentFactory.add(x,()=>i(d)),t.ui.componentFactory.add("menuBar:"+x,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class Y extends n{static get requires(){return[A,O]}static get pluginName(){return"Bold"}static get isOfficialPlugin(){return!0}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const m="code",Z="ck-code_selected";class P extends n{static get pluginName(){return"CodeEditing"}static get isOfficialPlugin(){return!0}static get requires(){return[w]}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:m}),t.model.schema.setAttributeProperties(m,{isFormatting:!0,copyOnEnter:!1}),t.conversion.attributeToElement({model:m,view:"code"}),t.commands.add(m,new l(t,m)),t.plugins.get(w).registerAttribute(m),Q(t,m,"code",Z),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Move out of an inline code style"),keystroke:[["arrowleft","arrowleft"],["arrowright","arrowright"]]}]})}}function tt(s,{insertAt:t}={}){if(typeof document>"u")return;const e=document.head||document.getElementsByTagName("head")[0],i=document.createElement("style");i.type="text/css",window.litNonce&&i.setAttribute("nonce",window.litNonce),t==="top"&&e.firstChild?e.insertBefore(i,e.firstChild):e.appendChild(i),i.styleSheet?i.styleSheet.cssText=s:i.appendChild(document.createTextNode(s))}tt(".ck-content code{background-color:hsla(0,0%,78%,.3);border-radius:2px;padding:.15em}.ck.ck-editor__editable .ck-code_selected{background-color:hsla(0,0%,78%,.5)}");/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const S="code";class U extends n{static get pluginName(){return"CodeUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:S,plugin:this,icon:M,label:e("Code")});t.ui.componentFactory.add(S,()=>i(d)),t.ui.componentFactory.add("menuBar:"+S,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class et extends n{static get requires(){return[P,U]}static get pluginName(){return"Code"}static get isOfficialPlugin(){return!0}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const h="italic";class F extends n{static get pluginName(){return"ItalicEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:h}),t.model.schema.setAttributeProperties(h,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:h,view:"i",upcastAlso:["em",{styles:{"font-style":"italic"}}]}),t.commands.add(h,new l(t,h)),t.keystrokes.set("CTRL+I",h),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Italic text"),keystroke:"CTRL+I"}]})}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const E="italic";class R extends n{static get pluginName(){return"ItalicUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:E,plugin:this,icon:X,keystroke:"CTRL+I",label:e("Italic")});t.ui.componentFactory.add(E,()=>i(d)),t.ui.componentFactory.add("menuBar:"+E,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class it extends n{static get requires(){return[F,R]}static get pluginName(){return"Italic"}static get isOfficialPlugin(){return!0}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const f="strikethrough";class v extends n{static get pluginName(){return"StrikethroughEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:f}),t.model.schema.setAttributeProperties(f,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:f,view:"s",upcastAlso:["del","strike",{styles:{"text-decoration":"line-through"}}]}),t.commands.add(f,new l(t,f)),t.keystrokes.set("CTRL+SHIFT+X","strikethrough"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Strikethrough text"),keystroke:"CTRL+SHIFT+X"}]})}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const C="strikethrough";class L extends n{static get pluginName(){return"StrikethroughUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:C,plugin:this,icon:j,keystroke:"CTRL+SHIFT+X",label:e("Strikethrough")});t.ui.componentFactory.add(C,()=>i(d)),t.ui.componentFactory.add("menuBar:"+C,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class st extends n{static get requires(){return[v,L]}static get pluginName(){return"Strikethrough"}static get isOfficialPlugin(){return!0}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const b="subscript";class K extends n{static get pluginName(){return"SubscriptEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor;t.model.schema.extend("$text",{allowAttributes:b}),t.model.schema.setAttributeProperties(b,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:b,view:"sub",upcastAlso:[{styles:{"vertical-align":"sub"}}]}),t.commands.add(b,new l(t,b))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const T="subscript";class $ extends n{static get pluginName(){return"SubscriptUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:T,plugin:this,icon:W,label:e("Subscript")});t.ui.componentFactory.add(T,()=>i(d)),t.ui.componentFactory.add("menuBar:"+T,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class nt extends n{static get requires(){return[K,$]}static get pluginName(){return"Subscript"}static get isOfficialPlugin(){return!0}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const y="superscript";class H extends n{static get pluginName(){return"SuperscriptEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor;t.model.schema.extend("$text",{allowAttributes:y}),t.model.schema.setAttributeProperties(y,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:y,view:"sup",upcastAlso:[{styles:{"vertical-align":"super"}}]}),t.commands.add(y,new l(t,y))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const B="superscript";class q extends n{static get pluginName(){return"SuperscriptUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:B,plugin:this,icon:z,label:e("Superscript")});t.ui.componentFactory.add(B,()=>i(d)),t.ui.componentFactory.add("menuBar:"+B,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class rt extends n{static get requires(){return[H,q]}static get pluginName(){return"Superscript"}static get isOfficialPlugin(){return!0}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const k="underline";class _ extends n{static get pluginName(){return"UnderlineEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:k}),t.model.schema.setAttributeProperties(k,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:k,view:"u",upcastAlso:{styles:{"text-decoration":"underline"}}}),t.commands.add(k,new l(t,k)),t.keystrokes.set("CTRL+U","underline"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Underline text"),keystroke:"CTRL+U"}]})}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/const N="underline";class V extends n{static get pluginName(){return"UnderlineUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=u({editor:t,commandName:N,plugin:this,icon:J,label:e("Underline"),keystroke:"CTRL+U"});t.ui.componentFactory.add(N,()=>i(d)),t.ui.componentFactory.add("menuBar:"+N,()=>i(a))}}/**
* @license Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
* For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
*/class ot extends n{static get requires(){return[_,V]}static get pluginName(){return"Underline"}static get isOfficialPlugin(){return!0}}export{l as AttributeCommand,Y as Bold,A as BoldEditing,O as BoldUI,et as Code,P as CodeEditing,U as CodeUI,it as Italic,F as ItalicEditing,R as ItalicUI,st as Strikethrough,v as StrikethroughEditing,L as StrikethroughUI,nt as Subscript,K as SubscriptEditing,$ as SubscriptUI,rt as Superscript,H as SuperscriptEditing,q as SuperscriptUI,ot as Underline,_ as UnderlineEditing,V as UnderlineUI,u as _getBasicStylesButtonCreator};
