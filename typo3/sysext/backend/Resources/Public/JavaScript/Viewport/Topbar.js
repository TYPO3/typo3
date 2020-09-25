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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","jquery"],(function(t,e,r,o){"use strict";return function(){function t(){}return t.refresh=function(){o.ajax(TYPO3.settings.ajaxUrls.topbar).done((function(e){o(t.topbarSelector).html(e.topbar),o(t.topbarSelector).trigger("t3-topbar-update")}))},t.topbarSelector=r.ScaffoldIdentifierEnum.header,t.Toolbar={registerEvent:function(e){o(e),o(t.topbarSelector).on("t3-topbar-update",e)}},t}()}));