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
var __decorate=this&&this.__decorate||function(e,t,o,r){var i,n=arguments.length,s=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,o,r);else for(var l=e.length-1;l>=0;l--)(i=e[l])&&(s=(n<3?i(s):n>3?i(t,o,s):i(t,o))||s);return n>3&&s&&Object.defineProperty(t,o,s),s},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","codemirror","lit","lit/decorators","TYPO3/CMS/Backend/FormEngine","TYPO3/CMS/Backend/Element/SpinnerElement"],(function(e,t,o,r,i,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.CodeMirrorElement=void 0,o=__importDefault(o);let s=class extends r.LitElement{constructor(){super(...arguments),this.addons=[],this.options={},this.loaded=!1}render(){return r.html`
      <slot></slot>
      <slot name="codemirror"></slot>
      ${this.loaded?"":r.html`<typo3-backend-spinner size="large" variant="dark"></typo3-backend-spinner>`}
    `}firstUpdated(){const e={root:document.body};let t=new IntersectionObserver(e=>{e.forEach(e=>{e.intersectionRatio>0&&(t.unobserve(e.target),this.firstElementChild&&"textarea"===this.firstElementChild.nodeName.toLowerCase()&&this.initializeEditor(this.firstElementChild))})},e);t.observe(this)}createPanelNode(e,t){const o=document.createElement("div");o.setAttribute("class","CodeMirror-panel CodeMirror-panel-"+e),o.setAttribute("id","panel-"+e);const r=document.createElement("span");return r.textContent=t,o.appendChild(r),o}initializeEditor(t){const r=this.mode.split("/"),i=this.options;e([this.mode,...this.addons],()=>{const e=o.default(e=>{const o=document.createElement("div");o.setAttribute("slot","codemirror"),o.appendChild(e),this.insertBefore(o,t)},{value:t.value,extraKeys:{"Ctrl-F":"findPersistent","Cmd-F":"findPersistent","Ctrl-Alt-F":e=>{e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:e=>{e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:r[r.length-1]});Object.keys(i).map(t=>{e.setOption(t,i[t])}),e.on("change",()=>{t.value=e.getValue(),n.Validation.markFieldAsChanged(t)});const s=this.createPanelNode("bottom",this.label);if(e.addPanel(s,{position:"bottom",stable:!1}),t.getAttribute("rows")){const o=18,r=4;e.setSize(null,parseInt(t.getAttribute("rows"),10)*o+r+s.getBoundingClientRect().height)}else e.getWrapperElement().style.height=document.body.getBoundingClientRect().height-e.getWrapperElement().getBoundingClientRect().top-80+"px",e.setOption("viewportMargin",1/0);this.loaded=!0})}};s.styles=r.css`
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
  `,__decorate([i.property()],s.prototype,"mode",void 0),__decorate([i.property()],s.prototype,"label",void 0),__decorate([i.property({type:Array})],s.prototype,"addons",void 0),__decorate([i.property({type:Object})],s.prototype,"options",void 0),__decorate([i.state()],s.prototype,"loaded",void 0),s=__decorate([i.customElement("typo3-t3editor-codemirror")],s),t.CodeMirrorElement=s}));