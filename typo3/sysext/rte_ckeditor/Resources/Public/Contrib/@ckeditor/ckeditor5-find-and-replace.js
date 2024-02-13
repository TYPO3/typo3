import{icons as e,Plugin as t,Command as i}from"@ckeditor/ckeditor5-core";import{View as n,ViewCollection as s,FocusCycler as r,submitHandler as a,CollapsibleView as c,SwitchButtonView as o,ButtonView as l,LabeledFieldView as d,createLabeledInputText as h,Dialog as f,DropdownView as u,createDropdown as p,FormHeaderView as k,DialogViewPosition as m,CssTransitionDisablerMixin as _}from"@ckeditor/ckeditor5-ui";import{FocusTracker as g,KeystrokeHandler as w,isVisible as x,Rect as b,Collection as v,ObservableMixin as y,uid as V,scrollViewportToShowTarget as R}from"@ckeditor/ckeditor5-utils";import{escapeRegExp as T,debounce as C}from"lodash-es";function F(e,{insertAt:t}={}){if(!e||"undefined"==typeof document)return;const i=document.head||document.getElementsByTagName("head")[0],n=document.createElement("style");n.type="text/css",window.litNonce&&n.setAttribute("nonce",window.litNonce),"top"===t&&i.firstChild?i.insertBefore(n,i.firstChild):i.appendChild(n),n.styleSheet?n.styleSheet.cssText=e:n.appendChild(document.createTextNode(e))}F('.ck-vertical-form .ck-button:after{bottom:-1px;content:"";position:absolute;right:-1px;top:-1px;width:0;z-index:1}.ck-vertical-form .ck-button:focus:after{display:none}@media screen and (max-width:600px){.ck.ck-responsive-form .ck-button:after{bottom:-1px;content:"";position:absolute;right:-1px;top:-1px;width:0;z-index:1}.ck.ck-responsive-form .ck-button:focus:after{display:none}}.ck-vertical-form>.ck-button:nth-last-child(2):after{border-right:1px solid var(--ck-color-base-border)}.ck.ck-responsive-form{padding:var(--ck-spacing-large)}.ck.ck-responsive-form:focus{outline:none}[dir=ltr] .ck.ck-responsive-form>:not(:first-child),[dir=rtl] .ck.ck-responsive-form>:not(:last-child){margin-left:var(--ck-spacing-standard)}@media screen and (max-width:600px){.ck.ck-responsive-form{padding:0;width:calc(var(--ck-input-width)*.8)}.ck.ck-responsive-form .ck-labeled-field-view{margin:var(--ck-spacing-large) var(--ck-spacing-large) 0}.ck.ck-responsive-form .ck-labeled-field-view .ck-input-text{min-width:0;width:100%}.ck.ck-responsive-form .ck-labeled-field-view .ck-labeled-field-view__error{white-space:normal}.ck.ck-responsive-form>.ck-button:nth-last-child(2):after{border-right:1px solid var(--ck-color-base-border)}.ck.ck-responsive-form>.ck-button:last-child,.ck.ck-responsive-form>.ck-button:nth-last-child(2){border-radius:0;margin-top:var(--ck-spacing-large);padding:var(--ck-spacing-standard)}.ck.ck-responsive-form>.ck-button:last-child:not(:focus),.ck.ck-responsive-form>.ck-button:nth-last-child(2):not(:focus){border-top:1px solid var(--ck-color-base-border)}[dir=ltr] .ck.ck-responsive-form>.ck-button:last-child,[dir=ltr] .ck.ck-responsive-form>.ck-button:nth-last-child(2),[dir=rtl] .ck.ck-responsive-form>.ck-button:last-child,[dir=rtl] .ck.ck-responsive-form>.ck-button:nth-last-child(2){margin-left:0}[dir=rtl] .ck.ck-responsive-form>.ck-button:last-child:last-of-type,[dir=rtl] .ck.ck-responsive-form>.ck-button:nth-last-child(2):last-of-type{border-right:1px solid var(--ck-color-base-border)}}');F(".ck.ck-find-and-replace-form{max-width:100%;& .ck-find-and-replace-form__inputs,.ck-find-and-replace-form__actions{display:flex}& .ck-find-and-replace-form__inputs.ck-find-and-replace-form__inputs .ck-results-counter,.ck-find-and-replace-form__actions.ck-find-and-replace-form__inputs .ck-results-counter{position:absolute}}.ck.ck-find-and-replace-form{width:400px}.ck.ck-find-and-replace-form:focus{outline:none}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions,.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs{align-content:stretch;align-items:center;flex:1 1 auto;flex-direction:row;flex-wrap:wrap;margin:0;padding:var(--ck-spacing-large)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions>.ck-button,.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>.ck-button{flex:0 0 auto}[dir=ltr] .ck.ck-find-and-replace-form .ck-find-and-replace-form__actions>*+*,[dir=ltr] .ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>*+*{margin-left:var(--ck-spacing-standard)}[dir=rtl] .ck.ck-find-and-replace-form .ck-find-and-replace-form__actions>*+*,[dir=rtl] .ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>*+*{margin-right:var(--ck-spacing-standard)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions .ck-labeled-field-view,.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs .ck-labeled-field-view{flex:1 1 auto}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions .ck-labeled-field-view .ck-input,.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs .ck-labeled-field-view .ck-input{min-width:50px;width:100%}.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs{align-items:flex-start}.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>.ck-button-prev>.ck-icon{transform:rotate(90deg)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>.ck-button-next>.ck-icon{transform:rotate(-90deg)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs .ck-results-counter{top:50%;transform:translateY(-50%)}[dir=ltr] .ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs .ck-results-counter{right:var(--ck-spacing-standard)}[dir=rtl] .ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs .ck-results-counter{left:var(--ck-spacing-standard)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs .ck-results-counter{color:var(--ck-color-base-border)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>.ck-labeled-field-replace{flex:0 0 100%;padding-top:var(--ck-spacing-standard)}[dir=ltr] .ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>.ck-labeled-field-replace{margin-left:0}[dir=rtl] .ck.ck-find-and-replace-form .ck-find-and-replace-form__inputs>.ck-labeled-field-replace{margin-right:0}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions{flex-wrap:wrap;justify-content:flex-end;margin-top:calc(var(--ck-spacing-large)*-1)}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions>.ck-button-find{font-weight:700}.ck.ck-find-and-replace-form .ck-find-and-replace-form__actions>.ck-button-find .ck-button__label{padding-left:var(--ck-spacing-large);padding-right:var(--ck-spacing-large)}.ck.ck-find-and-replace-form .ck-switchbutton{align-items:center;display:flex;flex-direction:row;flex-wrap:nowrap;justify-content:space-between;width:100%}@media screen and (max-width:600px){.ck.ck-find-and-replace-form{max-width:100%;width:300px}.ck.ck-find-and-replace-form.ck-find-and-replace-form__input{flex-wrap:wrap}.ck.ck-find-and-replace-form.ck-find-and-replace-form__input .ck-labeled-field-view{flex:1 0 auto;margin-bottom:var(--ck-spacing-standard);width:100%}.ck.ck-find-and-replace-form.ck-find-and-replace-form__input>.ck-button{text-align:center}.ck.ck-find-and-replace-form.ck-find-and-replace-form__input>.ck-button:first-of-type{flex:1 1 auto}[dir=ltr] .ck.ck-find-and-replace-form.ck-find-and-replace-form__input>.ck-button:first-of-type{margin-left:0}[dir=rtl] .ck.ck-find-and-replace-form.ck-find-and-replace-form__input>.ck-button:first-of-type{margin-right:0}.ck.ck-find-and-replace-form.ck-find-and-replace-form__input>.ck-button:first-of-type .ck-button__label{text-align:center;width:100%}.ck.ck-find-and-replace-form.ck-find-and-replace-form__actions>:not(.ck-labeled-field-view){flex:1 1 auto;flex-wrap:wrap}.ck.ck-find-and-replace-form.ck-find-and-replace-form__actions>:not(.ck-labeled-field-view)>.ck-button{text-align:center}.ck.ck-find-and-replace-form.ck-find-and-replace-form__actions>:not(.ck-labeled-field-view)>.ck-button:first-of-type{flex:1 1 auto}[dir=ltr] .ck.ck-find-and-replace-form.ck-find-and-replace-form__actions>:not(.ck-labeled-field-view)>.ck-button:first-of-type{margin-left:0}[dir=rtl] .ck.ck-find-and-replace-form.ck-find-and-replace-form__actions>:not(.ck-labeled-field-view)>.ck-button:first-of-type{margin-right:0}.ck.ck-find-and-replace-form.ck-find-and-replace-form__actions>:not(.ck-labeled-field-view)>.ck-button .ck-button__label{text-align:center;width:100%}}");
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
class B extends n{constructor(t){super(t);const i=t.t;this.children=this.createCollection(),this.set("matchCount",0),this.set("highlightOffset",0),this.set("isDirty",!1),this.set("_areCommandsEnabled",{}),this.set("_resultsCounterText",""),this.set("_matchCase",!1),this.set("_wholeWordsOnly",!1),this.bind("_searchResultsFound").to(this,"matchCount",this,"isDirty",((e,t)=>e>0&&!t)),this._findInputView=this._createInputField(i("Find in text…")),this._findPrevButtonView=this._createButton({label:i("Previous result"),class:"ck-button-prev",icon:e.previousArrow,keystroke:"Shift+F3",tooltip:!0}),this._findNextButtonView=this._createButton({label:i("Next result"),class:"ck-button-next",icon:e.previousArrow,keystroke:"F3",tooltip:!0}),this._replaceInputView=this._createInputField(i("Replace with…"),"ck-labeled-field-replace"),this._inputsDivView=this._createInputsDiv(),this._matchCaseSwitchView=this._createMatchCaseSwitch(),this._wholeWordsOnlySwitchView=this._createWholeWordsOnlySwitch(),this._advancedOptionsCollapsibleView=this._createAdvancedOptionsCollapsible(),this._replaceAllButtonView=this._createButton({label:i("Replace all"),class:"ck-button-replaceall",withText:!0}),this._replaceButtonView=this._createButton({label:i("Replace"),class:"ck-button-replace",withText:!0}),this._findButtonView=this._createButton({label:i("Find"),class:"ck-button-find ck-button-action",withText:!0}),this._actionButtonsDivView=this._createActionButtonsDiv(),this._focusTracker=new g,this._keystrokes=new w,this._focusables=new s,this.focusCycler=new r({focusables:this._focusables,focusTracker:this._focusTracker,keystrokeHandler:this._keystrokes,actions:{focusPrevious:"shift + tab",focusNext:"tab"}}),this.children.addMany([this._inputsDivView,this._advancedOptionsCollapsibleView,this._actionButtonsDivView]),this.setTemplate({tag:"form",attributes:{class:["ck","ck-find-and-replace-form"],tabindex:"-1"},children:this.children})}render(){super.render(),a({view:this}),this._initFocusCycling(),this._initKeystrokeHandling()}destroy(){super.destroy(),this._focusTracker.destroy(),this._keystrokes.destroy()}focus(e){-1===e?this.focusCycler.focusLast():this.focusCycler.focusFirst()}reset(){this._findInputView.errorText=null,this.isDirty=!0}get _textToFind(){return this._findInputView.fieldView.element.value}get _textToReplace(){return this._replaceInputView.fieldView.element.value}_createInputsDiv(){const e=this.locale,t=e.t,i=new n(e);return this._findInputView.fieldView.on("input",(()=>{this.isDirty=!0})),this._findPrevButtonView.delegate("execute").to(this,"findPrevious"),this._findNextButtonView.delegate("execute").to(this,"findNext"),this._findPrevButtonView.bind("isEnabled").to(this,"_areCommandsEnabled",(({findPrevious:e})=>e)),this._findNextButtonView.bind("isEnabled").to(this,"_areCommandsEnabled",(({findNext:e})=>e)),this._injectFindResultsCounter(),this._replaceInputView.bind("isEnabled").to(this,"_areCommandsEnabled",this,"_searchResultsFound",(({replace:e},t)=>e&&t)),this._replaceInputView.bind("infoText").to(this._replaceInputView,"isEnabled",this._replaceInputView,"isFocused",((e,i)=>e||!i?"":t("Tip: Find some text first in order to replace it."))),i.setTemplate({tag:"div",attributes:{class:["ck","ck-find-and-replace-form__inputs"]},children:[this._findInputView,this._findPrevButtonView,this._findNextButtonView,this._replaceInputView]}),i}_onFindButtonExecute(){if(this._textToFind)this.isDirty=!1,this.fire("findNext",{searchText:this._textToFind,matchCase:this._matchCase,wholeWords:this._wholeWordsOnly});else{const e=this.t;this._findInputView.errorText=e("Text to find must not be empty.")}}_injectFindResultsCounter(){const e=this.locale,t=e.t,i=this.bindTemplate,s=new n(this.locale);this.bind("_resultsCounterText").to(this,"highlightOffset",this,"matchCount",((e,i)=>t("%0 of %1",[e,i]))),s.setTemplate({tag:"span",attributes:{class:["ck","ck-results-counter",i.if("isDirty","ck-hidden")]},children:[{text:i.to("_resultsCounterText")}]});const r=()=>{const t=this._findInputView.fieldView.element;if(!t||!x(t))return;const i=new b(s.element).width,n="ltr"===e.uiLanguageDirection?"paddingRight":"paddingLeft";t.style[n]=i?`calc( 2 * var(--ck-spacing-standard) + ${i}px )`:""};this.on("change:_resultsCounterText",r,{priority:"low"}),this.on("change:isDirty",r,{priority:"low"}),this._findInputView.template.children[0].children.push(s)}_createAdvancedOptionsCollapsible(){const e=this.locale.t,t=new c(this.locale,[this._matchCaseSwitchView,this._wholeWordsOnlySwitchView]);return t.set({label:e("Advanced options"),isCollapsed:!0}),t}_createActionButtonsDiv(){const e=new n(this.locale);return this._replaceButtonView.bind("isEnabled").to(this,"_areCommandsEnabled",this,"_searchResultsFound",(({replace:e},t)=>e&&t)),this._replaceAllButtonView.bind("isEnabled").to(this,"_areCommandsEnabled",this,"_searchResultsFound",(({replaceAll:e},t)=>e&&t)),this._replaceButtonView.on("execute",(()=>{this.fire("replace",{searchText:this._textToFind,replaceText:this._textToReplace})})),this._replaceAllButtonView.on("execute",(()=>{this.fire("replaceAll",{searchText:this._textToFind,replaceText:this._textToReplace}),this.focus()})),this._findButtonView.on("execute",this._onFindButtonExecute.bind(this)),e.setTemplate({tag:"div",attributes:{class:["ck","ck-find-and-replace-form__actions"]},children:[this._replaceAllButtonView,this._replaceButtonView,this._findButtonView]}),e}_createMatchCaseSwitch(){const e=this.locale.t,t=new o(this.locale);return t.set({label:e("Match case"),withText:!0}),t.bind("isOn").to(this,"_matchCase"),t.on("execute",(()=>{this._matchCase=!this._matchCase,this.isDirty=!0})),t}_createWholeWordsOnlySwitch(){const e=this.locale.t,t=new o(this.locale);return t.set({label:e("Whole words only"),withText:!0}),t.bind("isOn").to(this,"_wholeWordsOnly"),t.on("execute",(()=>{this._wholeWordsOnly=!this._wholeWordsOnly,this.isDirty=!0})),t}_initFocusCycling(){[this._findInputView,this._findPrevButtonView,this._findNextButtonView,this._replaceInputView,this._advancedOptionsCollapsibleView.buttonView,this._matchCaseSwitchView,this._wholeWordsOnlySwitchView,this._replaceAllButtonView,this._replaceButtonView,this._findButtonView].forEach((e=>{this._focusables.add(e),this._focusTracker.add(e.element)}))}_initKeystrokeHandling(){const e=e=>e.stopPropagation(),t=e=>{e.stopPropagation(),e.preventDefault()};this._keystrokes.listenTo(this.element),this._keystrokes.set("f3",(e=>{t(e),this._findNextButtonView.fire("execute")})),this._keystrokes.set("shift+f3",(e=>{t(e),this._findPrevButtonView.fire("execute")})),this._keystrokes.set("enter",(e=>{const i=e.target;i===this._findInputView.fieldView.element?(this._areCommandsEnabled.findNext?this._findNextButtonView.fire("execute"):this._findButtonView.fire("execute"),t(e)):i!==this._replaceInputView.fieldView.element||this.isDirty||(this._replaceButtonView.fire("execute"),t(e))})),this._keystrokes.set("shift+enter",(e=>{e.target===this._findInputView.fieldView.element&&(this._areCommandsEnabled.findPrevious?this._findPrevButtonView.fire("execute"):this._findButtonView.fire("execute"),t(e))})),this._keystrokes.set("arrowright",e),this._keystrokes.set("arrowleft",e),this._keystrokes.set("arrowup",e),this._keystrokes.set("arrowdown",e)}_createButton(e){const t=new l(this.locale);return t.set(e),t}_createInputField(e,t){const i=new d(this.locale,h);return i.label=e,i.class=t,i}}var E='<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m12.87 13.786 1.532-1.286 3.857 4.596a1 1 0 1 1-1.532 1.286l-3.857-4.596z"/><path d="M16.004 8.5a6.5 6.5 0 0 1-9.216 5.905c-1.154-.53-.863-1.415-.663-1.615.194-.194.564-.592 1.635-.141a4.5 4.5 0 0 0 5.89-5.904l-.104-.227 1.332-1.331c.045-.046.196-.041.224.007a6.47 6.47 0 0 1 .902 3.306zm-3.4-5.715c.562.305.742 1.106.354 1.494-.388.388-.995.414-1.476.178a4.5 4.5 0 0 0-6.086 5.882l.114.236-1.348 1.349c-.038.037-.17.022-.198-.023a6.5 6.5 0 0 1 5.54-9.9 6.469 6.469 0 0 1 3.1.784z"/><path d="M4.001 11.93.948 8.877a.2.2 0 0 1 .141-.341h6.106a.2.2 0 0 1 .141.341L4.283 11.93a.2.2 0 0 1-.282 0zm11.083-6.789 3.053 3.053a.2.2 0 0 1-.14.342H11.89a.2.2 0 0 1-.14-.342l3.052-3.053a.2.2 0 0 1 .282 0z"/></svg>';
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class A extends t{static get requires(){return[f]}static get pluginName(){return"FindAndReplaceUI"}constructor(e){super(e),e.config.define("findAndReplace.uiType","dialog"),this.formView=null}init(){const e=this.editor,t="dropdown"===e.config.get("findAndReplace.uiType"),i=e.commands.get("find");e.ui.componentFactory.add("findAndReplace",(()=>{let n;return t?(n=this._createDropdown(),n.bind("isEnabled").to(i)):(n=this._createDialogButton(),n.bind("isEnabled").to(i)),e.keystrokes.set("Ctrl+F",((t,s)=>{if(i.isEnabled){if(n instanceof u){const e=n.buttonView;e.isOn||e.fire("execute")}else n.isOn?e.plugins.get("Dialog").view.focus():n.fire("execute");s()}})),n}))}_createDropdown(){const e=this.editor,t=e.locale.t,i=p(e.locale);return i.once("change:isOpen",(()=>{this.formView=this._createFormView(),this.formView.children.add(new k(e.locale,{label:t("Find and replace")}),0),i.panelView.children.add(this.formView)})),i.on("change:isOpen",((e,t,i)=>{i?this._setupFormView():this.fire("searchReseted")}),{priority:"low"}),i.buttonView.set({icon:E,label:t("Find and replace"),keystroke:"CTRL+F",tooltip:!0}),i}_createDialogButton(){const e=this.editor,t=new l(e.locale),i=e.plugins.get("Dialog"),n=e.locale.t;return t.set({icon:E,label:n("Find and replace"),keystroke:"CTRL+F",tooltip:!0}),t.bind("isOn").to(i,"id",(e=>"findAndReplace"===e)),t.on("execute",(()=>{this.formView||(this.formView=this._createFormView()),t.isOn?i.hide():i.show({id:"findAndReplace",title:n("Find and replace"),content:this.formView,position:m.EDITOR_TOP_SIDE,onShow:()=>{this._setupFormView()},onHide:()=>{this.fire("searchReseted")}})})),t}_createFormView(){const e=this.editor,t=new(_(B))(e.locale),i=e.commands,n=this.editor.plugins.get("FindAndReplaceEditing").state,s={before:-1,same:0,after:1,different:1};t.bind("highlightOffset").to(n,"highlightedResult",(e=>e?Array.from(n.results).sort(((e,t)=>s[e.marker.getStart().compareWith(t.marker.getStart())])).indexOf(e)+1:0)),t.listenTo(n.results,"change",(()=>{t.matchCount=n.results.length}));const r=i.get("findNext"),a=i.get("findPrevious"),c=i.get("replace"),o=i.get("replaceAll");return t.bind("_areCommandsEnabled").to(r,"isEnabled",a,"isEnabled",c,"isEnabled",o,"isEnabled",((e,t,i,n)=>({findNext:e,findPrevious:t,replace:i,replaceAll:n}))),t.delegate("findNext","findPrevious","replace","replaceAll").to(this),t.on("change:isDirty",((e,t,i)=>{i&&this.fire("searchReseted")})),t}_setupFormView(){this.formView.disableCssTransitions(),this.formView.reset(),this.formView._findInputView.fieldView.select(),this.formView.enableCssTransitions()}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class I extends i{constructor(e,t){super(e),this.isEnabled=!0,this.affectsData=!1,this._state=t}execute(e,{matchCase:t,wholeWords:i}={}){const{editor:n}=this,{model:s}=n,r=n.plugins.get("FindAndReplaceUtils");let a;"string"==typeof e?(a=r.findByTextCallback(e,{matchCase:t,wholeWords:i}),this._state.searchText=e):a=e;const c=s.document.getRootNames().reduce(((e,t)=>r.updateFindResultFromRange(s.createRangeIn(s.document.getRoot(t)),s,a,e)),null);return this._state.clear(s),this._state.results.addMany(c),this._state.highlightedResult=c.get(0),"string"==typeof e&&(this._state.searchText=e),this._state.matchCase=!!t,this._state.matchWholeWords=!!i,{results:c,findCallback:a}}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class D extends i{constructor(e,t){super(e),this.isEnabled=!0,this._state=t,this._isEnabledBasedOnSelection=!1}_replace(e,t){const{model:i}=this.editor,n=t.marker.getRange();i.canEditAt(n)&&i.change((s=>{if("$graveyard"===n.root.rootName)return void this._state.results.remove(t);let r={};for(const e of n.getItems())if(e.is("$text")||e.is("$textProxy")){r=e.getAttributes();break}i.insertContent(s.createText(e,r),n),this._state.results.has(t)&&this._state.results.remove(t)}))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class O extends D{execute(e,t){this._replace(e,t)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class N extends D{execute(e,t){const{editor:i}=this,{model:n}=i,s=i.plugins.get("FindAndReplaceUtils"),r=t instanceof v?t:n.document.getRootNames().reduce(((e,i)=>s.updateFindResultFromRange(n.createRangeIn(n.document.getRoot(i)),n,s.findByTextCallback(t,this._state),e)),null);r.length&&n.change((()=>{[...r].forEach((t=>{this._replace(e,t)}))}))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class P extends i{constructor(e,t){super(e),this.affectsData=!1,this._state=t,this.isEnabled=!1,this.listenTo(this._state.results,"change",(()=>{this.isEnabled=this._state.results.length>1}))}refresh(){this.isEnabled=this._state.results.length>1}execute(){const e=this._state.results,t=e.getIndex(this._state.highlightedResult),i=t+1>=e.length?0:t+1;this._state.highlightedResult=this._state.results.get(i)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class S extends P{execute(){const e=this._state.results.getIndex(this._state.highlightedResult),t=e-1<0?this._state.results.length-1:e-1;this._state.highlightedResult=this._state.results.get(t)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class W extends(y()){constructor(e){super(),this.set("results",new v),this.set("highlightedResult",null),this.set("searchText",""),this.set("replaceText",""),this.set("matchCase",!1),this.set("matchWholeWords",!1),this.results.on("change",((t,{removed:i,index:n})=>{if(Array.from(i).length){let t=!1;if(e.change((n=>{for(const s of i)this.highlightedResult===s&&(t=!0),e.markers.has(s.marker.name)&&n.removeMarker(s.marker)})),t){const e=n>=this.results.length?0:n;this.highlightedResult=this.results.get(e)}}}))}clear(e){this.searchText="",e.change((t=>{if(this.highlightedResult){const i=this.highlightedResult.marker.name.split(":")[1],n=e.markers.get(`findResultHighlighted:${i}`);n&&t.removeMarker(n)}[...this.results].forEach((({marker:e})=>{t.removeMarker(e)}))})),this.results.clear()}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class $ extends t{static get pluginName(){return"FindAndReplaceUtils"}updateFindResultFromRange(e,t,i,n){const s=n||new v;return t.change((n=>{[...e].forEach((({type:e,item:r})=>{if("elementStart"===e&&t.schema.checkChild(r,"$text")){const e=i({item:r,text:this.rangeToText(t.createRangeIn(r))});if(!e)return;e.forEach((e=>{const t=`findResult:${V()}`,i=n.addMarker(t,{usingOperation:!1,affectsData:!1,range:n.createRange(n.createPositionAt(r,e.start),n.createPositionAt(r,e.end))}),a=function(e,t){const i=e.find((({marker:e})=>t.getStart().isBefore(e.getStart())));return i?e.getIndex(i):e.length}(s,i);s.add({id:t,label:e.label,marker:i},a)}))}}))})),s}rangeToText(e){return Array.from(e.getItems()).reduce(((e,t)=>t.is("$text")||t.is("$textProxy")?e+t.data:`${e}\n`),"")}findByTextCallback(e,t){let i="gu";t.matchCase||(i+="i");let n=`(${T(e)})`;if(t.wholeWords){const t="[^a-zA-ZÀ-ɏḀ-ỿ]";new RegExp("^"+t).test(e)||(n=`(^|${t}|_)${n}`),new RegExp(t+"$").test(e)||(n=`${n}(?=_|${t}|$)`)}const s=new RegExp(n,i);return function({text:e}){return[...e.matchAll(s)].map(M)}}}function M(e){const t=e.length-1;let i=e.index;return 3===e.length&&(i+=e[1].length),{label:e[t],start:i,end:i+e[t].length}}F(".ck-find-result{background:var(--ck-color-highlight-background);color:var(--ck-color-text)}.ck-find-result_selected{background:#ff9633}");class H extends t{static get requires(){return[$]}static get pluginName(){return"FindAndReplaceEditing"}init(){this._activeResults=null,this.state=new W(this.editor.model),this._defineConverters(),this._defineCommands(),this.listenTo(this.state,"change:highlightedResult",((e,t,i,n)=>{const{model:s}=this.editor;s.change((e=>{if(n){const t=n.marker.name.split(":")[1],i=s.markers.get(`findResultHighlighted:${t}`);i&&e.removeMarker(i)}if(i){const t=i.marker.name.split(":")[1];e.addMarker(`findResultHighlighted:${t}`,{usingOperation:!1,affectsData:!1,range:i.marker.getRange()})}}))}));
/* istanbul ignore next -- @preserve */
const e=C(((e,t,i)=>{if(i){const e=this.editor.editing.view.domConverter,t=this.editor.editing.mapper.toViewRange(i.marker.getRange());R({target:e.viewRangeToDom(t),viewportOffset:40})}}).bind(this),32);this.listenTo(this.state,"change:highlightedResult",e,{priority:"low"}),this.listenTo(this.editor,"destroy",e.cancel)}find(e){const{editor:t}=this,{model:i}=t,{findCallback:n,results:s}=t.execute("find",e);return this._activeResults=s,this.listenTo(i.document,"change:data",(()=>function(e,t,i){const n=new Set,s=new Set,r=t.model;r.document.differ.getChanges().forEach((e=>{"$text"===e.name||r.schema.isInline(e.position.nodeAfter)?(n.add(e.position.parent),[...r.markers.getMarkersAtPosition(e.position)].forEach((e=>{s.add(e.name)}))):"insert"===e.type&&n.add(e.position.nodeAfter)})),r.document.differ.getChangedMarkers().forEach((({name:e,data:{newRange:t}})=>{t&&"$graveyard"===t.start.root.rootName&&s.add(e)})),n.forEach((e=>{[...r.markers.getMarkersIntersectingRange(r.createRangeIn(e))].forEach((e=>s.add(e.name)))})),r.change((t=>{s.forEach((i=>{e.has(i)&&e.remove(i),t.removeMarker(i)}))})),n.forEach((n=>{t.plugins.get("FindAndReplaceUtils").updateFindResultFromRange(r.createRangeOn(n),r,i,e)}))}(this._activeResults,t,n))),this._activeResults}stop(){this._activeResults&&(this.stopListening(this.editor.model.document),this.state.clear(this.editor.model),this._activeResults=null)}_defineCommands(){this.editor.commands.add("find",new I(this.editor,this.state)),this.editor.commands.add("findNext",new P(this.editor,this.state)),this.editor.commands.add("findPrevious",new S(this.editor,this.state)),this.editor.commands.add("replace",new O(this.editor,this.state)),this.editor.commands.add("replaceAll",new N(this.editor,this.state))}_defineConverters(){const{editor:e}=this;e.conversion.for("editingDowncast").markerToHighlight({model:"findResult",view:({markerName:e})=>{const[,t]=e.split(":");return{name:"span",classes:["ck-find-result"],attributes:{"data-find-result":t}}}}),e.conversion.for("editingDowncast").markerToHighlight({model:"findResultHighlighted",view:({markerName:e})=>{const[,t]=e.split(":");return{name:"span",classes:["ck-find-result_selected"],attributes:{"data-find-result":t}}}})}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */class z extends t{static get requires(){return[H,A]}static get pluginName(){return"FindAndReplace"}init(){const e=this.editor.plugins.get("FindAndReplaceUI"),t=this.editor.plugins.get("FindAndReplaceEditing"),i=t.state;e.on("findNext",((e,t)=>{t?(i.searchText=t.searchText,this.editor.execute("find",t.searchText,t)):this.editor.execute("findNext")})),e.on("findPrevious",((e,t)=>{t&&i.searchText!==t.searchText?this.editor.execute("find",t.searchText):this.editor.execute("findPrevious")})),e.on("replace",((e,t)=>{i.searchText!==t.searchText&&this.editor.execute("find",t.searchText);const n=i.highlightedResult;n&&this.editor.execute("replace",t.replaceText,n)})),e.on("replaceAll",((e,t)=>{i.searchText!==t.searchText&&this.editor.execute("find",t.searchText),this.editor.execute("replaceAll",t.replaceText,i.results)})),e.on("searchReseted",(()=>{i.clear(this.editor.model),t.stop()}))}}export{z as FindAndReplace,H as FindAndReplaceEditing,A as FindAndReplaceUI,$ as FindAndReplaceUtils,I as FindCommand,P as FindNextCommand,S as FindPreviousCommand,N as ReplaceAllCommand,O as ReplaceCommand};