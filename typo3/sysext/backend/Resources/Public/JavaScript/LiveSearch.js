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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./Viewport","./Icons","lit","lit/directives/unsafe-html","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/Storage/ModuleStateStorage","jquery/autocomplete","./Input/Clearable"],(function(e,t,l,o,a,r,s,n,i){"use strict";var d;l=__importDefault(l),function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem",e.toolbarItem=".t3js-toolbar-item-search",e.dropdownToggle=".t3js-toolbar-search-dropdowntoggle",e.searchFieldSelector=".t3js-topbar-navigation-search-field",e.formSelector=".t3js-topbar-navigation-search"}(d||(d={}));return new class{constructor(){this.url=TYPO3.settings.ajaxUrls.livesearch,o.Topbar.Toolbar.registerEvent(()=>{let e;this.registerAutocomplete(),this.registerEvents(),(0,l.default)(d.toolbarItem).removeAttr("style"),null!==(e=document.querySelector(d.searchFieldSelector))&&e.clearable({onClear:()=>{(0,l.default)(d.dropdownToggle).hasClass("show")&&(0,l.default)(d.dropdownToggle).dropdown("toggle")}})})}registerAutocomplete(){(0,l.default)(d.searchFieldSelector).autocomplete({serviceUrl:this.url,paramName:"q",dataType:"json",minChars:2,width:"100%",groupBy:"typeLabel",noCache:!0,containerClass:d.toolbarItem.substr(1,d.toolbarItem.length),appendTo:d.containerSelector+" .dropdown-menu",forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,triggerSelectOnValidInput:!1,preventBadQueries:!1,noSuggestionNotice:'<h3 class="dropdown-headline">'+TYPO3.lang.liveSearch_listEmptyText+"</h3><p>"+TYPO3.lang.liveSearch_helpTitle+"</p><hr><p>"+TYPO3.lang.liveSearch_helpDescription+"<br>"+TYPO3.lang.liveSearch_helpDescriptionPages+"</p>",transformResult:e=>({suggestions:l.default.map(e,e=>({value:e.title,data:e}))}),formatGroup:(e,t,l)=>(0,n.renderHTML)(r.html`
          ${l>0?r.html`<hr>`:""}
          <h3 class="dropdown-headline">${t}</h3>
        `),formatResult:e=>(0,n.renderHTML)(r.html`
          <div class="dropdown-table">
            <div class="dropdown-table-row">
              <div class="dropdown-table-column dropdown-table-icon">
                ${(0,s.unsafeHTML)(e.data.iconHTML)}
              </div>
              <div class="dropdown-table-column dropdown-table-title">
                ${this.linkItem(e)}
              </div>
            </div>
          </div>
        `),onSearchStart:()=>{const e=(0,l.default)(d.toolbarItem);e.hasClass("loading")||(e.addClass("loading"),a.getIcon("spinner-circle-light",a.sizes.small,"",a.states.default,a.markupIdentifiers.inline).then(t=>{e.find(".icon-apps-toolbar-menu-search").replaceWith(t)}))},onSearchComplete:()=>{const e=(0,l.default)(d.toolbarItem),t=(0,l.default)(d.searchFieldSelector);!(0,l.default)(d.dropdownToggle).hasClass("show")&&t.val().length>1&&((0,l.default)(d.dropdownToggle).dropdown("toggle"),t.focus()),e.hasClass("loading")&&(e.removeClass("loading"),a.getIcon("apps-toolbar-menu-search",a.sizes.small,"",a.states.default,a.markupIdentifiers.inline).then(t=>{e.find(".icon-spinner-circle-light").replaceWith(t)}))},beforeRender:e=>{e.append('<hr><div><a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">'+TYPO3.lang.liveSearch_showAllResults+"</a></div>"),(0,l.default)(d.dropdownToggle).hasClass("show")||((0,l.default)(d.dropdownToggle).dropdown("show"),(0,l.default)(d.searchFieldSelector).focus())},onHide:()=>{(0,l.default)(d.dropdownToggle).hasClass("show")&&(0,l.default)(d.dropdownToggle).dropdown("hide")}})}registerEvents(){const e=(0,l.default)(d.searchFieldSelector);if((0,l.default)(d.containerSelector).on("click",".t3js-live-search-show-all",t=>{t.preventDefault(),TYPO3.ModuleMenu.App.showModule("web_list","id=0&search_levels=-1&search_field="+encodeURIComponent(e.val())),e.val("").trigger("change")}),e.length){(0,l.default)("."+d.toolbarItem.substr(1,d.toolbarItem.length)).on("click.autocomplete",".dropdown-list-link",t=>{t.preventDefault();const o=(0,l.default)(t.currentTarget);i.ModuleStateStorage.updateWithCurrentMount("web",o.data("pageid"),!0);const a=document.querySelector("typo3-backend-module-router");a.setAttribute("endpoint",o.attr("href")),a.setAttribute("module","web_list"),e.val("").trigger("change")})}(0,l.default)(d.formSelector).on("submit",e=>{e.preventDefault()})}linkItem(e){return e.data.editLink?r.html`
        <a class="dropdown-table-title-ellipsis dropdown-list-link"
           data-pageid="${e.data.pageId}" href="${e.data.editLink}">
          ${e.data.title}
        </a>`:r.html`<span class="dropdown-table-title-ellipsis">${e.data.title}</span>`}}}));