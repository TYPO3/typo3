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
import{SeverityEnum}from"@typo3/backend/enum/severity.js";import Modal from"@typo3/backend/modal.js";class InfoWindow{static showItem(o,e){Modal.advanced({type:Modal.types.iframe,size:Modal.sizes.large,content:top.TYPO3.settings.ShowItem.moduleUrl+"&table="+encodeURIComponent(o)+"&uid="+("number"==typeof e?e:encodeURIComponent(e)),severity:SeverityEnum.notice})}}top.TYPO3.InfoWindow||(top.TYPO3.InfoWindow=InfoWindow),TYPO3.InfoWindow=InfoWindow;export default InfoWindow;