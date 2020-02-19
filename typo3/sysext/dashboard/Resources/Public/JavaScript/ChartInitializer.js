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
define(["require","exports","jquery"],(function(e,t,n){"use strict";let r=e("TYPO3/CMS/Dashboard/Contrib/chartjs");return new class{constructor(){this.selector=".dashboard-item--chart",n(()=>{this.initialize()})}initialize(){n(document).on("widgetContentRendered",this.selector,(e,t)=>{e.preventDefault();const i=n(e.currentTarget);if("undefined"===t.graphConfig)return;let s,o=i.find("canvas:first");o.length>0&&(s=o[0].getContext("2d")),"undefined"!==s&&new r(s,t.graphConfig)})}}}));