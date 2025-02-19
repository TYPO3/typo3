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
import{ScaffoldIdentifierEnum as r}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import t from"@typo3/core/document-service.js";import o from"@typo3/core/event/regular-event.js";class m{registerEvent(e){t.ready().then(()=>{e()}),new o("t3-topbar-update",e).bindTo(document.querySelector(r.header))}}export{m as default};
