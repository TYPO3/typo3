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
var __decorate=function(e,t,o,r){var i,a=arguments.length,l=a<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,o,r);else for(var n=e.length-1;n>=0;n--)(i=e[n])&&(l=(a<3?i(l):a>3?i(t,o,l):i(t,o))||l);return a>3&&l&&Object.defineProperty(t,o,l),l};import{LitElement,html,css}from"lit";import{customElement,property,state}from"lit/decorators.js";import{EditorView,lineNumbers,highlightSpecialChars,drawSelection,keymap,placeholder}from"@codemirror/view";import{EditorState,Compartment}from"@codemirror/state";import{syntaxHighlighting,defaultHighlightStyle}from"@codemirror/language";import{defaultKeymap,indentWithTab}from"@codemirror/commands";import{oneDark}from"@codemirror/theme-one-dark";import{executeJavaScriptModuleInstruction,loadModule,resolveSubjectRef}from"@typo3/core/java-script-item-processor.js";import"@typo3/backend/element/spinner-element.js";let CodeMirrorElement=class extends LitElement{constructor(){super(...arguments),this.mode=null,this.addons=[],this.keymaps=[],this.lineDigits=0,this.autoheight=!1,this.nolazyload=!1,this.readonly=!1,this.fullscreen=!1,this.panel="bottom",this.editorTheme=null,this.editorView=null}render(){return html`
      ${this.label&&"top"===this.panel?html`<div class="codemirror-label codemirror-label-top">${this.label}</div>`:""}
      <div id="codemirror-parent" @keydown=${e=>this.onKeydown(e)}></div>
      ${this.label&&"bottom"===this.panel?html`<div class="codemirror-label codemirror-label-bottom">${this.label}</div>`:""}
      ${null===this.editorView?html`<typo3-backend-spinner size="large"></typo3-backend-spinner>`:""}
    `}firstUpdated(){if(this.nolazyload)return void this.initializeEditor(this.firstElementChild);const e={root:document.body},t=new IntersectionObserver((e=>{e.forEach((e=>{e.intersectionRatio>0&&(t.unobserve(e.target),this.firstElementChild&&"textarea"===this.firstElementChild.nodeName.toLowerCase()&&this.initializeEditor(this.firstElementChild))}))}),e);t.observe(this)}onKeydown(e){e.ctrlKey&&e.altKey&&"f"===e.key&&(e.preventDefault(),this.fullscreen=!0),"Escape"===e.key&&this.fullscreen&&(e.preventDefault(),this.fullscreen=!1)}async initializeEditor(e){const t=EditorView.updateListener.of((t=>{t.docChanged&&(e.value=t.state.doc.toString(),e.dispatchEvent(new CustomEvent("change",{bubbles:!0})))}));this.lineDigits>0?this.style.setProperty("--rows",this.lineDigits.toString()):e.getAttribute("rows")&&this.style.setProperty("--rows",e.getAttribute("rows")),this.editorTheme=new Compartment;const o=[this.editorTheme.of([]),t,lineNumbers(),highlightSpecialChars(),drawSelection(),EditorState.allowMultipleSelections.of(!0),syntaxHighlighting(defaultHighlightStyle,{fallback:!0})];if(this.readonly&&o.push(EditorState.readOnly.of(!0)),this.placeholder&&o.push(placeholder(this.placeholder)),this.mode){const e=await executeJavaScriptModuleInstruction(this.mode);o.push(...e)}this.addons.length>0&&o.push(...await Promise.all(this.addons.map((e=>executeJavaScriptModuleInstruction(e)))));const r=[...defaultKeymap,indentWithTab];if(this.keymaps.length>0){const e=await Promise.all(this.keymaps.map((e=>loadModule(e).then((t=>resolveSubjectRef(t,e))))));e.forEach((e=>r.push(...e)))}o.push(keymap.of(r)),this.editorView=new EditorView({state:EditorState.create({doc:e.value,extensions:o}),parent:this.renderRoot.querySelector("#codemirror-parent"),root:this.renderRoot});const i=window.matchMedia("(prefers-color-scheme: dark)");this.toggleDarkMode(i.matches),i.addEventListener("change",(e=>{this.toggleDarkMode(e.matches)}))}toggleDarkMode(e){this.editorView.dispatch({effects:this.editorTheme.reconfigure(e?oneDark:[])})}};CodeMirrorElement.styles=css`
    @media (prefers-color-scheme: dark) {
      :host {
        color-scheme: dark;
      }
    }

    :host([fullscreen]) {
      position: fixed;
      inset: 64px 0 0;
      z-index: 9;
    }

    :host([fullscreen]) .cm-scroller {
      min-height: initial;
      max-height: 100%;
    }

    :host([autoheight]) .cm-scroller {
      max-height: initial;
    }

    .codemirror-label {
      font-size: .875em;
      opacity: .75;
    }

    .codemirror-label-top {
      margin-bottom: .25rem;
    }

    .codemirror-label-bottom {
      margin-top: .25rem;
    }

    typo3-backend-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .cm-editor {
      overflow: hidden;
      border-radius: var(--typo3-input-border-radius);
      border: var(--typo3-input-border-width) solid var(--typo3-input-border-color);
      box-shadow: var(--typo3-input-box-shadow);
      transition: outline-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .cm-focused {
      outline: none !important;
      border-color: var(--typo3-input-focus-border-color);
      box-shadow: var(--typo3-input-box-shadow), var(--typo3-input-focus-box-shadow);
    }

    .cm-gutters {
      height: auto !important;
      position: relative !important;
    }

    .cm-content {
      min-height: calc(8px + 12px * 1.4 * var(--rows, 18)) !important;
    }

    .cm-scroller {
      min-height: 100%;
      max-height: calc(100vh - 10rem);
      flex: 1;
      align-items: unset;
    }

    .ͼ1 .cm-scroller {
      align-items: unset !important;
    }
  `,__decorate([property({type:Object})],CodeMirrorElement.prototype,"mode",void 0),__decorate([property({type:Array})],CodeMirrorElement.prototype,"addons",void 0),__decorate([property({type:Array})],CodeMirrorElement.prototype,"keymaps",void 0),__decorate([property({type:Number})],CodeMirrorElement.prototype,"lineDigits",void 0),__decorate([property({type:Boolean,reflect:!0})],CodeMirrorElement.prototype,"autoheight",void 0),__decorate([property({type:Boolean})],CodeMirrorElement.prototype,"nolazyload",void 0),__decorate([property({type:Boolean})],CodeMirrorElement.prototype,"readonly",void 0),__decorate([property({type:Boolean,reflect:!0})],CodeMirrorElement.prototype,"fullscreen",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"label",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"placeholder",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"panel",void 0),__decorate([state()],CodeMirrorElement.prototype,"editorTheme",void 0),__decorate([state()],CodeMirrorElement.prototype,"editorView",void 0),CodeMirrorElement=__decorate([customElement("typo3-t3editor-codemirror")],CodeMirrorElement);export{CodeMirrorElement};