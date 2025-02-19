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
import"@typo3/backend/element/icon-element.js";class e{static getIcon(a,o=""){return a=e.getIconIdentifier(a),"<typo3-backend-icon "+Object.entries({identifier:a,overlay:o,size:"small"}).filter(([s,t])=>s&&t!=="").map(([s,t])=>`${s}="${t}"`).join(" ")+"></typo3-backend-icon>"}static getIconIdentifier(a){switch(a){case"language":a="flags-multiple";break;case"integrity":case"info":a="status-dialog-information";break;case"success":a="status-dialog-ok";break;case"warning":a="status-dialog-warning";break;case"error":a="status-dialog-error";break;default:}return a}}export{e as default};
