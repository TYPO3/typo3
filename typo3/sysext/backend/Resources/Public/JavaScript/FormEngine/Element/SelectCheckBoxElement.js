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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],function(e,t,n,c){"use strict";var l,i;return(i=l||(l={})).toggleAll=".t3js-toggle-checkboxes",i.singleItem=".t3js-checkbox",i.revertSelection=".t3js-revert-selection",function(){function e(e){var t=this;this.checkBoxId="",this.$table=null,this.checkedBoxes=null,this.checkBoxId=e,n(function(){t.$table=n("#"+e).closest("table"),t.checkedBoxes=t.$table.find(l.singleItem+":checked"),t.enableTriggerCheckBox(),t.registerEventHandler()})}return e.allCheckBoxesAreChecked=function(e){return e.length===e.filter(":checked").length},e.prototype.registerEventHandler=function(){var t=this;this.$table.on("change",l.toggleAll,function(i){var o=n(i.currentTarget),r=t.$table.find(l.singleItem),h=!e.allCheckBoxesAreChecked(r);r.prop("checked",h),o.prop("checked",h),c.Validation.markFieldAsChanged(o)}).on("change",l.singleItem,function(){t.setToggleAllState()}).on("click",l.revertSelection,function(){t.$table.find(l.singleItem).each(function(e,n){n.checked=t.checkedBoxes.index(n)>-1}),t.setToggleAllState()})},e.prototype.setToggleAllState=function(){var t=this.$table.find(l.singleItem),n=e.allCheckBoxesAreChecked(t);this.$table.find(l.toggleAll).prop("checked",n)},e.prototype.enableTriggerCheckBox=function(){var t=this.$table.find(l.singleItem),c=e.allCheckBoxesAreChecked(t);n("#"+this.checkBoxId).prop("checked",c)},e}()});