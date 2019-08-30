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
define(["require","exports","jquery","bootstrap"],function(t,o,e){"use strict";return new class{constructor(){this.DEFAULT_SELECTOR='[data-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,e(t).popover()}popover(t){t.popover()}setOptions(t,o){const e=(o=o||{}).title||t.data("title")||"",p=o.content||t.data("content")||"";t.attr("data-original-title",e).attr("data-content",p).attr("data-placement","auto").popover(o)}setOption(t,o,e){t.data("bs.popover").options[o]=e}show(t){t.popover("show")}hide(t){t.popover("hide")}destroy(t){t.popover("destroy")}toggle(t){t.popover("toggle")}}});