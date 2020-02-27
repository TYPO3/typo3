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
define(["require","exports","TYPO3/CMS/Dashboard/Contrib/chartjs","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,i){"use strict";return new class{constructor(){this.selector=".dashboard-item--chart",this.initialize()}initialize(){new i("widgetContentRendered",(function(e){e.preventDefault();const t=e.detail;if("undefined"===t.graphConfig)return;let i,r=this.querySelector("canvas");null!==r&&(i=r.getContext("2d")),"undefined"!==i&&new n(i,t.graphConfig)})).delegateTo(document,this.selector)}}}));