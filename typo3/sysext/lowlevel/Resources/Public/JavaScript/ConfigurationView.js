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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,r,s){"use strict";return new class{constructor(){if(this.searchForm=document.querySelector("#ConfigurationView"),this.searchField=this.searchForm.querySelector('input[name="searchString"]'),this.searchResultShown=""!==this.searchField.value,r.ready().then(()=>{new s("search",()=>{""===this.searchField.value&&this.searchResultShown&&this.searchForm.submit()}).bindTo(this.searchField)}),self.location.hash){let e=document.querySelector(self.location.hash);document.querySelector(".list-tree .active ")?e=document.querySelector(".list-tree .active "):e&&e.parentElement.parentElement.classList.add("active"),e&&e.scrollIntoView({block:"center"})}}}}));