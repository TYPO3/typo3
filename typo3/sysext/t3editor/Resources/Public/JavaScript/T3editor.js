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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","cm/lib/codemirror","jquery","TYPO3/CMS/Backend/FormEngine"],(function(e,t,i,r,n){"use strict";i=__importDefault(i),r=__importDefault(r);class o{static createPanelNode(e,t){return r.default("<div />",{class:"CodeMirror-panel CodeMirror-panel-"+e,id:"panel-"+e}).append(r.default("<span />").text(t)).get(0)}constructor(){this.initialize()}initialize(){r.default(()=>{this.observeEditorCandidates()})}observeEditorCandidates(){const e={root:document.body};let t=new IntersectionObserver(e=>{e.forEach(e=>{if(e.intersectionRatio>0){const t=r.default(e.target);t.prop("is_t3editor")||this.initializeEditor(t)}})},e);document.querySelectorAll("textarea.t3editor").forEach(e=>{t.observe(e)})}initializeEditor(t){const a=t.data("codemirror-config"),l=a.mode.split("/"),s=r.default.merge([l.join("/")],JSON.parse(a.addons)),d=JSON.parse(a.options);e(s,()=>{const e=i.default.fromTextArea(t.get(0),{extraKeys:{"Ctrl-F":"findPersistent","Cmd-F":"findPersistent","Ctrl-Alt-F":e=>{e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:e=>{e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:l[l.length-1]});r.default.each(d,(t,i)=>{e.setOption(t,i)}),e.on("change",()=>{n.Validation.markFieldAsChanged(t)});const s=o.createPanelNode("bottom",a.label);if(e.addPanel(s,{position:"bottom",stable:!1}),t.attr("rows")){const i=18,r=4;e.setSize(null,parseInt(t.attr("rows"),10)*i+r+s.getBoundingClientRect().height)}else e.getWrapperElement().style.height=document.body.getBoundingClientRect().height-e.getWrapperElement().getBoundingClientRect().top-80+"px",e.setOption("viewportMargin",1/0)}),t.prop("is_t3editor",!0)}}return new o}));