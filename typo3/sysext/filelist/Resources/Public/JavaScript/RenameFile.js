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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","TYPO3/CMS/Backend/Enum/Severity","jquery","TYPO3/CMS/Backend/Modal"],(function(e,t,a,n,i){"use strict";n=__importDefault(n);return new class{constructor(){this.initialize()}initialize(){n.default(".t3js-submit-file-rename").on("click",this.checkForDuplicate)}checkForDuplicate(e){e.preventDefault();const t=n.default("#"+n.default(e.currentTarget).attr("form")),l=t.find('input[name="data[rename][0][target]"]'),r=t.find('input[name="data[rename][0][conflictMode]"]'),s=TYPO3.settings.ajaxUrls.file_exists;n.default.ajax({cache:!1,data:{fileName:l.val(),fileTarget:t.find('input[name="data[rename][0][destination]"]').val()},success:e=>{const n=void 0!==e.uid,s=l.data("original"),c=l.val();if(n&&s!==c){const e=TYPO3.lang["file_rename.exists.description"].replace("{0}",s).replace(/\{1\}/g,c);i.confirm(TYPO3.lang["file_rename.exists.title"],e,a.SeverityEnum.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:TYPO3.lang["file_rename.actions.cancel"]},{btnClass:"btn-primary",name:"rename",text:TYPO3.lang["file_rename.actions.rename"]},{btnClass:"btn-default",name:"replace",text:TYPO3.lang["file_rename.actions.override"]}]).on("button.clicked",e=>{"cancel"!==e.target.name&&(r.val(e.target.name),t.submit()),i.dismiss()})}else t.submit()},url:s})}}}));