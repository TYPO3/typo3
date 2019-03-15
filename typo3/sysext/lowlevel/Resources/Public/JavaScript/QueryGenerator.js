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
define(["require","exports","jquery","TYPO3/CMS/Backend/jquery.clearable"],function(t,i,e){"use strict";return new(function(){function t(){this.form=null,this.limitField=null,this.initialize()}return t.prototype.initialize=function(){var t=this;this.form=e('form[name="queryform"]'),this.limitField=e("#queryLimit"),this.form.on("click",".t3js-submit-click",function(i){i.preventDefault(),t.doSubmit()}),this.form.on("change",".t3js-submit-change",function(i){i.preventDefault(),t.doSubmit()}),this.form.on("click",'.t3js-limit-submit input[type="button"]',function(i){i.preventDefault(),t.setLimit(e(i.currentTarget).data("value")),t.doSubmit()}),this.form.on("click",".t3js-addfield",function(i){i.preventDefault();var n=e(i.currentTarget);t.addValueToField(n.data("field"),n.val())}),this.form.find(".t3js-clearable").clearable({onClear:function(){t.doSubmit()}})},t.prototype.doSubmit=function(){this.form.submit()},t.prototype.setLimit=function(t){this.limitField.val(t)},t.prototype.addValueToField=function(t,i){var e=this.form.find('[name="'+t+'"]'),n=e.val();e.val(n+","+i)},t}())});