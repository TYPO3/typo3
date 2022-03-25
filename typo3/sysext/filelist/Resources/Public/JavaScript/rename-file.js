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
import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Modal from"@typo3/backend/modal.js";import DocumentService from"@typo3/core/document-service.js";class RenameFile{constructor(){DocumentService.ready().then((()=>{this.initialize()}))}initialize(){const e=document.querySelector(".t3js-submit-file-rename");null!==e&&e.addEventListener("click",this.checkForDuplicate)}checkForDuplicate(e){e.preventDefault();const t=e.currentTarget.form,a=t.querySelector('input[name="data[rename][0][target]"]'),n=t.querySelector('input[name="data[rename][0][destination]"]'),i=t.querySelector('input[name="data[rename][0][conflictMode]"]'),r={fileName:a.value};null!==n&&(r.fileTarget=n.value),new AjaxRequest(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments(r).get({cache:"no-cache"}).then((async e=>{const n=void 0!==(await e.resolve()).uid,r=a.dataset.original,l=a.value;if(n&&r!==l){const e=TYPO3.lang["file_rename.exists.description"].replace("{0}",r).replace(/\{1\}/g,l),a=Modal.confirm(TYPO3.lang["file_rename.exists.title"],e,SeverityEnum.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:TYPO3.lang["file_rename.actions.cancel"]},{btnClass:"btn-primary",name:"rename",text:TYPO3.lang["file_rename.actions.rename"]},{btnClass:"btn-default",name:"replace",text:TYPO3.lang["file_rename.actions.override"]}]);a.addEventListener("button.clicked",(e=>{const n=e.target;"cancel"!==n.name&&(null!==i&&(i.value=n.name),t.submit()),a.hideModal()}))}else t.submit()}))}}export default new RenameFile;