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
import $ from"jquery";import Severity from"@typo3/install/renderable/severity.js";class InfoBox{constructor(){this.template=$('<div class="t3js-infobox callout callout-sm"><h4 class="callout-title"></h4><div class="callout-body"></div></div>')}render(t,o,l){const e=this.template.clone();return e.addClass("callout-"+Severity.getCssClass(t)),o&&e.find("h4").text(o),l?e.find(".callout-body").text(l):e.find(".callout-body").remove(),e}}export default new InfoBox;