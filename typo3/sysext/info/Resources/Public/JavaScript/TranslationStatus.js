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
define(["require","exports","jquery"],function(e,t,n){"use strict";return new class{constructor(){this.registerEvents()}registerEvents(){n('input[type="checkbox"][data-lang]').on("change",this.toggleNewButton)}toggleNewButton(e){const t=n(e.currentTarget),a=parseInt(t.data("lang"),10),s=n(".t3js-language-new-"+a),r=n('input[type="checkbox"][data-lang="'+a+'"]:checked'),g=[];r.each((e,t)=>{g.push("edit[pages]["+t.dataset.uid+"]=new")});const c=s.data("editUrl")+"&"+g.join("&");s.attr("href",c),s.toggleClass("disabled",0===r.length)}}});