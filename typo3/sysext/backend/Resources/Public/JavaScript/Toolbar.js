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
define(["require","exports","jquery","./ModuleMenu"],function(e,o,a,n){"use strict";Object.defineProperty(o,"__esModule",{value:!0});var t=function(){function e(){}return e.initialize=function(){e.initializeEvents()},e.initializeEvents=function(){a(".t3js-toolbar-item").parent().on("hidden.bs.dropdown",function(){a(".scaffold").removeClass("scaffold-toolbar-expanded").removeClass("scaffold-search-expanded")}),a(document).on("click",".toolbar-item [data-modulename]",function(e){e.preventDefault();var o=a(e.target).closest("[data-modulename]").data("modulename");n.App.showModule(o)}),a(document).on("click",".t3js-topbar-button-toolbar",function(){a(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-search-expanded").toggleClass("scaffold-toolbar-expanded")}),a(document).on("click",".t3js-topbar-button-search",function(){a(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-toolbar-expanded").toggleClass("scaffold-search-expanded")})},e}();a(function(){t.initialize()})});