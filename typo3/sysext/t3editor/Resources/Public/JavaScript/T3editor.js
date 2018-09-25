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
define(["require","exports","cm/lib/codemirror","jquery","TYPO3/CMS/Backend/FormEngine"],function(e,t,n,i,o){"use strict";return new(function(){function t(){this.initialize()}return t.createPanelNode=function(e,t){return i("<div />",{class:"CodeMirror-panel CodeMirror-panel-"+e,id:"panel-"+e}).append(i("<span />").text(t)).get(0)},t.prototype.findAndInitializeEditors=function(){i(document).find("textarea.t3editor").each(function(){var r=i(this);if(!r.prop("is_t3editor")){var a=r.data("codemirror-config"),c=a.mode.split("/"),l=i.merge([c.join("/")],JSON.parse(a.addons)),d=JSON.parse(a.options);e(l,function(){var e=n.fromTextArea(r.get(0),{extraKeys:{"Ctrl-Alt-F":function(e){e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:function(e){e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:c[c.length-1]});i.each(d,function(t,n){e.setOption(t,n)}),e.on("change",function(){o.Validation.markFieldAsChanged(r)}),e.addPanel(t.createPanelNode("bottom",r.attr("alt")),{position:"bottom",stable:!0})}),r.prop("is_t3editor",!0)}})},t.prototype.initialize=function(){var e=this;i(function(){e.findAndInitializeEditors()})},t}())});