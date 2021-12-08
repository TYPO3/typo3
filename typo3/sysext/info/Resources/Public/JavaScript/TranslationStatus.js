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
import $ from"jquery";class TranslationStatus{constructor(){this.registerEvents()}registerEvents(){$('input[type="checkbox"][data-lang]').on("change",this.toggleNewButton)}toggleNewButton(t){const e=$(t.currentTarget),a=parseInt(e.data("lang"),10),n=$(".t3js-language-new-"+a),s=$('input[type="checkbox"][data-lang="'+a+'"]:checked'),o=[];s.each((t,e)=>{o.push("cmd[pages]["+e.dataset.uid+"][localize]="+a)});const r=n.data("editUrl")+"&"+o.join("&");n.attr("href",r),n.toggleClass("disabled",0===s.length)}}export default new TranslationStatus;