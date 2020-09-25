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
define(["require","exports","jquery","./DocumentHeader","TYPO3/CMS/Backend/jquery.clearable"],(function(e,i,t,o){"use strict";return new(function(){function e(){var e=this;t((function(){e.initialize()}))}return e.prototype.initialize=function(){var e=this,i=t("#db_list-searchbox-toolbar");t(".t3js-toggle-search-toolbox").on("click",(function(){i.toggle(),o.reposition(),i.is(":visible")&&t("#search_field").focus()}));var n=t("#search_field"),r=""!==n.val();n.clearable({onClear:function(){r&&t(e).closest("form").submit()}})},e}())}));