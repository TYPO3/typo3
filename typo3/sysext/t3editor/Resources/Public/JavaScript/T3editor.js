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
define(["require","exports","cm/lib/codemirror","jquery","TYPO3/CMS/Backend/FormEngine","intersection-observer"],(function(e,t,n,i,o){"use strict";return new(function(){function t(){this.initialize()}return t.createPanelNode=function(e,t){return i("<div />",{class:"CodeMirror-panel CodeMirror-panel-"+e,id:"panel-"+e}).append(i("<span />").text(t)).get(0)},t.prototype.observeEditorCandidates=function(){var e=this,t={root:document.body},n=new IntersectionObserver((function(t){t.forEach((function(t){if(t.intersectionRatio>0){var n=i(t.target);n.prop("is_t3editor")||e.initializeEditor(n)}}))}),t);i(document).find("textarea.t3editor").each((function(e,t){n.observe(t)}))},t.prototype.initialize=function(){var e=this;i((function(){e.observeEditorCandidates()}))},t.prototype.initializeEditor=function(r){var a=r.data("codemirror-config"),c=a.mode.split("/"),s=i.merge([c.join("/")],JSON.parse(a.addons)),d=JSON.parse(a.options);e(s,(function(){var e=n.fromTextArea(r.get(0),{extraKeys:{"Ctrl-F":"findPersistent","Cmd-F":"findPersistent","Ctrl-Alt-F":function(e){e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:function(e){e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:c[c.length-1]});i.each(d,(function(t,n){e.setOption(t,n)})),e.on("change",(function(){o.Validation.markFieldAsChanged(r)}));var a=t.createPanelNode("bottom",r.attr("alt"));if(e.addPanel(a,{position:"bottom",stable:!1}),r.attr("rows")){e.setSize(null,18*parseInt(r.attr("rows"),10)+4+a.getBoundingClientRect().height)}else e.getWrapperElement().style.height=document.body.getBoundingClientRect().height-e.getWrapperElement().getBoundingClientRect().top-80+"px",e.setOption("viewportMargin",1/0)})),r.prop("is_t3editor",!0)},t}())}));