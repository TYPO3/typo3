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
define(["require","exports","jquery","twbs/bootstrap-slider"],(function(e,t,a){"use strict";return function(){function e(e){var t=this;this.controlElement=null,this.renderTooltipValue=function(e){var n;switch(a(t.controlElement).data().sliderValueType){case"double":n=parseFloat(e).toFixed(2);break;case"int":default:n=parseInt(e,10).toString()}return n},this.controlElement=document.getElementById(e),this.initializeSlider()}return e.updateValue=function(e){var t=a(e.currentTarget),n=a('[data-formengine-input-name="'+t.data("sliderItemName")+'"]'),r=t.data("sliderCallbackParams");n.val(e.value.newValue),TBE_EDITOR.fieldChanged.apply(TBE_EDITOR,r)},e.prototype.initializeSlider=function(){var t=a(this.controlElement);t.slider({formatter:this.renderTooltipValue}),t.on("change",e.updateValue)},e}()}));