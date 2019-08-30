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
define(["require","exports","TYPO3/CMS/Backend/FormEngine/Element/SelectTree"],function(e,t,r){"use strict";return class{constructor(e,t,r){this.treeWrapper=null,this.recordField=null,this.callback=null,this.treeWrapper=document.querySelector("#"+e),this.recordField=document.querySelector("#"+t),this.callback=r,this.initialize()}initialize(){const t=this.generateRequestUrl(),i=new r,a={dataUrl:t,showIcons:!0,showCheckboxes:!0,readOnlyMode:1===parseInt(this.recordField.dataset.readOnly,10),input:this.recordField,exclusiveNodesIdentifiers:this.recordField.dataset.treeExclusiveKeys,validation:JSON.parse(this.recordField.dataset.formengineValidationRules)[0],expandUpToLevel:this.recordField.dataset.treeExpandUpToLevel};i.initialize(this.treeWrapper,a)&&(i.dispatch.on("nodeSelectedAfter.requestUpdate",this.callback),this.recordField.dataset.treeShowToolbar&&e(["TYPO3/CMS/Backend/FormEngine/Element/TreeToolbar"],e=>{(new e).initialize(this.treeWrapper)}))}generateRequestUrl(){const e={tableName:this.recordField.dataset.tablename,fieldName:this.recordField.dataset.fieldname,uid:this.recordField.dataset.uid,recordTypeValue:this.recordField.dataset.recordtypevalue,dataStructureIdentifier:""!==this.recordField.dataset.datastructureidentifier?JSON.parse(this.recordField.dataset.datastructureidentifier):"",flexFormSheetName:this.recordField.dataset.flexformsheetname,flexFormFieldName:this.recordField.dataset.flexformfieldname,flexFormContainerName:this.recordField.dataset.flexformcontainername,flexFormContainerIdentifier:this.recordField.dataset.flexformcontaineridentifier,flexFormContainerFieldName:this.recordField.dataset.flexformcontainerfieldname,flexFormSectionContainerIsNew:this.recordField.dataset.flexformsectioncontainerisnew,command:this.recordField.dataset.command};return TYPO3.settings.ajaxUrls.record_tree_data+"&"+$.param(e)}}});