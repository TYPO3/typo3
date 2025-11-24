import{Command as e,Plugin as t}from"@ckeditor/ckeditor5-core";import{Observer as n,BubblingEventInfo as r,DomEventData as i}from"@ckeditor/ckeditor5-engine";import{env as s}from"@ckeditor/ckeditor5-utils";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function*o(e,t){for(const n of t)n&&e.getAttributeProperties(n[0]).copyOnEnter&&(yield n)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class c extends e{execute(){this.editor.model.change(e=>{this.enterBlock(e),this.fire("afterExecute",{writer:e})})}enterBlock(e){const t=this.editor.model,n=t.document.selection,r=t.schema,i=n.isCollapsed,s=n.getFirstRange(),c=s.start.parent,l=s.end.parent;if(r.isLimit(c)||r.isLimit(l))return i||c!=l||t.deleteContent(n),!1;if(i){const t=o(e.model.schema,n.getAttributes());return a(e,s.start),e.setSelectionAttribute(t),!0}{const r=!(s.start.isAtStart&&s.end.isAtEnd),i=c==l;if(t.deleteContent(n,{leaveUnmerged:r}),r){if(i)return a(e,n.focus),!0;e.setSelection(l,0)}}return!1}}function a(e,t){e.split(t),e.setSelection(t.parent.nextSibling,0)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const l={insertParagraph:{isSoft:!1},insertLineBreak:{isSoft:!0}};class d extends n{constructor(e){super(e);const t=this.document;let n=!1;t.on("keydown",(e,t)=>{n=t.shiftKey}),t.on("beforeinput",(o,c)=>{if(!this.isEnabled)return;let a=c.inputType;s.isSafari&&n&&"insertParagraph"==a&&(a="insertLineBreak");const d=c.domEvent,f=l[a];if(!f)return;const u=new r(t,"enter",c.targetRanges[0]);t.fire(u,new i(e,d,{isSoft:f.isSoft})),u.stop.called&&o.stop()})}observe(){}stopObserving(){}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class f extends t{static get pluginName(){return"Enter"}static get isOfficialPlugin(){return!0}init(){const e=this.editor,t=e.editing.view,n=t.document,r=this.editor.t;t.addObserver(d),e.commands.add("enter",new c(e)),this.listenTo(n,"enter",(r,i)=>{n.isComposing||i.preventDefault(),i.isSoft||(e.execute("enter"),t.scrollToTheSelection())},{priority:"low"}),e.accessibility.addKeystrokeInfos({keystrokes:[{label:r("Insert a hard break (a new paragraph)"),keystroke:"Enter"}]})}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class u extends e{execute(){const e=this.editor.model,t=e.document;e.change(n=>{!function(e,t,n){const r=n.isCollapsed,i=n.getFirstRange(),s=i.start.parent,c=i.end.parent,a=s==c;if(r){const r=o(e.schema,n.getAttributes());m(e,t,i.end),t.removeSelectionAttribute(n.getAttributeKeys()),t.setSelectionAttribute(r)}else{const r=!(i.start.isAtStart&&i.end.isAtEnd);e.deleteContent(n,{leaveUnmerged:r}),a?m(e,t,n.focus):r&&t.setSelection(c,0)}}(e,n,t.selection),this.fire("afterExecute",{writer:n})})}refresh(){const e=this.editor.model,t=e.document;this.isEnabled=function(e,t){if(t.rangeCount>1)return!1;const n=t.anchor;if(!n||!e.checkChild(n,"softBreak"))return!1;const r=t.getFirstRange(),i=r.start.parent,s=r.end.parent;if((h(i,e)||h(s,e))&&i!==s)return!1;return!0}(e.schema,t.selection)}}function m(e,t,n){const r=t.createElement("softBreak");e.insertContent(r,n),t.setSelection(r,"after")}function h(e,t){return!e.is("rootElement")&&(t.isLimit(e)||h(e.parent,t))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class p extends t{static get pluginName(){return"ShiftEnter"}static get isOfficialPlugin(){return!0}init(){const e=this.editor,t=e.model.schema,n=e.conversion,r=e.editing.view,i=r.document,s=this.editor.t;t.register("softBreak",{allowWhere:"$text",isInline:!0}),n.for("upcast").elementToElement({model:"softBreak",view:"br"}),n.for("downcast").elementToElement({model:"softBreak",view:(e,{writer:t})=>t.createEmptyElement("br")}),r.addObserver(d),e.commands.add("shiftEnter",new u(e)),this.listenTo(i,"enter",(t,n)=>{i.isComposing||n.preventDefault(),n.isSoft&&(e.execute("shiftEnter"),r.scrollToTheSelection())},{priority:"low"}),e.accessibility.addKeystrokeInfos({keystrokes:[{label:s("Insert a soft break (a <code>&lt;br&gt;</code> element)"),keystroke:"Shift+Enter"}]})}}export{f as Enter,p as ShiftEnter};