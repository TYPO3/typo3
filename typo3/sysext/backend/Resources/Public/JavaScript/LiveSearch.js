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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./Viewport","./Icons","lit","lit/directives/unsafe-html","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/Storage/ModuleStateStorage","jquery/autocomplete","./Input/Clearable"],(function(e,t,o,l,a,r,s,n,d){"use strict";var i;o=__importDefault(o),function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem",e.toolbarItem=".t3js-toolbar-item-search",e.dropdownToggle=".t3js-toolbar-search-dropdowntoggle",e.searchFieldSelector=".t3js-topbar-navigation-search-field",e.formSelector=".t3js-topbar-navigation-search"}(i||(i={}));return new class{constructor(){this.url=TYPO3.settings.ajaxUrls.livesearch,l.Topbar.Toolbar.registerEvent(()=>{let e;this.registerAutocomplete(),this.registerEvents(),o.default(i.toolbarItem).removeAttr("style"),null!==(e=document.querySelector(i.searchFieldSelector))&&e.clearable({onClear:()=>{o.default(i.dropdownToggle).hasClass("show")&&o.default(i.dropdownToggle).dropdown("toggle")}})})}registerAutocomplete(){o.default(i.searchFieldSelector).autocomplete({serviceUrl:this.url,paramName:"q",dataType:"json",minChars:2,width:"100%",groupBy:"typeLabel",noCache:!0,containerClass:i.toolbarItem.substr(1,i.toolbarItem.length),appendTo:i.containerSelector+" .dropdown-menu",forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,triggerSelectOnValidInput:!1,preventBadQueries:!1,noSuggestionNotice:'<h3 class="dropdown-headline">'+TYPO3.lang.liveSearch_listEmptyText+"</h3><p>"+TYPO3.lang.liveSearch_helpTitle+"</p><hr><p>"+TYPO3.lang.liveSearch_helpDescription+"<br>"+TYPO3.lang.liveSearch_helpDescriptionPages+"</p>",transformResult:e=>({suggestions:o.default.map(e,e=>({value:e.title,data:e}))}),formatGroup:(e,t,o)=>n.renderHTML(r.html`
          ${o>0?r.html`<hr>`:""}
          <h3 class="dropdown-headline">${t}</h3>
        `),formatResult:e=>n.renderHTML(r.html`
          <div class="dropdown-table">
            <div class="dropdown-table-row">
              <div class="dropdown-table-column dropdown-table-icon">
                ${s.unsafeHTML(e.data.iconHTML)}
              </div>
              <div class="dropdown-table-column dropdown-table-title">
                <a class="dropdown-table-title-ellipsis dropdown-list-link"
                   data-pageid="${e.data.pageId}" href="${e.data.editLink}">
                  ${e.data.title}
                </a>
              </div>
            </div>
          </div>
        `),onSearchStart:()=>{const e=o.default(i.toolbarItem);e.hasClass("loading")||(e.addClass("loading"),a.getIcon("spinner-circle-light",a.sizes.small,"",a.states.default,a.markupIdentifiers.inline).then(t=>{e.find(".icon-apps-toolbar-menu-search").replaceWith(t)}))},onSearchComplete:()=>{const e=o.default(i.toolbarItem),t=o.default(i.searchFieldSelector);!o.default(i.dropdownToggle).hasClass("show")&&t.val().length>1&&(o.default(i.dropdownToggle).dropdown("toggle"),t.focus()),e.hasClass("loading")&&(e.removeClass("loading"),a.getIcon("apps-toolbar-menu-search",a.sizes.small,"",a.states.default,a.markupIdentifiers.inline).then(t=>{e.find(".icon-spinner-circle-light").replaceWith(t)}))},beforeRender:e=>{e.append('<hr><div><a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">'+TYPO3.lang.liveSearch_showAllResults+"</a></div>"),o.default(i.dropdownToggle).hasClass("show")||(o.default(i.dropdownToggle).dropdown("show"),o.default(i.searchFieldSelector).focus())},onHide:()=>{o.default(i.dropdownToggle).hasClass("show")&&o.default(i.dropdownToggle).dropdown("hide")}})}registerEvents(){const e=o.default(i.searchFieldSelector);if(o.default(i.containerSelector).on("click",".t3js-live-search-show-all",t=>{t.preventDefault(),TYPO3.ModuleMenu.App.showModule("web_list","id=0&search_levels=-1&search_field="+encodeURIComponent(e.val())),e.val("").trigger("change")}),e.length){o.default("."+i.toolbarItem.substr(1,i.toolbarItem.length)).on("click.autocomplete",".dropdown-list-link",t=>{t.preventDefault();const l=o.default(t.currentTarget);d.ModuleStateStorage.updateWithCurrentMount("web",l.data("pageid"),!0);const a=document.querySelector("typo3-backend-module-router");a.setAttribute("endpoint",l.attr("href")),a.setAttribute("module","web_list"),e.val("").trigger("change")})}o.default(i.formSelector).on("submit",e=>{e.preventDefault()})}}}));