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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],function(e,t,n,c){"use strict";var l,r;return(r=l||(l={})).toggleAll=".t3js-toggle-checkboxes",r.singleItem=".t3js-checkbox",function(){function e(e){var t=this;this.checkBoxId="",this.$table=null,this.checkBoxId=e,n(function(){t.$table=n("#"+e).closest("table"),t.enableTriggerCheckBox(),t.registerEventHandler()})}return e.allCheckBoxesAreChecked=function(e){return e.length===e.filter(":checked").length},e.prototype.registerEventHandler=function(){var t=this;n(this.$table).on("change",l.toggleAll,function(r){var i=n(r.currentTarget),o=t.$table.find(l.singleItem),h=!e.allCheckBoxesAreChecked(o);o.prop("checked",h),i.prop("checked",h),c.Validation.markFieldAsChanged(i)}).on("change",l.singleItem,function(){var n=t.$table.find(l.singleItem),c=e.allCheckBoxesAreChecked(n);t.$table.find(l.toggleAll).prop("checked",c)})},e.prototype.enableTriggerCheckBox=function(){var t=this.$table.find(l.singleItem),c=e.allCheckBoxesAreChecked(t);n("#"+this.checkBoxId).prop("checked",c)},e}()});