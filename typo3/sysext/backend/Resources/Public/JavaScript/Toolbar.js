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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./ModuleMenu"],(function(e,t,a,l){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),a=__importDefault(a);class o{static initialize(){o.initializeEvents()}static initializeEvents(){a.default(document).on("click",".toolbar-item [data-modulename]",e=>{e.preventDefault();const t=a.default(e.target).closest("[data-modulename]").data("modulename");l.App.showModule(t)}),a.default(document).on("click",".t3js-topbar-button-toolbar",()=>{a.default(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-search-expanded").toggleClass("scaffold-toolbar-expanded")}),a.default(document).on("click",".t3js-topbar-button-search",()=>{a.default(".scaffold").removeClass("scaffold-modulemenu-expanded").removeClass("scaffold-toolbar-expanded").toggleClass("scaffold-search-expanded")})}}a.default(()=>{o.initialize()})}));