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
import t from"@typo3/core/document-service.js";import o from"@typo3/core/event/regular-event.js";class a{constructor(){t.ready().then(()=>{const e=document.getElementById("search_field");if(e!==null){const r=e.value!=="";new o("search",()=>{e.value===""&&r&&e.closest("form").requestSubmit()}).bindTo(e)}})}}var n=new a;export{n as default};
