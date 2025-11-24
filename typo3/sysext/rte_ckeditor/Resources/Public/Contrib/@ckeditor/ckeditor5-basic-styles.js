import{Command as t,Plugin as e,icons as i}from"@ckeditor/ckeditor5-core";import{MenuBarMenuListItemButtonView as s,ButtonView as n}from"@ckeditor/ckeditor5-ui";import{TwoStepCaretMovement as r,inlineHighlight as o}from"@ckeditor/ckeditor5-typing";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class c extends t{constructor(t,e){super(t),this.attributeKey=e}refresh(){const t=this.editor.model,e=t.document;this.value=this._getValueFromFirstAllowedNode(),this.isEnabled=t.schema.checkAttributeInSelection(e.selection,this.attributeKey)}execute(t={}){const e=this.editor.model,i=e.document.selection,s=void 0===t.forceValue?!this.value:t.forceValue;e.change(t=>{if(i.isCollapsed)s?t.setSelectionAttribute(this.attributeKey,!0):t.removeSelectionAttribute(this.attributeKey);else{const n=e.schema.getValidRanges(i.getRanges(),this.attributeKey);for(const e of n)s?t.setAttribute(this.attributeKey,s,e):t.removeAttribute(this.attributeKey,e)}})}_getValueFromFirstAllowedNode(){const t=this.editor.model,e=t.schema,i=t.document.selection;if(i.isCollapsed)return i.hasAttribute(this.attributeKey);for(const t of i.getRanges())for(const i of t.getItems())if(e.checkAttribute(i,this.attributeKey))return i.hasAttribute(this.attributeKey);return!1}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const a="bold";class l extends e{static get pluginName(){return"BoldEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:a}),t.model.schema.setAttributeProperties(a,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:a,view:"strong",upcastAlso:["b",t=>{const e=t.getStyle("font-weight");return e&&("bold"==e||Number(e)>=600)?{name:!0,styles:["font-weight"]}:null}]}),t.commands.add(a,new c(t,a)),t.keystrokes.set("CTRL+B",a),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Bold text"),keystroke:"CTRL+B"}]})}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function d({editor:t,commandName:e,plugin:i,icon:n,label:r,keystroke:o}){return c=>{const a=t.commands.get(e),l=new c(t.locale);return l.set({label:r,icon:n,keystroke:o,isToggleable:!0}),l.bind("isEnabled").to(a,"isEnabled"),l.bind("isOn").to(a,"value"),l instanceof s?l.set({role:"menuitemcheckbox"}):l.set({tooltip:!0}),i.listenTo(l,"execute",()=>{t.execute(e),t.editing.view.focus()}),l}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const u="bold";class g extends e{static get pluginName(){return"BoldUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,r=d({editor:t,commandName:u,plugin:this,icon:i.bold,label:e("Bold"),keystroke:"CTRL+B"});t.ui.componentFactory.add(u,()=>r(n)),t.ui.componentFactory.add("menuBar:"+u,()=>r(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class m extends e{static get requires(){return[l,g]}static get pluginName(){return"Bold"}static get isOfficialPlugin(){return!0}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const h="code";class p extends e{static get pluginName(){return"CodeEditing"}static get isOfficialPlugin(){return!0}static get requires(){return[r]}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:h}),t.model.schema.setAttributeProperties(h,{isFormatting:!0,copyOnEnter:!1}),t.conversion.attributeToElement({model:h,view:"code",upcastAlso:{styles:{"word-wrap":"break-word"}}}),t.commands.add(h,new c(t,h)),t.plugins.get(r).registerAttribute(h),o(t,h,"code","ck-code_selected"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Move out of an inline code style"),keystroke:[["arrowleft","arrowleft"],["arrowright","arrowright"]]}]})}}!function(t,{insertAt:e}={}){if("undefined"==typeof document)return;const i=document.head||document.getElementsByTagName("head")[0],s=document.createElement("style");s.type="text/css",window.litNonce&&s.setAttribute("nonce",window.litNonce),"top"===e&&i.firstChild?i.insertBefore(s,i.firstChild):i.appendChild(s),s.styleSheet?s.styleSheet.cssText=t:s.appendChild(document.createTextNode(t))}(".ck-content code{background-color:hsla(0,0%,78%,.3);border-radius:2px;padding:.15em}.ck.ck-editor__editable .ck-code_selected{background-color:hsla(0,0%,78%,.5)}");
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
const b="code";class f extends e{static get pluginName(){return"CodeUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=d({editor:t,commandName:b,plugin:this,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m12.5 5.7 5.2 3.9v1.3l-5.6 4c-.1.2-.3.2-.5.2-.3-.1-.6-.7-.6-1l.3-.4 4.7-3.5L11.5 7l-.2-.2c-.1-.3-.1-.6 0-.8.2-.2.5-.4.8-.4a.8.8 0 0 1 .4.1zm-5.2 0L2 9.6v1.3l5.6 4c.1.2.3.2.5.2.3-.1.7-.7.6-1 0-.1 0-.3-.2-.4l-5-3.5L8.2 7l.2-.2c.1-.3.1-.6 0-.8-.2-.2-.5-.4-.8-.4a.8.8 0 0 0-.3.1z"/></svg>',label:e("Code")});t.ui.componentFactory.add(b,()=>i(n)),t.ui.componentFactory.add("menuBar:"+b,()=>i(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class y extends e{static get requires(){return[p,f]}static get pluginName(){return"Code"}static get isOfficialPlugin(){return!0}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const k="italic";class w extends e{static get pluginName(){return"ItalicEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:k}),t.model.schema.setAttributeProperties(k,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:k,view:"i",upcastAlso:["em",{styles:{"font-style":"italic"}}]}),t.commands.add(k,new c(t,k)),t.keystrokes.set("CTRL+I",k),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Italic text"),keystroke:"CTRL+I"}]})}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
const x="italic";class v extends e{static get pluginName(){return"ItalicUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=d({editor:t,commandName:x,plugin:this,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m9.586 14.633.021.004c-.036.335.095.655.393.962.082.083.173.15.274.201h1.474a.6.6 0 1 1 0 1.2H5.304a.6.6 0 0 1 0-1.2h1.15c.474-.07.809-.182 1.005-.334.157-.122.291-.32.404-.597l2.416-9.55a1.053 1.053 0 0 0-.281-.823 1.12 1.12 0 0 0-.442-.296H8.15a.6.6 0 0 1 0-1.2h6.443a.6.6 0 1 1 0 1.2h-1.195c-.376.056-.65.155-.823.296-.215.175-.423.439-.623.79l-2.366 9.347z"/></svg>',keystroke:"CTRL+I",label:e("Italic")});t.ui.componentFactory.add(x,()=>i(n)),t.ui.componentFactory.add("menuBar:"+x,()=>i(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class A extends e{static get requires(){return[w,v]}static get pluginName(){return"Italic"}static get isOfficialPlugin(){return!0}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const N="strikethrough";class O extends e{static get pluginName(){return"StrikethroughEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:N}),t.model.schema.setAttributeProperties(N,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:N,view:"s",upcastAlso:["del","strike",{styles:{"text-decoration":"line-through"}}]}),t.commands.add(N,new c(t,N)),t.keystrokes.set("CTRL+SHIFT+X","strikethrough"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Strikethrough text"),keystroke:"CTRL+SHIFT+X"}]})}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
const F="strikethrough";class P extends e{static get pluginName(){return"StrikethroughUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=d({editor:t,commandName:F,plugin:this,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M7 16.4c-.8-.4-1.5-.9-2.2-1.5a.6.6 0 0 1-.2-.5l.3-.6h1c1 1.2 2.1 1.7 3.7 1.7 1 0 1.8-.3 2.3-.6.6-.4.6-1.2.6-1.3.2-1.2-.9-2.1-.9-2.1h2.1c.3.7.4 1.2.4 1.7v.8l-.6 1.2c-.6.8-1.1 1-1.6 1.2a6 6 0 0 1-2.4.6c-1 0-1.8-.3-2.5-.6zM6.8 9 6 8.3c-.4-.5-.5-.8-.5-1.6 0-.7.1-1.3.5-1.8.4-.6 1-1 1.6-1.3a6.3 6.3 0 0 1 4.7 0 4 4 0 0 1 1.7 1l.3.7c0 .1.2.4-.2.7-.4.2-.9.1-1 0a3 3 0 0 0-1.2-1c-.4-.2-1-.3-2-.4-.7 0-1.4.2-2 .6-.8.6-1 .8-1 1.5 0 .8.5 1 1.2 1.5.6.4 1.1.7 1.9 1H6.8z"/><path d="M3 10.5V9h14v1.5z"/></svg>',keystroke:"CTRL+SHIFT+X",label:e("Strikethrough")});t.ui.componentFactory.add(F,()=>i(n)),t.ui.componentFactory.add("menuBar:"+F,()=>i(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class T extends e{static get requires(){return[O,P]}static get pluginName(){return"Strikethrough"}static get isOfficialPlugin(){return!0}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const E="subscript";class I extends e{static get pluginName(){return"SubscriptEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor;t.model.schema.extend("$text",{allowAttributes:E}),t.model.schema.setAttributeProperties(E,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:E,view:"sub",upcastAlso:[{styles:{"vertical-align":"sub"}}]}),t.commands.add(E,new c(t,E))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
const B="subscript";class C extends e{static get pluginName(){return"SubscriptUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=d({editor:t,commandName:B,plugin:this,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m7.03 10.349 3.818-3.819a.8.8 0 1 1 1.132 1.132L8.16 11.48l3.819 3.818a.8.8 0 1 1-1.132 1.132L7.03 12.61l-3.818 3.82a.8.8 0 1 1-1.132-1.132L5.9 11.48 2.08 7.662A.8.8 0 1 1 3.212 6.53l3.818 3.82zm8.147 7.829h2.549c.254 0 .447.05.58.152a.49.49 0 0 1 .201.413.54.54 0 0 1-.159.393c-.105.108-.266.162-.48.162h-3.594c-.245 0-.435-.066-.572-.197a.621.621 0 0 1-.205-.463c0-.114.044-.265.132-.453a1.62 1.62 0 0 1 .288-.444c.433-.436.824-.81 1.172-1.122.348-.312.597-.517.747-.615.267-.183.49-.368.667-.553.177-.185.312-.375.405-.57.093-.194.139-.384.139-.57a1.008 1.008 0 0 0-.554-.917 1.197 1.197 0 0 0-.56-.133c-.426 0-.761.182-1.005.546a2.332 2.332 0 0 0-.164.39 1.609 1.609 0 0 1-.258.488c-.096.114-.237.17-.423.17a.558.558 0 0 1-.405-.156.568.568 0 0 1-.161-.427c0-.218.05-.446.151-.683.101-.238.252-.453.452-.646s.454-.349.762-.467a2.998 2.998 0 0 1 1.081-.178c.498 0 .923.076 1.274.228a1.916 1.916 0 0 1 1.004 1.032 1.984 1.984 0 0 1-.156 1.794c-.2.32-.405.572-.613.754-.208.182-.558.468-1.048.857-.49.39-.826.691-1.008.906a2.703 2.703 0 0 0-.24.309z"/></svg>',label:e("Subscript")});t.ui.componentFactory.add(B,()=>i(n)),t.ui.componentFactory.add("menuBar:"+B,()=>i(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class S extends e{static get requires(){return[I,C]}static get pluginName(){return"Subscript"}static get isOfficialPlugin(){return!0}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const L="superscript";class K extends e{static get pluginName(){return"SuperscriptEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor;t.model.schema.extend("$text",{allowAttributes:L}),t.model.schema.setAttributeProperties(L,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:L,view:"sup",upcastAlso:[{styles:{"vertical-align":"super"}}]}),t.commands.add(L,new c(t,L))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
const R="superscript";class U extends e{static get pluginName(){return"SuperscriptUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=d({editor:t,commandName:R,plugin:this,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M15.677 8.678h2.549c.254 0 .447.05.58.152a.49.49 0 0 1 .201.413.54.54 0 0 1-.159.393c-.105.108-.266.162-.48.162h-3.594c-.245 0-.435-.066-.572-.197a.621.621 0 0 1-.205-.463c0-.114.044-.265.132-.453a1.62 1.62 0 0 1 .288-.444c.433-.436.824-.81 1.172-1.122.348-.312.597-.517.747-.615.267-.183.49-.368.667-.553.177-.185.312-.375.405-.57.093-.194.139-.384.139-.57a1.008 1.008 0 0 0-.554-.917 1.197 1.197 0 0 0-.56-.133c-.426 0-.761.182-1.005.546a2.332 2.332 0 0 0-.164.39 1.609 1.609 0 0 1-.258.488c-.096.114-.237.17-.423.17a.558.558 0 0 1-.405-.156.568.568 0 0 1-.161-.427c0-.218.05-.446.151-.683.101-.238.252-.453.452-.646s.454-.349.762-.467a2.998 2.998 0 0 1 1.081-.178c.498 0 .923.076 1.274.228a1.916 1.916 0 0 1 1.004 1.032 1.984 1.984 0 0 1-.156 1.794c-.2.32-.405.572-.613.754-.208.182-.558.468-1.048.857-.49.39-.826.691-1.008.906a2.703 2.703 0 0 0-.24.309zM7.03 10.349l3.818-3.819a.8.8 0 1 1 1.132 1.132L8.16 11.48l3.819 3.818a.8.8 0 1 1-1.132 1.132L7.03 12.61l-3.818 3.82a.8.8 0 1 1-1.132-1.132L5.9 11.48 2.08 7.662A.8.8 0 1 1 3.212 6.53l3.818 3.82z"/></svg>',label:e("Superscript")});t.ui.componentFactory.add(R,()=>i(n)),t.ui.componentFactory.add("menuBar:"+R,()=>i(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class z extends e{static get requires(){return[K,U]}static get pluginName(){return"Superscript"}static get isOfficialPlugin(){return!0}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const V="underline";class q extends e{static get pluginName(){return"UnderlineEditing"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=this.editor.t;t.model.schema.extend("$text",{allowAttributes:V}),t.model.schema.setAttributeProperties(V,{isFormatting:!0,copyOnEnter:!0}),t.conversion.attributeToElement({model:V,view:"u",upcastAlso:{styles:{"text-decoration":"underline"}}}),t.commands.add(V,new c(t,V)),t.keystrokes.set("CTRL+U","underline"),t.accessibility.addKeystrokeInfos({keystrokes:[{label:e("Underline text"),keystroke:"CTRL+U"}]})}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
const M="underline";class $ extends e{static get pluginName(){return"UnderlineUI"}static get isOfficialPlugin(){return!0}init(){const t=this.editor,e=t.locale.t,i=d({editor:t,commandName:M,plugin:this,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M3 18v-1.5h14V18zm2.2-8V3.6c0-.4.4-.6.8-.6.3 0 .7.2.7.6v6.2c0 2 1.3 2.8 3.2 2.8 1.9 0 3.4-.9 3.4-2.9V3.6c0-.3.4-.5.8-.5.3 0 .7.2.7.5V10c0 2.7-2.2 4-4.9 4-2.6 0-4.7-1.2-4.7-4z"/></svg>',label:e("Underline"),keystroke:"CTRL+U"});t.ui.componentFactory.add(M,()=>i(n)),t.ui.componentFactory.add("menuBar:"+M,()=>i(s))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class H extends e{static get requires(){return[q,$]}static get pluginName(){return"Underline"}static get isOfficialPlugin(){return!0}}export{c as AttributeCommand,m as Bold,l as BoldEditing,g as BoldUI,y as Code,p as CodeEditing,f as CodeUI,A as Italic,w as ItalicEditing,v as ItalicUI,T as Strikethrough,O as StrikethroughEditing,P as StrikethroughUI,S as Subscript,I as SubscriptEditing,C as SubscriptUI,z as Superscript,K as SuperscriptEditing,U as SuperscriptUI,H as Underline,q as UnderlineEditing,$ as UnderlineUI};