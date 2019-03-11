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
define(["require","exports","TYPO3/CMS/Backend/Enum/Severity","jquery","TYPO3/CMS/Backend/Modal"],function(e,a,n,t,i){"use strict";return new(function(){function e(){this.initialize()}return e.prototype.initialize=function(){t(".t3js-submit-file-rename").on("click",this.checkForDuplicate)},e.prototype.checkForDuplicate=function(e){e.preventDefault();var a=t("#"+t(e.currentTarget).attr("form")),r=a.find('input[name="data[rename][0][target]"]'),l=a.find('input[name="data[rename][0][conflictMode]"]'),c=TYPO3.settings.ajaxUrls.file_exists;t.ajax({cache:!1,data:{fileName:r.val(),fileTarget:a.find('input[name="data[rename][0][destination]"]').val()},success:function(e){var t=void 0!==e.uid,c=r.data("original"),s=r.val();if(t&&c!==s){var o=TYPO3.lang["file_rename.exists.description"].replace("{0}",c).replace("{1}",s);i.confirm(TYPO3.lang["file_rename.exists.title"],o,n.SeverityEnum.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:TYPO3.lang["file_rename.actions.cancel"]},{btnClass:"btn-primary",name:"rename",text:TYPO3.lang["file_rename.actions.rename"]},{btnClass:"btn-default",name:"replace",text:TYPO3.lang["file_rename.actions.override"]}]).on("button.clicked",function(e){"cancel"!==e.target.name&&(l.val(e.target.name),a.submit()),i.dismiss()})}else a.submit()},url:c})},e}())});