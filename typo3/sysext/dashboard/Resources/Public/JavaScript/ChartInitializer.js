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
var __importStar=this&&this.__importStar||function(t){if(t&&t.__esModule)return t;var e={};if(null!=t)for(var r in t)Object.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e.default=t,e};define(["require","exports","TYPO3/CMS/Dashboard/Contrib/chartjs","TYPO3/CMS/Core/Event/RegularEvent"],(function(t,e,r,i){"use strict";r=__importStar(r);return new class{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){new i("widgetContentRendered",(function(t){t.preventDefault();const e=t.detail;if(void 0===e||void 0===e.graphConfig)return;let i,n=this.querySelector("canvas");null!==n&&(i=n.getContext("2d")),void 0!==i&&new r(i,e.graphConfig)})).delegateTo(document,this.selector)}}}));