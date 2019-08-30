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
define(["require","exports","jquery","twbs/bootstrap-slider"],function(e,t,a){"use strict";class i{constructor(){this.selector="[data-slider-id]",this.initializeSlider()}static updateValue(e){const t=a(e.currentTarget),i=a('[data-formengine-input-name="'+t.data("sliderItemName")+'"]'),r=t.data("sliderCallbackParams");i.val(e.value.newValue),TBE_EDITOR.fieldChanged.apply(TBE_EDITOR,r)}initializeSlider(){const e=a(this.selector);e.length>0&&(e.slider({formatter:this.renderTooltipValue}),e.on("change",i.updateValue))}renderTooltipValue(e){let t;switch(a('[data-slider-id="'+this.id+'"]').data().sliderValueType){case"double":t=parseFloat(e).toFixed(2);break;case"int":default:t=parseInt(e,10)}return t}}return new i});