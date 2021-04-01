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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,i){"use strict";i=__importDefault(i);return new class{constructor(){i.default(()=>{this.initialize()})}initialize(){const t=i.default("#db_list-searchbox-toolbar");i.default(".t3js-toggle-search-toolbox").on("click",()=>{t.toggle(),t.is(":visible")&&i.default("#search_field").focus()})}}}));