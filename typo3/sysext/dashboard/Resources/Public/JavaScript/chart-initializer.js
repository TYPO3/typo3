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
import Chart from"@typo3/dashboard/contrib/chartjs.js";import RegularEvent from"@typo3/core/event/regular-event.js";class ChartInitializer{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){new RegularEvent("widgetContentRendered",(function(t){t.preventDefault();const e=t.detail;if(void 0===e||void 0===e.graphConfig)return;let r,i=this.querySelector("canvas");null!==i&&(r=i.getContext("2d")),void 0!==r&&new Chart(r,e.graphConfig)})).delegateTo(document,this.selector)}}export default new ChartInitializer;