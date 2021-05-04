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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,r,t,s){"use strict";return new class{constructor(){this.searchForm=document.querySelector("#ConfigurationView"),this.searchField=this.searchForm.querySelector('input[name="searchString"]'),this.searchResultShown=""!==this.searchField.value,t.ready().then(()=>{new s("search",()=>{""===this.searchField.value&&this.searchResultShown&&this.searchForm.submit()}).bindTo(this.searchField)}),self.location.hash&&$("html, body").scrollTop((document.documentElement.scrollTop||document.body.scrollTop)-80)}}}));