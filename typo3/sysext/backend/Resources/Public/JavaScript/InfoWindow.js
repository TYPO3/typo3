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
define(["require","exports","./Enum/Severity","./Modal"],(function(e,n,t,o){"use strict";var i=function(){function e(){}return e.showItem=function(e,n){o.advanced({type:o.types.iframe,size:o.sizes.large,content:top.TYPO3.settings.ShowItem.moduleUrl+"&table="+encodeURIComponent(e)+"&uid="+("number"==typeof n?n:encodeURIComponent(n)),severity:t.SeverityEnum.notice})},e}();return TYPO3.InfoWindow=i,i}));