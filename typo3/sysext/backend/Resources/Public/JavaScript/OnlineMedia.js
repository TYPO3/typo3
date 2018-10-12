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
define(["require","exports","./Enum/KeyTypes","jquery","nprogress","./Modal","./Severity"],function(e,t,n,o,i,r,a){"use strict";return new(function(){function e(){var e=this;o(function(){e.registerEvents()})}return e.prototype.registerEvents=function(){var e=this;o(document).on("click",".t3js-online-media-add-btn",function(t){e.triggerModal(o(t.currentTarget))})},e.prototype.addOnlineMedia=function(e,t){var n=e.data("target-folder"),l=e.data("online-media-allowed"),d=e.data("file-irre-object");i.start(),o.post(TYPO3.settings.ajaxUrls.online_media_create,{url:t,targetFolder:n,allowed:l},function(e){if(e.file)window.inline.delayedImportElement(d,"sys_file",e.file,"file");else var t=r.confirm("ERROR",e.error,a.error,[{text:TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+a.getCssClass(a.error),name:"ok",active:!0}]).on("confirm.button.ok",function(){t.modal("hide")});i.done()})},e.prototype.triggerModal=function(e){var t=this,i=e.data("btn-submit")||"Add",l=e.data("placeholder")||"Paste media url here...",d=o.map(e.data("online-media-allowed").split(","),function(e){return'<span class="label label-success">'+e.toUpperCase()+"</span>"}),s=e.data("online-media-allowed-help-text")||"Allow to embed from sources:",c=r.show(e.attr("title"),'<div class="form-control-wrap"><input type="text" class="form-control online-media-url" placeholder="'+l+'" /></div><div class="help-block">'+s+"<br>"+d.join(" ")+"</div>",a.notice,[{text:i,btnClass:"btn btn-primary",name:"ok",trigger:function(){var n=c.find("input.online-media-url").val();n&&(c.modal("hide"),t.addOnlineMedia(e,n))}}]);c.on("shown.bs.modal",function(e){o(e.currentTarget).find("input.online-media-url").first().focus().on("keydown",function(e){e.keyCode===n.KeyTypesEnum.ENTER&&c.find('button[name="ok"]').trigger("click")})})},e}())});