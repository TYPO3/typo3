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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","bootstrap"],(function(t,e,i){"use strict";i=__importDefault(i);const o=new class{constructor(){i.default(()=>{this.initialize('[data-bs-toggle="tooltip"]')})}initialize(t,e){(e=e||{}).title=e.title||"",i.default(t).tooltip(e)}show(t,e){i.default(t).attr("data-bs-placement","auto").attr("data-title",e).tooltip("show")}hide(t){i.default(t).tooltip("hide")}};return TYPO3.Tooltip=o,o}));