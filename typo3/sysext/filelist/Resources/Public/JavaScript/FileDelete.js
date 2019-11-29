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
define(["require","exports","TYPO3/CMS/Backend/Enum/Severity","jquery","TYPO3/CMS/Backend/Modal"],(function(e,t,n,a,o){"use strict";return new class{constructor(){a(()=>{a(document).on("click",".t3js-filelist-delete",e=>{e.preventDefault();const t=a(e.currentTarget);let i=t.data("redirectUrl");i=i?encodeURIComponent(i):encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search);const r=t.data("identifier"),c=t.data("deleteType"),l=t.data("deleteUrl")+"&data[delete][0][data]="+encodeURIComponent(r)+"&data[delete][0][redirect]="+i;if(t.data("check")){o.confirm(t.data("title"),t.data("content"),n.SeverityEnum.warning,[{text:TYPO3.lang["buttons.confirm.delete_file.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no"},{text:TYPO3.lang["buttons.confirm."+c+".yes"]||"Yes, delete this file or folder",btnClass:"btn-warning",name:"yes"}]).on("button.clicked",e=>{const t=e.target.name;"no"===t?o.dismiss():"yes"===t&&(o.dismiss(),top.list_frame.location.href=l)})}else top.list_frame.location.href=l})})}}}));