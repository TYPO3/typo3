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
define(["require","exports","jquery"],function(a,b,c){"use strict";Object.defineProperty(b,"__esModule",{value:!0});var d=function(){function b(){}return b.initialize=function(){b.initializeEvents()},b.initializeEvents=function(){c(".t3js-toolbar-item").parent().on("hidden.bs.dropdown",function(){c(".scaffold").removeClass("scaffold-toolbar-expanded").removeClass("scaffold-search-expanded")}),c(document).on("click",".toolbar-item [data-modulename]",function(b){b.preventDefault(),a(["TYPO3/CMS/Backend/ModuleMenu"],function(){var a=c(b.target).closest("[data-modulename]").data("modulename");TYPO3.ModuleMenu.App.showModule(a)})}),c(document).on("click",".t3js-topbar-button-toolbar",function(){c(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-search-expanded").toggleClass("scaffold-toolbar-expanded")}),c(document).on("click",".t3js-topbar-button-search",function(){c(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-toolbar-expanded").toggleClass("scaffold-search-expanded")})},b}();c(function(){d.initialize()})});