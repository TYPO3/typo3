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
define(["require","exports","jquery","twbs/bootstrap-slider"],function(e,t,a){"use strict";return new(function(){function e(){this.selector="[data-slider-id]",this.initializeSlider()}return e.updateValue=function(e){var t=a(e.currentTarget),i=a('[data-formengine-input-name="'+t.data("sliderItemName")+'"]'),r=t.data("sliderCallbackParams");i.val(e.value.newValue),TBE_EDITOR.fieldChanged.apply(TBE_EDITOR,r)},e.prototype.initializeSlider=function(){var t=a(this.selector);t.length>0&&(t.slider({formatter:this.renderTooltipValue}),t.on("change",e.updateValue))},e.prototype.renderTooltipValue=function(e){var t;switch(a('[data-slider-id="'+this.id+'"]').data().sliderValueType){case"double":t=parseFloat(e).toFixed(2);break;case"int":default:t=parseInt(e,10)}return t},e}())});