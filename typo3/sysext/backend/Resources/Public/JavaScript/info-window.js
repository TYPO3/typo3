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
import{SeverityEnum as i}from"@typo3/backend/enum/severity.js";import e from"@typo3/backend/modal.js";class t{static showItem(n,o){e.advanced({type:e.types.iframe,size:e.sizes.large,content:top.TYPO3.settings.ShowItem.moduleUrl+"&table="+encodeURIComponent(n)+"&uid="+(typeof o=="number"?o:encodeURIComponent(o)),severity:i.notice})}}top.TYPO3.InfoWindow||(top.TYPO3.InfoWindow=t),TYPO3.InfoWindow=t;export{t as default};
