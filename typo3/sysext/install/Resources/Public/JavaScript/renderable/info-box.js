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
import $ from"jquery";import Severity from"@typo3/install/renderable/severity.js";class InfoBox{constructor(){this.template=$('<div class="t3js-infobox callout callout-sm"><h4 class="callout-title"></h4><div class="callout-body"></div></div>')}render(t,l,e){let o=this.template.clone();return o.addClass("callout-"+Severity.getCssClass(t)),l&&o.find("h4").text(l),e?o.find(".callout-body").text(e):o.find(".callout-body").remove(),o}}export default new InfoBox;