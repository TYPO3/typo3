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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","./LinkBrowser","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,r,l){"use strict";l=__importDefault(l);return new class{constructor(){new l.default("submit",(e,t)=>{e.preventDefault();let l=t.querySelector('[name="ltelephone"]').value;"tel:"!==l&&(l.startsWith("tel:")&&(l=l.substr(4)),r.finalizeFunction("tel:"+l))}).delegateTo(document,"#ltelephoneform")}}}));