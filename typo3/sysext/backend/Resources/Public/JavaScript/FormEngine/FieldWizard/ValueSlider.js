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
define(["require","exports","TYPO3/CMS/Core/Event/ThrottleEvent"],(function(e,t,a){"use strict";class n{constructor(e){this.controlElement=null,this.handleRangeChange=e=>{const t=e.target;n.updateValue(t),n.updateTooltipValue(t)},this.controlElement=document.getElementById(e),new a("input",this.handleRangeChange,25).bindTo(this.controlElement)}static updateValue(e){const t=document.querySelector(`[data-formengine-input-name="${e.dataset.sliderItemName}"]`),a=JSON.parse(e.dataset.sliderCallbackParams);t.value=e.value,TBE_EDITOR.fieldChanged.apply(TBE_EDITOR,a)}static updateTooltipValue(e){let t;const a=e.value;switch(e.dataset.sliderValueType){case"double":t=parseFloat(a).toFixed(2);break;case"int":default:t=parseInt(a,10)}e.title=t.toString()}}return n}));