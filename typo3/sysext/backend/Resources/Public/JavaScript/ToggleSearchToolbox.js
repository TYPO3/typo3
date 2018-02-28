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
define(["require","exports","jquery","./DocumentHeader","TYPO3/CMS/Backend/jquery.clearable"],function(e,i,o,t){"use strict";Object.defineProperty(i,"__esModule",{value:!0});!function(){function e(){var e=this;o(function(){e.initialize()})}e.prototype.initialize=function(){var e=this,i=o("#db_list-searchbox-toolbar");o(".t3js-toggle-search-toolbox").on("click",function(){i.toggle(),t.reposition(),i.is(":visible")&&o("#search_field").focus()});var n=o("#search_field"),r=""!==n.val();n.clearable({onClear:function(){r&&o(e).closest("form").submit()}})}}()});