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
define(["require","exports","jquery"],function(e,o,n){"use strict";Object.defineProperty(o,"__esModule",{value:!0});var a=function(){function o(){}return o.initialize=function(){o.initializeEvents()},o.initializeEvents=function(){n(".t3js-toolbar-item").parent().on("hidden.bs.dropdown",function(){n(".scaffold").removeClass("scaffold-toolbar-expanded").removeClass("scaffold-search-expanded")}),n(document).on("click",".toolbar-item [data-modulename]",function(o){o.preventDefault(),e(["TYPO3/CMS/Backend/ModuleMenu"],function(){var e=n(o.target).closest("[data-modulename]").data("modulename");TYPO3.ModuleMenu.App.showModule(e)})}),n(document).on("click",".t3js-topbar-button-toolbar",function(){n(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-search-expanded").toggleClass("scaffold-toolbar-expanded")}),n(document).on("click",".t3js-topbar-button-search",function(){n(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-toolbar-expanded").toggleClass("scaffold-search-expanded")})},o}();n(function(){a.initialize()})});