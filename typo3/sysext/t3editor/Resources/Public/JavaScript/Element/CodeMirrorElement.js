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
var __decorate=this&&this.__decorate||function(t,e,o,r){var i,n=arguments.length,s=n<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,o,r);else for(var l=t.length-1;l>=0;l--)(i=t[l])&&(s=(n<3?i(s):n>3?i(e,o,s):i(e,o))||s);return n>3&&s&&Object.defineProperty(e,o,s),s},__importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","codemirror","lit","lit/decorators","TYPO3/CMS/Backend/Element/SpinnerElement"],(function(t,e,o,r,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.CodeMirrorElement=void 0,o=__importDefault(o);let n=class extends r.LitElement{constructor(){super(...arguments),this.addons=["codemirror/addon/display/panel"],this.options={},this.scrollto=0,this.marktext=[],this.lineDigits=0,this.autoheight=!1,this.nolazyload=!1,this.panel="bottom",this.loaded=!1}render(){return r.html`
      <slot></slot>
      <slot name="codemirror"></slot>
      ${this.loaded?"":r.html`<typo3-backend-spinner size="large" variant="dark"></typo3-backend-spinner>`}
    `}firstUpdated(){if(this.nolazyload)return void this.initializeEditor(this.firstElementChild);const t={root:document.body};let e=new IntersectionObserver(t=>{t.forEach(t=>{t.intersectionRatio>0&&(e.unobserve(t.target),this.firstElementChild&&"textarea"===this.firstElementChild.nodeName.toLowerCase()&&this.initializeEditor(this.firstElementChild))})},t);e.observe(this)}createPanelNode(t,e){const o=document.createElement("div");o.setAttribute("class","CodeMirror-panel CodeMirror-panel-"+t),o.setAttribute("id","panel-"+t);const r=document.createElement("span");return r.textContent=e,o.appendChild(r),o}initializeEditor(e){const r=this.mode.split("/"),i=this.options;t([this.mode,...this.addons],()=>{const t=(0,o.default)(t=>{const o=document.createElement("div");o.setAttribute("slot","codemirror"),o.appendChild(t),this.insertBefore(o,e)},{value:e.value,extraKeys:{"Ctrl-F":"findPersistent","Cmd-F":"findPersistent","Ctrl-Alt-F":t=>{t.setOption("fullScreen",!t.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:t=>{t.getOption("fullScreen")&&t.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:r[r.length-1]});Object.keys(i).map(e=>{t.setOption(e,i[e])}),t.on("change",()=>{e.value=t.getValue(),e.dispatchEvent(new CustomEvent("change",{bubbles:!0}))});const n=this.createPanelNode(this.panel,this.label);if(t.addPanel(n,{position:this.panel,stable:!1}),e.getAttribute("rows")){const o=18,r=4;t.setSize(null,parseInt(e.getAttribute("rows"),10)*o+r+n.getBoundingClientRect().height)}else t.getWrapperElement().style.height="auto",t.setOption("viewportMargin",1/0);this.autoheight&&t.setOption("viewportMargin",1/0),this.lineDigits>0&&t.setOption("lineNumberFormatter",t=>t.toString().padStart(this.lineDigits," ")),this.scrollto>0&&t.scrollIntoView({line:this.scrollto,ch:0});for(let e of this.marktext)e.from&&e.to&&t.markText(e.from,e.to,{className:"CodeMirror-markText"});this.loaded=!0})}};n.styles=r.css`
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
  `,__decorate([(0,i.property)()],n.prototype,"mode",void 0),__decorate([(0,i.property)()],n.prototype,"label",void 0),__decorate([(0,i.property)({type:Array})],n.prototype,"addons",void 0),__decorate([(0,i.property)({type:Object})],n.prototype,"options",void 0),__decorate([(0,i.property)({type:Number})],n.prototype,"scrollto",void 0),__decorate([(0,i.property)({type:Object})],n.prototype,"marktext",void 0),__decorate([(0,i.property)({type:Number})],n.prototype,"lineDigits",void 0),__decorate([(0,i.property)({type:Boolean})],n.prototype,"autoheight",void 0),__decorate([(0,i.property)({type:Boolean})],n.prototype,"nolazyload",void 0),__decorate([(0,i.property)({type:String})],n.prototype,"panel",void 0),__decorate([(0,i.state)()],n.prototype,"loaded",void 0),n=__decorate([(0,i.customElement)("typo3-t3editor-codemirror")],n),e.CodeMirrorElement=n}));