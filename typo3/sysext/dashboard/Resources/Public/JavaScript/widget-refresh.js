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
var Selectors,__decorate=function(e,t,r,s){var o,n=arguments.length,i=n<3?t:null===s?s=Object.getOwnPropertyDescriptor(t,r):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,s);else for(var c=e.length-1;c>=0;c--)(o=e[c])&&(i=(n<3?o(i):n>3?o(t,r,i):o(t,r))||i);return n>3&&i&&Object.defineProperty(t,r,i),i};import{html,LitElement}from"lit";import{customElement}from"lit/decorators.js";!function(e){e.dashboardItem=".dashboard-item"}(Selectors||(Selectors={}));let WidgetRefresh=class extends LitElement{connectedCallback(){super.connectedCallback(),this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("click",this.onRefresh),this.addEventListener("keydown",this.onKeyDown)}disconnectedCallback(){this.removeEventListener("click",this.onRefresh),this.removeEventListener("keydown",this.onKeyDown),super.disconnectedCallback()}render(){return html`<slot></slot>`}onKeyDown(e){"Enter"!==e.key&&" "!==e.key||this.onRefresh(e)}onRefresh(e){e.preventDefault(),this.closest(Selectors.dashboardItem).dispatchEvent(new Event("widgetRefresh",{bubbles:!0})),this.blur()}};WidgetRefresh=__decorate([customElement("typo3-dashboard-widget-refresh")],WidgetRefresh);export{WidgetRefresh};