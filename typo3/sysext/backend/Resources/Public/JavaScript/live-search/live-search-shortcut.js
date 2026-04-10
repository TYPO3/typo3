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
import e from"@typo3/backend/hotkeys.js";import r from"@typo3/core/document-service.js";import{topDocument as o}from"@typo3/core/utility/top-window.js";class i{constructor(){r.ready().then(()=>{e.register([e.normalizedCtrlModifierKey,"k"],t=>{t.preventDefault(),o.dispatchEvent(new CustomEvent("typo3:live-search:trigger-open"))},{allowOnEditables:!0})})}}var a=new i;export{a as default};
