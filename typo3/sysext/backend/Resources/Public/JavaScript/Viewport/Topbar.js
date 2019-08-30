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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","jquery"],function(e,t,r,o){"use strict";class a{static refresh(){o.ajax(TYPO3.settings.ajaxUrls.topbar).done(e=>{o(a.topbarSelector).html(e.topbar),o(a.topbarSelector).trigger("t3-topbar-update")})}}return a.topbarSelector=r.ScaffoldIdentifierEnum.header,a.Toolbar={registerEvent:e=>{o(e),o(a.topbarSelector).on("t3-topbar-update",e)}},a});