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
define(["require","exports","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Core/DocumentService"],(function(e,t,a,n,i,r){"use strict";return new class{constructor(){r.ready().then(()=>{this.initialize()})}initialize(){const e=document.querySelector(".t3js-submit-file-rename");null!==e&&e.addEventListener("click",this.checkForDuplicate)}checkForDuplicate(e){e.preventDefault();const t=e.currentTarget.form,r=t.querySelector('input[name="data[rename][0][target]"]'),l=t.querySelector('input[name="data[rename][0][destination]"]'),c=t.querySelector('input[name="data[rename][0][conflictMode]"]'),s={fileName:r.value};null!==l&&(s.fileTarget=l.value),new n(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments(s).get({cache:"no-cache"}).then(async e=>{const n=void 0!==(await e.resolve()).uid,l=r.dataset.original,s=r.value;if(n&&l!==s){const e=TYPO3.lang["file_rename.exists.description"].replace("{0}",l).replace(/\{1\}/g,s);i.confirm(TYPO3.lang["file_rename.exists.title"],e,a.SeverityEnum.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:TYPO3.lang["file_rename.actions.cancel"]},{btnClass:"btn-primary",name:"rename",text:TYPO3.lang["file_rename.actions.rename"]},{btnClass:"btn-default",name:"replace",text:TYPO3.lang["file_rename.actions.override"]}]).on("button.clicked",e=>{"cancel"!==e.target.name&&(null!==c&&(c.value=e.target.name),t.submit()),i.dismiss()})}else t.submit()})}}}));