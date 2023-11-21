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
import"@typo3/backend/element/icon-element.js";export default class IconHelper{static getIcon(e,t=""){return e=IconHelper.getIconIdentifier(e),"<typo3-backend-icon "+Object.entries({identifier:e,overlay:t,size:"small"}).filter((([e,t])=>e&&""!==t)).map((([e,t])=>`${e}="${t}"`)).join(" ")+"></typo3-backend-icon>"}static getIconIdentifier(e){switch(e){case"language":e="flags-multiple";break;case"integrity":case"info":e="status-dialog-information";break;case"success":e="status-dialog-ok";break;case"warning":e="status-dialog-warning";break;case"error":e="status-dialog-error"}return e}}