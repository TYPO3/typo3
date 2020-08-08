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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./ModuleMenu"],(function(e,a,t,o){"use strict";Object.defineProperty(a,"__esModule",{value:!0});class l{static initialize(){l.initializeEvents()}static initializeEvents(){t.default(".t3js-toolbar-item").parent().on("hidden.bs.dropdown",()=>{t.default(".scaffold").removeClass("scaffold-toolbar-expanded").removeClass("scaffold-search-expanded")}),t.default(document).on("click",".toolbar-item [data-modulename]",e=>{e.preventDefault();const a=t.default(e.target).closest("[data-modulename]").data("modulename");o.App.showModule(a)}),t.default(document).on("click",".t3js-topbar-button-toolbar",()=>{t.default(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-search-expanded").toggleClass("scaffold-toolbar-expanded")}),t.default(document).on("click",".t3js-topbar-button-search",()=>{t.default(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-toolbar-expanded").toggleClass("scaffold-search-expanded")})}}(t=__importDefault(t)).default(()=>{l.initialize()})}));