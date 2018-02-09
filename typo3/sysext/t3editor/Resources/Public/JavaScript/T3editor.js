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
define(["require","exports","cm/lib/codemirror","jquery"],function(a,b,c,d){"use strict";var e=function(){function b(){this.initialize()}return b.createPanelNode=function(a,b){var c=d("<div />",{class:"CodeMirror-panel CodeMirror-panel-"+a,id:"panel-"+a}).append(d("<span />").text(b));return c.get(0)},b.prototype.findAndInitializeEditors=function(){d(document).find("textarea.t3editor").each(function(){var e=d(this);if(!e.prop("is_t3editor")){var f=e.data("codemirror-config"),g=f.mode.split("/"),h=d.merge([g.join("/")],JSON.parse(f.addons)),i=JSON.parse(f.options);a(h,function(){var a=c.fromTextArea(e.get(0),{extraKeys:{"Ctrl-Alt-F":function(a){a.setOption("fullScreen",!a.getOption("fullScreen"))},"Ctrl-Space":"autocomplete",Esc:function(a){a.getOption("fullScreen")&&a.setOption("fullScreen",!1)}},fullScreen:!1,lineNumbers:!0,lineWrapping:!0,mode:g[g.length-1]});d.each(i,function(b,c){a.setOption(b,c)}),a.addPanel(b.createPanelNode("bottom",e.attr("alt")),{position:"bottom",stable:!0})}),e.prop("is_t3editor",!0)}})},b.prototype.initialize=function(){var a=this;d(function(){a.findAndInitializeEditors()})},b}();return new e});