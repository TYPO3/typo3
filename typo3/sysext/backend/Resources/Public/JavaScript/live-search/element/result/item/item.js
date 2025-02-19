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
import{property as d,customElement as p}from"lit/decorators.js";import{LitElement as u,html as f}from"lit";import"@typo3/backend/element/icon-element.js";var l=function(n,e,t,o){var i=arguments.length,c=i<3?e:o===null?o=Object.getOwnPropertyDescriptor(e,t):o,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")c=Reflect.decorate(n,e,t,o);else for(var a=n.length-1;a>=0;a--)(r=n[a])&&(c=(i<3?r(c):i>3?r(e,t,c):r(e,t))||c);return i>3&&c&&Object.defineProperty(e,t,c),c};let s=class extends u{connectedCallback(){super.connectedCallback(),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.addEventListener("focus",this.onFocus)}disconnectedCallback(){this.removeEventListener("focus",this.onFocus),super.disconnectedCallback()}createRenderRoot(){return this}render(){return f`<div class=livesearch-expand-action @click=${e=>{e.stopPropagation(),this.focus()}}><typo3-backend-icon identifier=actions-chevron-right size=small></typo3-backend-icon></div>`}onFocus(e){const t=e.target;t.parentElement.querySelector(".active")?.classList.remove("active"),t.classList.add("active")}};l([d({type:Object,attribute:!1})],s.prototype,"resultItem",void 0),s=l([p("typo3-backend-live-search-result-item")],s);export{s as Item};
