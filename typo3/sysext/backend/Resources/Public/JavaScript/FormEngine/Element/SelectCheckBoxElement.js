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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],(function(e,t,l,c){"use strict";var s;!function(e){e.toggleAll=".t3js-toggle-checkboxes",e.singleItem=".t3js-checkbox",e.revertSelection=".t3js-revert-selection"}(s||(s={}));class i{constructor(e){this.checkBoxId="",this.$table=null,this.checkedBoxes=null,this.checkBoxId=e,l(()=>{this.$table=l("#"+e).closest("table"),this.checkedBoxes=this.$table.find(s.singleItem+":checked"),this.enableTriggerCheckBox(),this.registerEventHandler()})}static allCheckBoxesAreChecked(e){return e.length===e.filter(":checked").length}registerEventHandler(){this.$table.on("change",s.toggleAll,e=>{const t=l(e.currentTarget),h=this.$table.find(s.singleItem),n=!i.allCheckBoxesAreChecked(h);h.prop("checked",n),t.prop("checked",n),c.Validation.markFieldAsChanged(t)}).on("change",s.singleItem,()=>{this.setToggleAllState()}).on("click",s.revertSelection,()=>{this.$table.find(s.singleItem).each((e,t)=>{t.checked=this.checkedBoxes.index(t)>-1}),this.setToggleAllState()})}setToggleAllState(){const e=this.$table.find(s.singleItem),t=i.allCheckBoxesAreChecked(e);this.$table.find(s.toggleAll).prop("checked",t)}enableTriggerCheckBox(){const e=this.$table.find(s.singleItem),t=i.allCheckBoxesAreChecked(e);l("#"+this.checkBoxId).prop("checked",t)}}return i}));