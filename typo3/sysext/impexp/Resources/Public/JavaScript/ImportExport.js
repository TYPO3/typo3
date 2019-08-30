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
define(["require","exports","jquery","TYPO3/CMS/Backend/Modal"],function(t,e,c,r){"use strict";return new class{constructor(){c(()=>{c(document).on("click",".t3js-confirm-trigger",t=>{const e=c(t.currentTarget);r.confirm(e.data("title"),e.data("message")).on("confirm.button.ok",()=>{c("#t3js-submit-field").attr("name",e.attr("name")).closest("form").submit(),r.currentModal.trigger("modal-dismiss")}).on("confirm.button.cancel",()=>{r.currentModal.trigger("modal-dismiss")})}),c(".t3js-impexp-toggledisabled").on("click",()=>{const t=c('table.t3js-impexp-preview tr[data-active="hidden"] input.t3js-exclude-checkbox');if(t.length){const e=t.get(0);t.prop("checked",!e.checked)}})})}}});