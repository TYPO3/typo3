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
import{SeverityEnum as p}from"@typo3/backend/enum/severity.js";import g from"@typo3/core/ajax/ajax-request.js";import v from"@typo3/backend/modal.js";import b from"@typo3/core/document-service.js";class h{constructor(){b.ready().then(()=>{this.initialize()})}initialize(){const e=document.querySelector(".t3js-submit-file-rename");e!==null&&e.addEventListener("click",this.checkForDuplicate)}checkForDuplicate(e){e.preventDefault();const t=e.currentTarget.form,n=t.querySelector('input[name="data[rename][0][target]"]'),a=t.querySelector('input[name="data[rename][0][destination]"]'),i=t.querySelector('input[name="data[rename][0][conflictMode]"]'),l={fileName:n.value};a!==null&&(l.fileTarget=a.value),new g(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments(l).get({cache:"no-cache"}).then(async m=>{const u=typeof(await m.resolve()).uid<"u",r=n.dataset.original,c=n.value;if(u&&r!==c){const d=TYPO3.lang["file_rename.exists.description"].replace("{0}",r).replace(/\{1\}/g,c),s=v.confirm(TYPO3.lang["file_rename.exists.title"],d,p.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:TYPO3.lang["file_rename.actions.cancel"]},{btnClass:"btn-primary",name:"rename",text:TYPO3.lang["file_rename.actions.rename"]},{btnClass:"btn-default",name:"replace",text:TYPO3.lang["file_rename.actions.override"]}]);s.addEventListener("button.clicked",f=>{const o=f.target;o.name!=="cancel"&&(i!==null&&(i.value=o.name),t.submit()),s.hideModal()})}else t.submit()})}}var x=new h;export{x as default};
