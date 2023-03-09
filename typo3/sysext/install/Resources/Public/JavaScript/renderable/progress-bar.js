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
import $ from"jquery";import Severity from"@typo3/install/renderable/severity.js";class ProgressBar{constructor(){this.template=$('<div class="progress"><div class="t3js-progressbar progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"> <span></span></div></div>')}render(r,s,e){const a=this.template.clone();return a.addClass("progress-bar-"+Severity.getCssClass(r)),e&&(a.css("width",e+"%"),a.attr("aria-valuenow",e)),s&&a.find("span").text(s),a}}export default new ProgressBar;