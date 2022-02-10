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
import $ from"jquery";import Viewport from"@typo3/backend/viewport.js";import Icons from"@typo3/backend/icons.js";import"jquery/autocomplete.js";import"@typo3/backend/input/clearable.js";import{html}from"lit";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{renderHTML}from"@typo3/core/lit-helper.js";import{ModuleStateStorage}from"@typo3/backend/storage/module-state-storage.js";var Identifiers;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem",e.toolbarItem=".t3js-toolbar-item-search",e.dropdownToggle=".t3js-toolbar-search-dropdowntoggle",e.searchFieldSelector=".t3js-topbar-navigation-search-field",e.formSelector=".t3js-topbar-navigation-search"}(Identifiers||(Identifiers={}));class LiveSearch{constructor(){this.url=TYPO3.settings.ajaxUrls.livesearch,Viewport.Topbar.Toolbar.registerEvent(()=>{let e;this.registerAutocomplete(),this.registerEvents(),$(Identifiers.toolbarItem).removeAttr("style"),null!==(e=document.querySelector(Identifiers.searchFieldSelector))&&e.clearable({onClear:()=>{$(Identifiers.dropdownToggle).hasClass("show")&&$(Identifiers.dropdownToggle).dropdown("toggle")}})})}registerAutocomplete(){$(Identifiers.searchFieldSelector).autocomplete({serviceUrl:this.url,paramName:"q",dataType:"json",minChars:2,width:"100%",groupBy:"typeLabel",noCache:!0,containerClass:Identifiers.toolbarItem.substr(1,Identifiers.toolbarItem.length),appendTo:Identifiers.containerSelector+" .dropdown-menu",forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,triggerSelectOnValidInput:!1,preventBadQueries:!1,noSuggestionNotice:'<h3 class="dropdown-headline">'+TYPO3.lang.liveSearch_listEmptyText+"</h3><p>"+TYPO3.lang.liveSearch_helpTitle+"</p><hr><p>"+TYPO3.lang.liveSearch_helpDescription+"<br>"+TYPO3.lang.liveSearch_helpDescriptionPages+"</p>",transformResult:e=>({suggestions:$.map(e,e=>({value:e.title,data:e}))}),formatGroup:(e,t,o)=>renderHTML(html`
          ${o>0?html`<hr>`:""}
          <h3 class="dropdown-headline">${t}</h3>
        `),formatResult:e=>renderHTML(html`
          <div class="dropdown-table">
            <div class="dropdown-table-row">
              <div class="dropdown-table-column dropdown-table-icon">
                ${unsafeHTML(e.data.iconHTML)}
              </div>
              <div class="dropdown-table-column dropdown-table-title">
                ${this.linkItem(e)}
              </div>
            </div>
          </div>
        `),onSearchStart:()=>{const e=$(Identifiers.toolbarItem);e.hasClass("loading")||(e.addClass("loading"),Icons.getIcon("spinner-circle-light",Icons.sizes.small,"",Icons.states.default,Icons.markupIdentifiers.inline).then(t=>{e.find(".icon-apps-toolbar-menu-search").replaceWith(t)}))},onSearchComplete:()=>{const e=$(Identifiers.toolbarItem),t=$(Identifiers.searchFieldSelector);!$(Identifiers.dropdownToggle).hasClass("show")&&t.val().length>1&&($(Identifiers.dropdownToggle).dropdown("toggle"),t.focus()),e.hasClass("loading")&&(e.removeClass("loading"),Icons.getIcon("apps-toolbar-menu-search",Icons.sizes.small,"",Icons.states.default,Icons.markupIdentifiers.inline).then(t=>{e.find(".icon-spinner-circle-light").replaceWith(t)}))},beforeRender:e=>{e.append('<hr><div><a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">'+TYPO3.lang.liveSearch_showAllResults+"</a></div>"),$(Identifiers.dropdownToggle).hasClass("show")||($(Identifiers.dropdownToggle).dropdown("show"),$(Identifiers.searchFieldSelector).focus())},onHide:()=>{$(Identifiers.dropdownToggle).hasClass("show")&&$(Identifiers.dropdownToggle).dropdown("hide")}})}registerEvents(){const e=$(Identifiers.searchFieldSelector);if($(Identifiers.containerSelector).on("click",".t3js-live-search-show-all",t=>{t.preventDefault(),TYPO3.ModuleMenu.App.showModule("web_list","id=0&search_levels=-1&search_field="+encodeURIComponent(e.val())),e.val("").trigger("change")}),e.length){$("."+Identifiers.toolbarItem.substr(1,Identifiers.toolbarItem.length)).on("click.autocomplete",".dropdown-list-link",t=>{t.preventDefault();const o=$(t.currentTarget);ModuleStateStorage.updateWithCurrentMount("web",o.data("pageid"),!0);const r=document.querySelector("typo3-backend-module-router");r.setAttribute("endpoint",o.attr("href")),r.setAttribute("module","web_list"),e.val("").trigger("change")})}$(Identifiers.formSelector).on("submit",e=>{e.preventDefault()})}linkItem(e){return e.data.editLink?html`
        <a class="dropdown-table-title-ellipsis dropdown-list-link"
           data-pageid="${e.data.pageId}" href="${e.data.editLink}">
          ${e.data.title}
        </a>`:html`<span class="dropdown-table-title-ellipsis">${e.data.title}</span>`}}export default new LiveSearch;