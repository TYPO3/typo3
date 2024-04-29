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
import $ from"jquery";import Severity from"@typo3/install/renderable/severity.js";class InfoBox{constructor(){this.template=$('<div class="t3js-infobox callout callout-sm"><div class="callout-title"></div><div class="callout-body"></div></div>')}render(t,l,o){const e=this.template.clone();return e.addClass("callout-"+Severity.getCssClass(t)),l&&e.find(".callout-title").text(l),o?e.find(".callout-body").text(o):e.find(".callout-body").remove(),e}}export default new InfoBox;