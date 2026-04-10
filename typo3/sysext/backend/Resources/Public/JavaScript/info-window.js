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
import{SeverityEnum as m}from"@typo3/backend/enum/severity.js";import e from"@typo3/backend/modal.js";import{topWindow as t}from"@typo3/core/utility/top-window.js";class n{static showItem(i,o){e.advanced({type:e.types.iframe,size:e.sizes.large,content:t.TYPO3.settings.ShowItem.moduleUrl+"&table="+encodeURIComponent(i)+"&uid="+(typeof o=="number"?o:encodeURIComponent(o)),severity:m.notice})}}t.TYPO3.InfoWindow||(t.TYPO3.InfoWindow=n),TYPO3.InfoWindow=n;export{n as default};
