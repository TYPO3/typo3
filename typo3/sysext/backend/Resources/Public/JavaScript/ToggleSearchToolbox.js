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
define(["require","exports","jquery","./DocumentHeader","TYPO3/CMS/Backend/jquery.clearable"],function(a,b,c,d){"use strict";Object.defineProperty(b,"__esModule",{value:!0});(function(){function a(){var a=this;c(function(){a.initialize()})}return a.prototype.initialize=function(){var a=this,b=c("#db_list-searchbox-toolbar");c(".t3js-toggle-search-toolbox").on("click",function(){b.toggle(),d.reposition(),b.is(":visible")&&c("#search_field").focus()});var e=c("#search_field"),f=""!==e.val();e.clearable({onClear:function(){f&&c(a).closest("form").submit()}})},a})()});