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
import{property as d,customElement as p}from"lit/decorators.js";import{LitElement as u,html as s}from"lit";import{lll as h}from"@typo3/core/lit-helper.js";import"@typo3/backend/element/icon-element.js";import"bootstrap";var m=function(n,e,o,i){var c=arguments.length,t=c<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,o):i,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,o,i);else for(var a=n.length-1;a>=0;a--)(l=n[a])&&(t=(c<3?l(t):c>3?l(e,o,t):l(e,o))||t);return c>3&&t&&Object.defineProperty(e,o,t),t};const b={fromAttribute(n){return document.querySelector(n)}};let r=class extends u{constructor(){super(...arguments),this.activeTheme="light",this.themes={auto:{icon:"actions-circle-half",label:"colorScheme.auto"},light:{icon:"actions-brightness-high",label:"colorScheme.light"},dark:{icon:"actions-moon",label:"colorScheme.dark"}}}createRenderRoot(){return this}render(){const e=s`<span class=text-primary><typo3-backend-icon identifier=actions-dot size=small></typo3-backend-icon></span>`,o=s`<typo3-backend-icon identifier=miscellaneous-placeholder size=small></typo3-backend-icon>`,i=[];for(const[c,t]of Object.entries(this.themes))i.push(s`<li><a class="dropdown-item dropdown-item-spaced" href=# data-theme=${c} @click=${this.setTheme}> ${c===this.activeTheme?e:o} ${h(t.label)} </a></li>`);return s`<div class=colorscheme-switch>${h("colorScheme.selector.label")}<div class=dropdown><button class="btn btn-link dropdown-toggle" type=button data-bs-toggle=dropdown aria-expanded=false><typo3-backend-icon identifier=${this.themes[this.activeTheme].icon} size=small></typo3-backend-icon>${h(this.themes[this.activeTheme].label)}</button><ul class=dropdown-menu>${i}</ul></div></div>`}setTheme(e){this.activeTheme=e.target.dataset.theme,this.example.dataset.colorScheme=this.activeTheme}};m([d()],r.prototype,"activeTheme",void 0),m([d({converter:b})],r.prototype,"example",void 0),r=m([p("typo3-styleguide-theme-switcher")],r);export{r as ThemeSwitcherElement};
