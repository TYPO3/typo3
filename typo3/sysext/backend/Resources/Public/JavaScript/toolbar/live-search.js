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
import $ from"jquery";import Viewport from"@typo3/backend/viewport.js";import Icons from"@typo3/backend/icons.js";import"jquery/autocomplete.js";import"@typo3/backend/input/clearable.js";import{html}from"lit";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{renderHTML}from"@typo3/core/lit-helper.js";var Identifiers;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem",e.toolbarItem=".t3js-toolbar-item-search",e.dropdownToggle=".t3js-toolbar-search-dropdowntoggle",e.searchFieldSelector=".t3js-topbar-navigation-search-field",e.formSelector=".t3js-topbar-navigation-search",e.dropdownClass="toolbar-item-search-field-dropdown"}(Identifiers||(Identifiers={}));class LiveSearch{constructor(){this.url=TYPO3.settings.ajaxUrls.livesearch,Viewport.Topbar.Toolbar.registerEvent(()=>{let e;this.registerAutocomplete(),this.registerEvents(),$(Identifiers.toolbarItem).removeAttr("style"),null!==(e=document.querySelector(Identifiers.searchFieldSelector))&&e.clearable()})}registerAutocomplete(){const e=$(Identifiers.searchFieldSelector);$(Identifiers.searchFieldSelector).autocomplete({serviceUrl:this.url,paramName:"q",dataType:"json",minChars:2,width:"100%",groupBy:"typeLabel",tabDisabled:!0,noCache:!0,containerClass:Identifiers.toolbarItem.substr(1,Identifiers.toolbarItem.length)+" dropdown-menu "+Identifiers.dropdownClass,forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,triggerSelectOnValidInput:!1,preventBadQueries:!1,noSuggestionNotice:'<h3 class="dropdown-headline">'+TYPO3.lang.liveSearch_listEmptyText+"</h3><p>"+TYPO3.lang.liveSearch_helpTitle+"</p><hr><p>"+TYPO3.lang.liveSearch_helpDescription+"<br>"+TYPO3.lang.liveSearch_helpDescriptionPages+"</p>",transformResult:e=>{let t=$.map(e,e=>({value:e.title,data:e}));if(t.length>0){let e={value:"search_all",data:{typeLabel:"",title:TYPO3.lang.liveSearch_showAllResults,editLink:"#",iconHTML:"",id:"",pageId:0}};t.push(e)}return{suggestions:t}},formatGroup:(e,t,o)=>t.length<1?"":renderHTML(html`
          ${o>0?html`<hr>`:""}
          <h3 class="dropdown-headline">${t}</h3>
        `),formatResult:e=>renderHTML(html`
          <div class="dropdown-table">
            <div class="dropdown-table-row">
              ${this.linkItem(e)}
            </div>
          </div>
        `),onSearchStart:()=>{const e=$(Identifiers.toolbarItem);e.hasClass("loading")||(e.addClass("loading"),Icons.getIcon("spinner-circle-light",Icons.sizes.small,"",Icons.states.default,Icons.markupIdentifiers.inline).then(t=>{e.find(".icon-apps-toolbar-menu-search").replaceWith(t)}))},onSearchComplete:()=>{const e=$(Identifiers.toolbarItem);e.hasClass("loading")&&(e.removeClass("loading"),Icons.getIcon("apps-toolbar-menu-search",Icons.sizes.small,"",Icons.states.default,Icons.markupIdentifiers.inline).then(t=>{e.find(".icon-spinner-circle-light").replaceWith(t)}))},onSelect:t=>{e.focus(),$(Identifiers.searchFieldSelector).autocomplete("hide"),"search_all"===t.value?TYPO3.ModuleMenu.App.showModule("web_list","id=0&search_levels=-1&search_field="+encodeURIComponent(e.val())):TYPO3.Backend.ContentContainer.setUrl(t.data.editLink),document.body.classList.contains("scaffold-search-expanded")&&document.body.classList.remove("scaffold-search-expanded"),document.getElementById("typo3-contentIframe").onload=function(){$(Identifiers.searchFieldSelector).autocomplete("hide")}}})}registerEvents(){$(Identifiers.formSelector).on("submit",e=>{e.preventDefault()})}linkItem(e){return"search_all"===e.value?html`
        <a class="dropdown-list-link btn btn-primary float-end t3js-live-search-show-all" data-pageid="0">${e.data.title}</a>
      `:e.data.editLink?html`
        <a class="dropdown-list-link"
           data-pageid="${e.data.pageId}" href="#">
          <div class="dropdown-table-column dropdown-table-icon">
            ${unsafeHTML(e.data.iconHTML)}
          </div>
          <div class="dropdown-table-column">
            ${e.data.title}
          </div>
        </a>`:html`<span class="dropdown-list-title">${e.data.title}</span>`}}export default new LiveSearch;