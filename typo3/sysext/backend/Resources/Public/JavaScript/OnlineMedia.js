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
define(["require","exports","./Enum/KeyTypes","jquery","nprogress","./Modal","./Severity","TYPO3/CMS/Core/SecurityUtility","TYPO3/CMS/Backend/Utility/MessageUtility"],function(e,t,i,a,n,r,o,l,s){"use strict";return new class{constructor(){this.securityUtility=new l,a(()=>{this.registerEvents()})}registerEvents(){const e=this;a(document).on("click",".t3js-online-media-add-btn",t=>{e.triggerModal(a(t.currentTarget))})}addOnlineMedia(e,t){const i=e.data("target-folder"),l=e.data("online-media-allowed"),d=e.data("file-irre-object");n.start(),a.post(TYPO3.settings.ajaxUrls.online_media_create,{url:t,targetFolder:i,allowed:l},e=>{if(e.file){const t={objectGroup:d,table:"sys_file",uid:e.file};s.MessageUtility.send(t)}else{const t=r.confirm("ERROR",e.error,o.error,[{text:TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+o.getCssClass(o.error),name:"ok",active:!0}]).on("confirm.button.ok",()=>{t.modal("hide")})}n.done()})}triggerModal(e){const t=e.data("btn-submit")||"Add",n=e.data("placeholder")||"Paste media url here...",l=a.map(e.data("online-media-allowed").split(","),e=>'<span class="label label-success">'+this.securityUtility.encodeHtml(e.toUpperCase(),!1)+"</span>"),s=e.data("online-media-allowed-help-text")||"Allow to embed from sources:",d=a("<div>").attr("class","form-control-wrap").append([a("<input>").attr("type","text").attr("class","form-control online-media-url").attr("placeholder",n),a("<div>").attr("class","help-block").html(this.securityUtility.encodeHtml(s,!1)+"<br>"+l.join(" "))]),c=r.show(e.attr("title"),d,o.notice,[{text:t,btnClass:"btn btn-primary",name:"ok",trigger:()=>{const t=c.find("input.online-media-url").val();t&&(c.modal("hide"),this.addOnlineMedia(e,t))}}]);c.on("shown.bs.modal",e=>{a(e.currentTarget).find("input.online-media-url").first().focus().on("keydown",e=>{e.keyCode===i.KeyTypesEnum.ENTER&&c.find('button[name="ok"]').trigger("click")})})}}});