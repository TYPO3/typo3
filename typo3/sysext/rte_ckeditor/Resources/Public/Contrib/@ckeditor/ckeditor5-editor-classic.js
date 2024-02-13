import{EditorUI as t,normalizeToolbarConfig as e,DialogView as i,BoxedEditorUIView as o,StickyPanelView as r,ToolbarView as n,InlineEditableUIView as c}from"@ckeditor/ckeditor5-ui";import{enablePlaceholder as s}from"@ckeditor/ckeditor5-engine";import{ElementReplacer as d,Rect as a,CKEditorError as l,getDataFromElement as h}from"@ckeditor/ckeditor5-utils";import{DataApiMixin as k,ElementApiMixin as u,Editor as p,attachToForm as m,Context as b}from"@ckeditor/ckeditor5-core";import{EditorWatchdog as _,ContextWatchdog as g}from"@ckeditor/ckeditor5-watchdog";import{isElement as f}from"lodash-es";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class y extends t{constructor(t,i){super(t),this.view=i,this._toolbarConfig=e(t.config.get("toolbar")),this._elementReplacer=new d,this.listenTo(t.editing.view,"scrollToTheSelection",this._handleScrollToTheSelectionWithStickyPanel.bind(this))}get element(){return this.view.element}init(t){const e=this.editor,i=this.view,o=e.editing.view,r=i.editable,n=o.document.getRoot();r.name=n.rootName,i.render();const c=r.element;this.setEditableElement(r.name,c),i.editable.bind("isFocused").to(this.focusTracker),o.attachDomRoot(c),t&&this._elementReplacer.replace(t,this.element),this._initPlaceholder(),this._initToolbar(),this._initDialogPluginIntegration(),this.fire("ready")}destroy(){super.destroy();const t=this.view,e=this.editor.editing.view;this._elementReplacer.restore(),e.detachDomRoot(t.editable.name),t.destroy()}_initToolbar(){const t=this.view;t.stickyPanel.bind("isActive").to(this.focusTracker,"isFocused"),t.stickyPanel.limiterElement=t.element,t.stickyPanel.bind("viewportTopOffset").to(this,"viewportOffset",(({top:t})=>t||0)),t.toolbar.fillFromConfig(this._toolbarConfig,this.componentFactory),this.addToolbar(t.toolbar)}_initPlaceholder(){const t=this.editor,e=t.editing.view,i=e.document.getRoot(),o=t.sourceElement;let r;const n=t.config.get("placeholder");n&&(r="string"==typeof n?n:n[this.view.editable.name]),!r&&o&&"textarea"===o.tagName.toLowerCase()&&(r=o.getAttribute("placeholder")),r&&(i.placeholder=r),s({view:e,element:i,isDirectHost:!1,keepOnFocus:!0})}_handleScrollToTheSelectionWithStickyPanel(t,e,i){const o=this.view.stickyPanel;if(o.isSticky){const t=new a(o.element).height;e.viewportOffset.top+=t}else{const t=()=>{this.editor.editing.view.scrollToTheSelection(i)};this.listenTo(o,"change:isSticky",t),setTimeout((()=>{this.stopListening(o,"change:isSticky",t)}),20)}}_initDialogPluginIntegration(){if(!this.editor.plugins.has("Dialog"))return;const t=this.view.stickyPanel,e=this.editor.plugins.get("Dialog");e.on("show",(()=>{const o=e.view;o.on("moveTo",((e,r)=>{if(!t.isSticky||o.wasMoved)return;const n=new a(t.contentPanelElement);r[1]<n.bottom+i.defaultOffset&&(r[1]=n.bottom+i.defaultOffset)}),{priority:"high"})}),{priority:"low"})}}!function(t,{insertAt:e}={}){if(!t||"undefined"==typeof document)return;const i=document.head||document.getElementsByTagName("head")[0],o=document.createElement("style");o.type="text/css",window.litNonce&&o.setAttribute("nonce",window.litNonce),"top"===e&&i.firstChild?i.insertBefore(o,i.firstChild):i.appendChild(o),o.styleSheet?o.styleSheet.cssText=t:o.appendChild(document.createTextNode(t))}(".ck.ck-editor{position:relative}.ck.ck-editor .ck-editor__top .ck-sticky-panel .ck-toolbar{z-index:var(--ck-z-panel)}.ck.ck-editor__top .ck-sticky-panel .ck-toolbar{border-radius:0}.ck-rounded-corners .ck.ck-editor__top .ck-sticky-panel .ck-toolbar,.ck.ck-editor__top .ck-sticky-panel .ck-toolbar.ck-rounded-corners{border-radius:var(--ck-border-radius);border-bottom-left-radius:0;border-bottom-right-radius:0}.ck.ck-editor__top .ck-sticky-panel .ck-toolbar{border-bottom-width:0}.ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content_sticky .ck-toolbar{border-bottom-width:1px;border-radius:0}.ck-rounded-corners .ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content_sticky .ck-toolbar,.ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content_sticky .ck-toolbar.ck-rounded-corners{border-radius:var(--ck-border-radius);border-radius:0}.ck.ck-editor__main>.ck-editor__editable{background:var(--ck-color-base-background);border-radius:0}.ck-rounded-corners .ck.ck-editor__main>.ck-editor__editable,.ck.ck-editor__main>.ck-editor__editable.ck-rounded-corners{border-radius:var(--ck-border-radius);border-top-left-radius:0;border-top-right-radius:0}.ck.ck-editor__main>.ck-editor__editable:not(.ck-focused){border-color:var(--ck-color-base-border)}");
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
class w extends o{constructor(t,e,i={}){super(t),this.stickyPanel=new r(t),this.toolbar=new n(t,{shouldGroupWhenFull:i.shouldToolbarGroupWhenFull}),this.editable=new c(t,e)}render(){super.render(),this.stickyPanel.content.add(this.toolbar),this.top.add(this.stickyPanel),this.main.add(this.editable)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class v extends(k(u(p))){constructor(t,e={}){if(!T(t)&&void 0!==e.initialData)throw new l("editor-create-initial-data",null);super(e),void 0===this.config.get("initialData")&&this.config.set("initialData",function(t){return T(t)?h(t):t}(t)),T(t)&&(this.sourceElement=t),this.model.document.createRoot();const i=!this.config.get("toolbar.shouldNotGroupWhenFull"),o=new w(this.locale,this.editing.view,{shouldToolbarGroupWhenFull:i});this.ui=new y(this,o),m(this)}destroy(){return this.sourceElement&&this.updateSourceElement(),this.ui.destroy(),super.destroy()}static create(t,e={}){return new Promise((i=>{const o=new this(t,e);i(o.initPlugins().then((()=>o.ui.init(T(t)?t:null))).then((()=>o.data.init(o.config.get("initialData")))).then((()=>o.fire("ready"))).then((()=>o)))}))}}function T(t){return f(t)}v.Context=b,v.EditorWatchdog=_,v.ContextWatchdog=g;export{v as ClassicEditor};