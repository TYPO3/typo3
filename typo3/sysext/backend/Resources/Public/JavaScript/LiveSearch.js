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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","./Viewport","./Icons","lit","lit/directives/unsafe-html","TYPO3/CMS/Core/lit-helper","jquery/autocomplete","./Input/Clearable"],(function(e,t,l,o,a,r,s,n){"use strict";var i;l=__importDefault(l),function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem",e.toolbarItem=".t3js-toolbar-item-search",e.dropdownToggle=".t3js-toolbar-search-dropdowntoggle",e.searchFieldSelector=".t3js-topbar-navigation-search-field",e.formSelector=".t3js-topbar-navigation-search"}(i||(i={}));return new class{constructor(){this.url=TYPO3.settings.ajaxUrls.livesearch,o.Topbar.Toolbar.registerEvent(()=>{let e;this.registerAutocomplete(),this.registerEvents(),l.default(i.toolbarItem).removeAttr("style"),null!==(e=document.querySelector(i.searchFieldSelector))&&e.clearable({onClear:()=>{l.default(i.dropdownToggle).hasClass("show")&&l.default(i.dropdownToggle).dropdown("toggle")}})})}registerAutocomplete(){l.default(i.searchFieldSelector).autocomplete({serviceUrl:this.url,paramName:"q",dataType:"json",minChars:2,width:"100%",groupBy:"typeLabel",noCache:!0,containerClass:i.toolbarItem.substr(1,i.toolbarItem.length),appendTo:i.containerSelector+" .dropdown-menu",forceFixPosition:!1,preserveInput:!0,showNoSuggestionNotice:!0,triggerSelectOnValidInput:!1,preventBadQueries:!1,noSuggestionNotice:'<h3 class="dropdown-headline">'+TYPO3.lang.liveSearch_listEmptyText+"</h3><p>"+TYPO3.lang.liveSearch_helpTitle+"</p><hr><p>"+TYPO3.lang.liveSearch_helpDescription+"<br>"+TYPO3.lang.liveSearch_helpDescriptionPages+"</p>",transformResult:e=>({suggestions:l.default.map(e,e=>({value:e.title,data:e}))}),formatGroup:(e,t,l)=>n.renderHTML(r.html`
          ${l>0?r.html`<hr>`:""}
          <h3 class="dropdown-headline">${t}</h3>
        `),formatResult:e=>n.renderHTML(r.html`
          <div class="dropdown-table">
            <div class="dropdown-table-row">
              <div class="dropdown-table-column dropdown-table-icon">
                ${s.unsafeHTML(e.data.iconHTML)}
              </div>
              <div class="dropdown-table-column dropdown-table-title">
                <a class="dropdown-table-title-ellipsis dropdown-list-link"
                   href="#" data-pageid="${e.data.pageId}" data-target="${e.data.editLink}">
                  ${e.data.title}
                </a>
              </div>
            </div>
          </div>
        `),onSearchStart:()=>{const e=l.default(i.toolbarItem);e.hasClass("loading")||(e.addClass("loading"),a.getIcon("spinner-circle-light",a.sizes.small,"",a.states.default,a.markupIdentifiers.inline).then(t=>{e.find(".icon-apps-toolbar-menu-search").replaceWith(t)}))},onSearchComplete:()=>{const e=l.default(i.toolbarItem),t=l.default(i.searchFieldSelector);!l.default(i.dropdownToggle).hasClass("show")&&t.val().length>1&&(l.default(i.dropdownToggle).dropdown("toggle"),t.focus()),e.hasClass("loading")&&(e.removeClass("loading"),a.getIcon("apps-toolbar-menu-search",a.sizes.small,"",a.states.default,a.markupIdentifiers.inline).then(t=>{e.find(".icon-spinner-circle-light").replaceWith(t)}))},beforeRender:e=>{e.append('<hr><div><a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">'+TYPO3.lang.liveSearch_showAllResults+"</a></div>"),l.default(i.dropdownToggle).hasClass("show")||(l.default(i.dropdownToggle).dropdown("show"),l.default(i.searchFieldSelector).focus())},onHide:()=>{l.default(i.dropdownToggle).hasClass("show")&&l.default(i.dropdownToggle).dropdown("hide")}})}registerEvents(){const e=l.default(i.searchFieldSelector);if(l.default(i.containerSelector).on("click",".t3js-live-search-show-all",t=>{t.preventDefault(),TYPO3.ModuleMenu.App.showModule("web_list","id=0&search_levels=-1&search_field="+encodeURIComponent(e.val())),e.val("").trigger("change")}),e.length){l.default("."+i.toolbarItem.substr(1,i.toolbarItem.length)).on("click.autocomplete",".dropdown-list-link",t=>{t.preventDefault();const o=l.default(t.currentTarget);top.jump(o.data("target"),"web_list","web",o.data("pageid")),e.val("").trigger("change")})}l.default(i.formSelector).on("submit",e=>{e.preventDefault()})}}}));