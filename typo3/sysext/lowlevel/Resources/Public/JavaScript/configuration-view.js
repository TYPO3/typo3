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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";class ConfigurationView{constructor(){if(this.searchForm=document.querySelector("#ConfigurationView"),this.searchField=this.searchForm.querySelector('input[name="searchString"]'),this.searchResultShown=""!==this.searchField.value,DocumentService.ready().then(()=>{new RegularEvent("search",()=>{""===this.searchField.value&&this.searchResultShown&&this.searchForm.submit()}).bindTo(this.searchField)}),self.location.hash){let e=document.querySelector(self.location.hash);document.querySelector(".list-tree .active ")?e=document.querySelector(".list-tree .active "):e&&e.parentElement.parentElement.classList.add("active"),e&&e.scrollIntoView({block:"center"})}}}export default new ConfigurationView;