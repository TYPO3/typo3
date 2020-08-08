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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Backend/Input/Clearable"],(function(e,t,r){"use strict";r=__importDefault(r);return new class{constructor(){this.searchField=document.querySelector('input[name="searchString"]'),this.searchResultShown=""!==this.searchField.value,this.searchField.clearable({onClear:e=>{this.searchResultShown&&e.closest("form").submit()}}),self.location.hash&&r.default("html, body").scrollTop((document.documentElement.scrollTop||document.body.scrollTop)-80)}}}));