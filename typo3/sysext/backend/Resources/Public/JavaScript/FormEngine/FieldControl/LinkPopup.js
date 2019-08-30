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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","../../Modal"],function(e,t,n,o,l){"use strict";return class{constructor(e){this.controlElement=null,this.handleControlClick=(e=>{e.preventDefault();const t=this.controlElement.dataset.itemName,n=this.controlElement.getAttribute("href")+"&P[currentValue]="+encodeURIComponent(document.editform[t].value)+"&P[currentSelectedValues]="+encodeURIComponent(o.getFieldElement(t).val());l.advanced({type:l.types.iframe,content:n,size:l.sizes.large})}),n(()=>{this.controlElement=document.querySelector(e),this.controlElement.addEventListener("click",this.handleControlClick)})}}});