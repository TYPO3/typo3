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
var __decorate=function(e,t,o,r){var i,n=arguments.length,l=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(e,t,o,r);else for(var s=e.length-1;s>=0;s--)(i=e[s])&&(l=(n<3?i(l):n>3?i(t,o,l):i(t,o))||l);return n>3&&l&&Object.defineProperty(t,o,l),l};import{LitElement,html,css}from"lit";import{customElement,property,state}from"lit/decorators.js";import"@typo3/backend/element/spinner-element.js";let CodeMirrorElement=class extends LitElement{constructor(){super(...arguments),this.addons=["codemirror/addon/display/panel"],this.options={},this.scrollto=0,this.marktext=[],this.lineDigits=0,this.autoheight=!1,this.nolazyload=!1,this.panel="bottom",this.loaded=!1}render(){return html`
      <slot></slot>
      <slot name="codemirror"></slot>
      ${this.loaded?"":html`<typo3-backend-spinner size="large" variant="dark"></typo3-backend-spinner>`}
    `}firstUpdated(){if(this.nolazyload)return void this.initializeEditor(this.firstElementChild);const e={root:document.body};let t=new IntersectionObserver(e=>{e.forEach(e=>{e.intersectionRatio>0&&(t.unobserve(e.target),this.firstElementChild&&"textarea"===this.firstElementChild.nodeName.toLowerCase()&&this.initializeEditor(this.firstElementChild))})},e);t.observe(this)}createPanelNode(e,t){const o=document.createElement("div");o.setAttribute("class","CodeMirror-panel CodeMirror-panel-"+e),o.setAttribute("id","panel-"+e);const r=document.createElement("span");return r.textContent=t,o.appendChild(r),o}initializeEditor(e){const t=this.mode.split("/"),o=this.options;window.require(["codemirror",this.mode,...this.addons],r=>{const i=r(t=>{const o=document.createElement("div");o.setAttribute("slot","codemirror"),o.appendChild(t),this.insertBefore(o,e)},{value:e.value,extraKeys:{"Ctrl-F":"findPersistent","Cmd-F":"findPersistent","Ctrl-Alt-F":e=>{e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:e=>{e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:t[t.length-1]});Object.keys(o).map(e=>{i.setOption(e,o[e])}),i.on("change",()=>{e.value=i.getValue(),e.dispatchEvent(new CustomEvent("change",{bubbles:!0}))});const n=this.createPanelNode(this.panel,this.label);if(i.addPanel(n,{position:this.panel,stable:!1}),e.getAttribute("rows")){const t=18,o=4;i.setSize(null,parseInt(e.getAttribute("rows"),10)*t+o+n.getBoundingClientRect().height)}else i.getWrapperElement().style.height="auto",i.setOption("viewportMargin",1/0);this.autoheight&&i.setOption("viewportMargin",1/0),this.lineDigits>0&&i.setOption("lineNumberFormatter",e=>e.toString().padStart(this.lineDigits," ")),this.scrollto>0&&i.scrollIntoView({line:this.scrollto,ch:0});for(let e of this.marktext)e.from&&e.to&&i.markText(e.from,e.to,{className:"CodeMirror-markText"});this.loaded=!0})}};CodeMirrorElement.styles=css`
   :host {
     display: block;
     position: relative;
   }
   typo3-backend-spinner {
     position: absolute;
     top: 50%;
     left: 50%;
     transform: translate(-50%, -50%);
   }
  `,__decorate([property()],CodeMirrorElement.prototype,"mode",void 0),__decorate([property()],CodeMirrorElement.prototype,"label",void 0),__decorate([property({type:Array})],CodeMirrorElement.prototype,"addons",void 0),__decorate([property({type:Object})],CodeMirrorElement.prototype,"options",void 0),__decorate([property({type:Number})],CodeMirrorElement.prototype,"scrollto",void 0),__decorate([property({type:Object})],CodeMirrorElement.prototype,"marktext",void 0),__decorate([property({type:Number})],CodeMirrorElement.prototype,"lineDigits",void 0),__decorate([property({type:Boolean})],CodeMirrorElement.prototype,"autoheight",void 0),__decorate([property({type:Boolean})],CodeMirrorElement.prototype,"nolazyload",void 0),__decorate([property({type:String})],CodeMirrorElement.prototype,"panel",void 0),__decorate([state()],CodeMirrorElement.prototype,"loaded",void 0),CodeMirrorElement=__decorate([customElement("typo3-t3editor-codemirror")],CodeMirrorElement);export{CodeMirrorElement};