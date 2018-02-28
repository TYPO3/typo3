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
define(["require","exports","cm/lib/codemirror","jquery"],function(e,t,n,i){"use strict";return new(function(){function t(){this.initialize()}return t.createPanelNode=function(e,t){return i("<div />",{class:"CodeMirror-panel CodeMirror-panel-"+e,id:"panel-"+e}).append(i("<span />").text(t)).get(0)},t.prototype.findAndInitializeEditors=function(){i(document).find("textarea.t3editor").each(function(){var o=i(this);if(!o.prop("is_t3editor")){var r=o.data("codemirror-config"),a=r.mode.split("/"),l=i.merge([a.join("/")],JSON.parse(r.addons)),c=JSON.parse(r.options);e(l,function(){var e=n.fromTextArea(o.get(0),{extraKeys:{"Ctrl-Alt-F":function(e){e.setOption("fullScreen",!e.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:function(e){e.getOption("fullScreen")&&e.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:a[a.length-1]});i.each(c,function(t,n){e.setOption(t,n)}),e.addPanel(t.createPanelNode("bottom",o.attr("alt")),{position:"bottom",stable:!0})}),o.prop("is_t3editor",!0)}})},t.prototype.initialize=function(){var e=this;i(function(){e.findAndInitializeEditors()})},t}())});