/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
var __decorate=function(e,t,r,o){var i,l=arguments.length,n=l<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,r,o);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(n=(l<3?i(n):l>3?i(t,r,n):i(t,r))||n);return l>3&&n&&Object.defineProperty(t,r,n),n};import{LitElement,html,css}from"lit";import{customElement,property,state}from"lit/decorators.js";import{EditorView,lineNumbers,highlightSpecialChars,drawSelection,keymap,placeholder}from"@codemirror/view";import{EditorState}from"@codemirror/state";import{syntaxHighlighting,defaultHighlightStyle}from"@codemirror/language";import{defaultKeymap,indentWithTab}from"@codemirror/commands";import{oneDark}from"@codemirror/theme-one-dark";import{executeJavaScriptModuleInstruction,loadModule,resolveSubjectRef}from"@typo3/core/java-script-item-processor.js";import"@typo3/backend/element/spinner-element.js";let CodeMirrorElement=class extends LitElement{constructor(){super(...arguments),this.mode=null,this.addons=[],this.keymaps=[],this.lineDigits=0,this.autoheight=!1,this.nolazyload=!1,this.readonly=!1,this.fullscreen=!1,this.panel="bottom",this.editorView=null}render(){return html`
      <div id="codemirror-parent" @keydown=${e=>this.onKeydown(e)}></div>
      ${this.label?html`<div class="panel panel-${this.panel}">${this.label}</div>`:""}
      ${null===this.editorView?html`<typo3-backend-spinner size="large" variant="dark"></typo3-backend-spinner>`:""}
    `}firstUpdated(){if(this.nolazyload)return void this.initializeEditor(this.firstElementChild);const e={root:document.body},t=new IntersectionObserver((e=>{e.forEach((e=>{e.intersectionRatio>0&&(t.unobserve(e.target),this.firstElementChild&&"textarea"===this.firstElementChild.nodeName.toLowerCase()&&this.initializeEditor(this.firstElementChild))}))}),e);t.observe(this)}onKeydown(e){e.ctrlKey&&e.altKey&&"f"===e.key&&(e.preventDefault(),this.fullscreen=!0),"Escape"===e.key&&this.fullscreen&&(e.preventDefault(),this.fullscreen=!1)}async initializeEditor(e){const t=EditorView.updateListener.of((t=>{t.docChanged&&(e.value=t.state.doc.toString(),e.dispatchEvent(new CustomEvent("change",{bubbles:!0})))}));this.lineDigits>0?this.style.setProperty("--rows",this.lineDigits.toString()):e.getAttribute("rows")&&this.style.setProperty("--rows",e.getAttribute("rows"));const r=[oneDark,t,lineNumbers(),highlightSpecialChars(),drawSelection(),EditorState.allowMultipleSelections.of(!0),syntaxHighlighting(defaultHighlightStyle,{fallback:!0})];if(this.readonly&&r.push(EditorState.readOnly.of(!0)),this.placeholder&&r.push(placeholder(this.placeholder)),this.mode){const e=await executeJavaScriptModuleInstruction(this.mode);r.push(...e)}this.addons.length>0&&r.push(...await Promise.all(this.addons.map((e=>executeJavaScriptModuleInstruction(e)))));const o=[...defaultKeymap,indentWithTab];if(this.keymaps.length>0){const e=await Promise.all(this.keymaps.map((e=>loadModule(e).then((t=>resolveSubjectRef(t,e))))));e.forEach((e=>o.push(...e)))}r.push(keymap.of(o)),this.editorView=new EditorView({state:EditorState.create({doc:e.value,extensions:r}),parent:this.renderRoot.querySelector("#codemirror-parent"),root:this.renderRoot})}};CodeMirrorElement.styles=css`
    :host {
      display: flex;
      flex-direction: column;
      position: relative;
    }

    :host([fullscreen]) {
      position: fixed;
      inset: 64px 0 0;
      z-index: 9;
    }

    typo3-backend-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    #codemirror-parent {
      min-height: calc(8px + 12px * 1.4 * var(--rows, 18));
    }
    #codemirror-parent,
    .cm-editor {
      display: flex;
      flex-direction: column;
      flex: 1;
      max-height: 100%;
    }

    .cm-scroller {
      min-height: 100%;
      max-height: calc(100vh - 10rem);
      flex: 1;
      color-scheme: dark;
    }

    :host([fullscreen]) .cm-scroller {
      min-height: initial;
      max-height: 100%;
    }

    :host([autoheight]) .cm-scroller {
      max-height: initial;
    }

    .panel {
      font-size: .85em;
      color: #abb2bf;
      background: #282c34;
      border-style: solid;
      border-color: #7d8799;
      border-width: 0;
      border-top-width: 1px;
      padding: .25em .5em;
    }

    .panel-top {
      border-top-width: 0;
      border-bottom-width: 1px;
      order: -1;
    }
  `,__decorate([property({type:Object})],CodeMirrorElement.prototype,"mode",void 0),__decorate([property({type:Array})],CodeMirrorElement.prototype,"addons",void 0),__decorate([property({type:Array})],CodeMirrorElement.prototype,"keymaps",void 0),__decorate([property({type:Number})],CodeMirrorElement.prototype,"lineDigits",void 0),__decorate([property({type:Boolean,reflect:!0})],CodeMirrorElement.prototype,"autoheight",void 0),__decorate([property({type:Boolean})],CodeMirrorElement.prototype,"nolazyload",void 0),__decorate([property({type:Boolean})],CodeMirrorElement.prototype,"readonly",void 0),__decorate([property({type:Boolean,reflect:!0})],CodeMirrorElement.prototype,"fullscreen",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"label",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"placeholder",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"panel",void 0),__decorate([state()],CodeMirrorElement.prototype,"editorView",void 0),CodeMirrorElement=__decorate([customElement("typo3-t3editor-codemirror")],CodeMirrorElement);export{CodeMirrorElement};