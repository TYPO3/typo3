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
define(["require","exports","jquery","TYPO3/CMS/Backend/Modal"],function(t,e,n,i){"use strict";return new function(){n(function(){n(document).on("click",".t3js-confirm-trigger",function(t){var e=n(t.currentTarget);i.confirm(e.data("title"),e.data("message")).on("confirm.button.ok",function(){n("#t3js-submit-field").attr("name",e.attr("name")).closest("form").submit(),i.currentModal.trigger("modal-dismiss")}).on("confirm.button.cancel",function(){i.currentModal.trigger("modal-dismiss")})}),n(".t3js-impexp-toggledisabled").on("click",function(){var t=n('table.t3js-impexp-preview tr[data-active="hidden"] input.t3js-exclude-checkbox');if(t.length){var e=t.get(0);t.prop("checked",!e.checked)}})})}});