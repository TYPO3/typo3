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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./ElementBrowser"],(function(t,e,r,a){"use strict";r=__importDefault(r);return new class{constructor(){r.default(()=>{r.default("[data-close]").on("click",t=>{t.preventDefault();const e=r.default(t.currentTarget).parents("span").data();a.insertElement(e.table,e.uid,"db",e.title,"","",e.icon,"",1===parseInt(r.default(t.currentTarget).data("close"),10))})});const t=document.getElementById("db_list-searchbox-toolbar");t.style.display="block",t.style.position="relative"}}}));