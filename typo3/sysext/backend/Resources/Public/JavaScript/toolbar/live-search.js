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
import{html}from"lit";import{lll}from"@typo3/core/lit-helper.js";import Viewport from"@typo3/backend/viewport.js";import Modal from"@typo3/backend/modal.js";import"@typo3/backend/element/icon-element.js";import"@typo3/backend/input/clearable.js";import"@typo3/backend/live-search/element/result-container.js";import"@typo3/backend/live-search/element/show-all.js";import"@typo3/backend/live-search/live-search-shortcut.js";import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import DebounceEvent from"@typo3/core/event/debounce-event.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";var Identifiers;!function(e){e.toolbarItem=".t3js-topbar-button-search"}(Identifiers||(Identifiers={}));class LiveSearch{constructor(){this.lastTerm="",this.lastResultSet="",this.hints=[lll("liveSearch_helpDescriptionPages"),lll("liveSearch_helpDescriptionContent"),lll("liveSearch_help.shortcutOpen")],this.search=async e=>{this.lastTerm=e;let t="[]";if(""!==e){document.querySelector("typo3-backend-live-search-result-container").setAttribute("loading","loading");const r=await new AjaxRequest(TYPO3.settings.ajaxUrls.livesearch).withQueryArguments({q:e}).get({cache:"no-cache"});t=await r.raw().text()}this.lastResultSet=t,this.updateSearchResults(t)},DocumentService.ready().then((()=>{Viewport.Topbar.Toolbar.registerEvent((()=>{this.registerEvents()}))}))}registerEvents(){new RegularEvent("click",(()=>{this.openSearchModal()})).delegateTo(document,Identifiers.toolbarItem),new RegularEvent("live-search:item-chosen",(e=>{Modal.dismiss(),e.detail.callback()})).bindTo(document),new RegularEvent("typo3:live-search:trigger-open",(()=>{Modal.currentModal||this.openSearchModal()})).bindTo(document)}openSearchModal(){const e=Modal.advanced({content:this.composeSearchComponent(),title:lll("labels.search"),severity:SeverityEnum.notice,size:Modal.sizes.medium});e.addEventListener("typo3-modal-shown",(()=>{const t=e.querySelector('input[type="search"]');t.clearable({onClear:()=>{this.search("")}}),t.focus(),t.select(),new DebounceEvent("input",(e=>{const t=e.target.value;this.search(t)})).bindTo(t),new RegularEvent("keydown",this.handleKeyDown).bindTo(t),this.lastResultSet&&this.updateSearchResults(this.lastResultSet)}))}composeSearchComponent(){return html`<div id="backend-live-search">
      <div class="sticky-form-actions">
        <div class="row row-cols-auto justify-content-between">
          <div class="col flex-grow-1">
            <input type="search" name="searchField" class="form-control" placeholder="Search" value="${this.lastTerm}" autocomplete="off">
            <div class="form-text mt-2">
              <typo3-backend-icon identifier="actions-lightbulb-on" size="small"></typo3-backend-icon>${this.hints[Math.floor(Math.random()*this.hints.length)]}
            </div>
          </div>
          <div class="col" hidden>
            <typo3-backend-live-search-show-all></typo3-backend-live-search-show-all>
          </div>
        </div>
      </div>
      <typo3-backend-live-search-result-container class="livesearch-results"></typo3-backend-live-search-result-container>
    </div>`}handleKeyDown(e){if("ArrowDown"!==e.key)return;e.preventDefault();document.getElementById("backend-live-search").querySelector("typo3-backend-live-search-result-item")?.focus()}updateSearchResults(e){document.querySelector("typo3-backend-live-search-show-all").parentElement.hidden=0===JSON.parse(e).length;const t=document.querySelector("typo3-backend-live-search-result-container");""!==this.lastTerm?t.setAttribute("results",e):t.removeAttribute("results"),t.removeAttribute("loading")}}export default new LiveSearch;