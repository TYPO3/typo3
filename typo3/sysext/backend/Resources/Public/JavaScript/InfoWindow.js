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
define(["require","exports","TYPO3/CMS/Backend/Modal","./Severity"],function(a,b,c,d){"use strict";var e=function(){function a(){}return a.showItem=function(a,b){c.advanced({type:c.types.iframe,size:c.sizes.large,content:TYPO3.settings.ShowItem.moduleUrl+"&table="+encodeURIComponent(a)+"&uid="+("number"==typeof b?b:encodeURIComponent(b)),severity:d.notice})},a}();return TYPO3.InfoWindow=e,e});