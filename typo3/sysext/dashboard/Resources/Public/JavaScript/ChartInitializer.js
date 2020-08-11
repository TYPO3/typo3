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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","TYPO3/CMS/Dashboard/Contrib/chartjs","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,i,n){"use strict";i=__importDefault(i);return new class{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){new n("widgetContentRendered",(function(e){e.preventDefault();const t=e.detail;if(void 0===t||void 0===t.graphConfig)return;let n,r=this.querySelector("canvas");null!==r&&(n=r.getContext("2d")),void 0!==n&&new i.default(n,t.graphConfig)})).delegateTo(document,this.selector)}}}));