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
import $ from"jquery";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";class UploadForm{constructor(){this.expandedUploadFormClass="transformed"}initializeEvents(){$(document).on("click",".t3js-upload",(e=>{const a=$(e.currentTarget),s=$(".extension-upload-form");e.preventDefault(),a.hasClass(this.expandedUploadFormClass)?(s.stop().slideUp(),a.removeClass(this.expandedUploadFormClass)):(a.addClass(this.expandedUploadFormClass),s.stop().slideDown(),new AjaxRequest(a.attr("href")).get().then((async e=>{s.find(".t3js-upload-form-target").html(await e.resolve())})))}))}}export default UploadForm;