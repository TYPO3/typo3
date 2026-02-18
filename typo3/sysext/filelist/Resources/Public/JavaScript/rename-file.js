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
import{SeverityEnum as g}from"@typo3/backend/enum/severity.js";import v from"@typo3/core/ajax/ajax-request.js";import b from"@typo3/backend/modal.js";import h from"@typo3/core/document-service.js";import n from"~labels/filelist.messages";class x{constructor(){h.ready().then(()=>{this.initialize()})}initialize(){const e=document.querySelector(".t3js-submit-file-rename");e!==null&&e.addEventListener("click",this.checkForDuplicate)}checkForDuplicate(e){e.preventDefault();const t=e.currentTarget.form,a=t.querySelector('input[name="data[rename][0][target]"]'),i=t.querySelector('input[name="data[rename][0][destination]"]'),r=t.querySelector('input[name="data[rename][0][conflictMode]"]'),l={fileName:a.value};i!==null&&(l.fileTarget=i.value),new v(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments(l).get({cache:"no-cache"}).then(async u=>{const d=typeof(await u.resolve()).uid<"u",o=a.dataset.original,s=a.value;if(d&&o!==s){const f=n.get("file_rename.exists.description",o,s),c=b.confirm(n.get("file_rename.exists.title"),f,g.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:n.get("file_rename.actions.cancel")},{btnClass:"btn-primary",name:"rename",text:n.get("file_rename.actions.rename")},{btnClass:"btn-default",name:"replace",text:n.get("file_rename.actions.override")}]);c.addEventListener("button.clicked",p=>{const m=p.target;m.name!=="cancel"&&(r!==null&&(r.value=m.name),t.submit()),c.hideModal()})}else t.submit()})}}var y=new x;export{y as default};
