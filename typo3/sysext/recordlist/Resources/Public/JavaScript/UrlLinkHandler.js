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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./LinkBrowser"],(function(t,e,r,u){"use strict";r=__importDefault(r);return new class{constructor(){this.link=t=>{t.preventDefault();const e=r.default(t.currentTarget).find('[name="lurl"]').val();""!==e&&u.finalizeFunction(e)},r.default(()=>{r.default("#lurlform").on("submit",this.link)})}}}));