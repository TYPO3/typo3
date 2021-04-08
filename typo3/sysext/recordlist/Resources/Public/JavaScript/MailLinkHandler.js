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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","./LinkBrowser","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,r,u){"use strict";u=__importDefault(u);return new class{constructor(){new u.default("submit",(e,t)=>{e.preventDefault();let u=t.querySelector('[name="lemail"]').value;if("mailto:"!==u){for(;"mailto:"===u.substr(0,7);)u=u.substr(7);r.finalizeFunction("mailto:"+u)}}).delegateTo(document,"#lmailform")}}}));