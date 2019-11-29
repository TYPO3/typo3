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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","jquery","./Toolbar"],(function(r,e,t,o,a){"use strict";class i{constructor(){this.Toolbar=new a}refresh(){o.ajax(TYPO3.settings.ajaxUrls.topbar).done(r=>{o(i.topbarSelector).html(r.topbar),o(i.topbarSelector).trigger("t3-topbar-update")})}}return i.topbarSelector=t.ScaffoldIdentifierEnum.header,i}));