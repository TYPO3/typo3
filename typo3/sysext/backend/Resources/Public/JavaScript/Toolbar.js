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
define(["require","exports","jquery","./ModuleMenu"],(function(e,a,o,s){"use strict";Object.defineProperty(a,"__esModule",{value:!0});class t{static initialize(){t.initializeEvents()}static initializeEvents(){o(".t3js-toolbar-item").parent().on("hidden.bs.dropdown",()=>{o(".scaffold").removeClass("scaffold-toolbar-expanded").removeClass("scaffold-search-expanded")}),o(document).on("click",".toolbar-item [data-modulename]",e=>{e.preventDefault();const a=o(e.target).closest("[data-modulename]").data("modulename");s.App.showModule(a)}),o(document).on("click",".t3js-topbar-button-toolbar",()=>{o(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-search-expanded").toggleClass("scaffold-toolbar-expanded")}),o(document).on("click",".t3js-topbar-button-search",()=>{o(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-toolbar-expanded").toggleClass("scaffold-search-expanded")})}}o(()=>{t.initialize()})}));