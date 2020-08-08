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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","../../Modal"],(function(e,t,n,l,o){"use strict";n=__importDefault(n);return class{constructor(e){this.controlElement=null,this.handleControlClick=e=>{e.preventDefault();const t=this.controlElement.dataset.itemName,n=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(document.editform[t].value)+"&P[currentSelectedValues]="+encodeURIComponent(l.getFieldElement(t).val());o.advanced({type:o.types.iframe,content:n,size:o.sizes.large})},n.default(()=>{this.controlElement=document.querySelector(e),this.controlElement.addEventListener("click",this.handleControlClick)})}}}));