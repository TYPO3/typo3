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
define(["require","exports","./Enum/Severity","jquery","./Modal"],function(a,b,c,d,e){"use strict";var f=function(){function a(){this.initialize()}return a.prototype.initialize=function(){d(".t3js-submit-file-rename").on("click",this.checkForDuplicate)},a.prototype.checkForDuplicate=function(a){a.preventDefault();var b=d("#"+d(a.currentTarget).attr("form")),f=b.find('input[name="data[rename][0][target]"]'),g=b.find('input[name="data[rename][0][conflictMode]"]'),h=TYPO3.settings.ajaxUrls.file_exists;d.ajax({cache:!1,data:{fileName:f.val(),fileTarget:b.find('input[name="data[rename][0][destination]"]').val()},success:function(a){var d="undefined"!=typeof a.uid,h=f.data("original"),i=f.val();if(d&&h!==i){var j=TYPO3.lang["file_rename.exists.description"].replace("{0}",h).replace("{1}",i),k=e.confirm(TYPO3.lang["file_rename.exists.title"],j,c.SeverityEnum.warning,[{active:!0,btnClass:"btn-default",name:"cancel",text:TYPO3.lang["file_rename.actions.cancel"]},{btnClass:"btn-primary",name:"rename",text:TYPO3.lang["file_rename.actions.rename"]},{btnClass:"btn-default",name:"replace",text:TYPO3.lang["file_rename.actions.override"]}]);k.on("button.clicked",function(a){"cancel"!==a.target.name&&(g.val(a.target.name),b.submit()),e.dismiss()})}else b.submit()},url:h})},a}();return new f});