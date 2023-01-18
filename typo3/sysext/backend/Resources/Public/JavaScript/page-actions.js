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
import $ from"jquery";import PersistentStorage from"@typo3/backend/storage/persistent.js";import"@typo3/backend/element/icon-element.js";var IdentifierEnum;!function(e){e.pageTitle=".t3js-title-inlineedit",e.hiddenElements=".t3js-hidden-record"}(IdentifierEnum||(IdentifierEnum={}));class PageActions{constructor(){this.$showHiddenElementsCheckbox=null,$((()=>{this.initializeElements(),this.initializeEvents()}))}initializeElements(){this.$showHiddenElementsCheckbox=$("#checkShowHidden")}initializeEvents(){this.$showHiddenElementsCheckbox.on("change",this.toggleContentElementVisibility)}toggleContentElementVisibility(e){const i=$(e.currentTarget),t=$(IdentifierEnum.hiddenElements),n=$('<span class="form-check-spinner"><typo3-backend-icon identifier="spinner-circle" size="small"></typo3-backend-icon></span>');i.hide().after(n),i.prop("checked")?t.slideDown():t.slideUp(),PersistentStorage.set("moduleData.web_layout.showHidden",i.prop("checked")?"1":"0").then((()=>{n.remove(),i.show()}))}}export default new PageActions;