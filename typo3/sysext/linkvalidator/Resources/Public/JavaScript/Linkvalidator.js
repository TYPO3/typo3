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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification"],function(t,e,n,i){"use strict";return new(function(){function t(){this.initializeEvents()}return t.prototype.toggleActionButton=function(t){var e=!0;n("."+t).each(function(t,i){n(i).prop("checked")&&(e=!1)}),"check"===t?n("#updateLinkList").prop("disabled",e):n("#refreshLinkList").prop("disabled",e)},t.prototype.initializeEvents=function(){var t=this;n(".refresh").on("click",function(){t.toggleActionButton("refresh")}),n(".check").on("click",function(){t.toggleActionButton("check")}),n(".t3js-update-button").on("click",function(t){var e=n(t.currentTarget),c=e.attr("name"),o="Event triggered";"refreshLinkList"!==c&&"updateLinkList"!==c||(o=e.data("notification-message")),i.success(o)})},t}())});