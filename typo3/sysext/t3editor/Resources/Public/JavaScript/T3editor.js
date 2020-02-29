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
define(["require","exports","cm/lib/codemirror","jquery","TYPO3/CMS/Backend/FormEngine"],(function(e,t,i,r,o){"use strict";class n{static createPanelNode(e,t){return r("<div />",{class:"CodeMirror-panel CodeMirror-panel-"+e,id:"panel-"+e}).append(r("<span />").text(t)).get(0)}constructor(){this.initialize()}initialize(){r(()=>{this.observeEditorCandidates()})}observeEditorCandidates(){const e={root:document.body};let t=new IntersectionObserver(e=>{e.forEach(e=>{if(e.intersectionRatio>0){const t=r(e.target);t.prop("is_t3editor")||this.initializeEditor(t)}})},e);document.querySelectorAll("textarea.t3editor").forEach(e=>{t.observe(e)})}initializeEditor(t){const a=t.data("codemirror-config"),s=a.mode.split("/"),l=r.merge([s.join("/")],JSON.parse(a.addons)),d=JSON.parse(a.options);e(l,()=>{const e=i.fromTextArea(t.get(0),{extraKeys:{"Ctrl-F":"findPersistent","Cmd-F":"findPersistent","Ctrl-Alt-F":e=>{e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:e=>{e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:s[s.length-1]});r.each(d,(t,i)=>{e.setOption(t,i)}),e.on("change",()=>{o.Validation.markFieldAsChanged(t)}),e.addPanel(n.createPanelNode("bottom",t.attr("alt")),{position:"bottom",stable:!0})}),t.prop("is_t3editor",!0)}}return new n}));