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
define(["require","exports","jquery"],(function(e,t,a){"use strict";return new class{constructor(){this.registerEvents()}registerEvents(){a('input[type="checkbox"][data-lang]').on("change",this.toggleNewButton)}toggleNewButton(e){const t=a(e.currentTarget),n=parseInt(t.data("lang"),10),s=a(".t3js-language-new-"+n),r=a('input[type="checkbox"][data-lang="'+n+'"]:checked'),c=[];r.each((e,t)=>{c.push("cmd[pages]["+t.dataset.uid+"][localize]="+n)});const g=s.data("editUrl")+"&"+c.join("&");s.attr("href",g),s.toggleClass("disabled",0===r.length)}}}));