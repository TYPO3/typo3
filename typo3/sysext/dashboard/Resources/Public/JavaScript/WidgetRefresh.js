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
var __decorate=this&&this.__decorate||function(e,t,r,o){var s,n=arguments.length,c=n<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(e,t,r,o);else for(var d=e.length-1;d>=0;d--)(s=e[d])&&(c=(n<3?s(c):n>3?s(t,r,c):s(t,r))||c);return n>3&&c&&Object.defineProperty(t,r,c),c};define(["require","exports","lit","lit/decorators"],(function(e,t,r,o){"use strict";var s;Object.defineProperty(t,"__esModule",{value:!0}),function(e){e.dashboardItem=".dashboard-item"}(s||(s={}));let n=class extends r.LitElement{constructor(){super(),this.addEventListener("click",e=>{e.preventDefault(),this.closest(s.dashboardItem).dispatchEvent(new Event("widgetRefresh",{bubbles:!0})),this.querySelector("button").blur()})}render(){return r.html`<slot></slot>`}};n=__decorate([(0,o.customElement)("typo3-dashboard-widget-refresh")],n)}));