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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./DocumentHeader","TYPO3/CMS/Backend/Input/Clearable"],(function(e,t,l,i){"use strict";l=__importDefault(l);return new class{constructor(){l.default(()=>{this.initialize()})}initialize(){const e=l.default("#db_list-searchbox-toolbar");let t;if(l.default(".t3js-toggle-search-toolbox").on("click",()=>{e.toggle(),i.reposition(),e.is(":visible")&&l.default("#search_field").focus()}),null!==(t=document.getElementById("search_field"))){const e=""!==t.value;t.clearable({onClear:t=>{e&&t.closest("form").submit()}})}}}}));