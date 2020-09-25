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
define(["require","exports","jquery"],(function(t,e,n){"use strict";return new(function(){function t(){this.registerEvents()}return t.prototype.registerEvents=function(){n('input[type="checkbox"][data-lang]').on("change",this.toggleNewButton)},t.prototype.toggleNewButton=function(t){var e=n(t.currentTarget),a=parseInt(e.data("lang"),10),r=n(".t3js-language-new-"+a),o=n('input[type="checkbox"][data-lang="'+a+'"]:checked'),i=[];o.each((function(t,e){i.push("cmd[pages]["+e.dataset.uid+"][localize]="+a)}));var u=r.data("editUrl")+"&"+i.join("&");r.attr("href",u),r.toggleClass("disabled",0===o.length)},t}())}));