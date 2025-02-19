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
import n from"@typo3/backend/link-browser.js";import r from"@typo3/core/event/regular-event.js";class o{constructor(){new r("submit",(t,l)=>{t.preventDefault();let e=l.querySelector('[name="ltelephone"]').value;e!=="tel:"&&(e.startsWith("tel:")&&(e=e.substr(4)),n.finalizeFunction("tel:"+e))}).delegateTo(document,"#ltelephoneform")}}var i=new o;export{i as default};
