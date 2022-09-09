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
var __decorate=function(e,t,l,o){var r,c=arguments.length,n=c<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,l):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,l,o);else for(var s=e.length-1;s>=0;s--)(r=e[s])&&(n=(c<3?r(n):c>3?r(t,l,n):r(t,l))||n);return c>3&&n&&Object.defineProperty(t,l,n),n};import{customElement}from"lit/decorators.js";import{html,LitElement}from"lit";import{lll}from"@typo3/core/lit-helper.js";let ResultItem=class extends LitElement{connectedCallback(){super.connectedCallback(),this.addEventListener("click",this.dispatchItemChosenEvent)}createRenderRoot(){return this}render(){return html`<button class="btn btn-primary">${lll("liveSearch_showAllResults")}</button>`}dispatchItemChosenEvent(e){e.preventDefault();const t=document.getElementById("backend-live-search").querySelector('input[type="search"]');document.dispatchEvent(new CustomEvent("live-search:item-chosen",{detail:{callback:()=>{TYPO3.ModuleMenu.App.showModule("web_list","id=0&search_levels=-1&search_field="+encodeURIComponent(t.value))}}}))}};ResultItem=__decorate([customElement("typo3-backend-live-search-show-all")],ResultItem);export{ResultItem};