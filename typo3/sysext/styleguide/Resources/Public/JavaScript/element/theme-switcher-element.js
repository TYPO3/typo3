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
import{property as d,customElement as p}from"lit/decorators.js";import{LitElement as u,html as l}from"lit";import"@typo3/backend/element/icon-element.js";import"bootstrap";import b from"~labels/styleguide.messages";import m from"~labels/backend.messages";var h=function(n,e,o,i){var c=arguments.length,t=c<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,o):i,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,o,i);else for(var a=n.length-1;a>=0;a--)(s=n[a])&&(t=(c<3?s(t):c>3?s(e,o,t):s(e,o))||t);return c>3&&t&&Object.defineProperty(e,o,t),t};const f={fromAttribute(n){return document.querySelector(n)}};let r=class extends u{constructor(){super(...arguments),this.activeTheme="light",this.themes={auto:{icon:"actions-circle-half",label:m.get("colorScheme.auto")},light:{icon:"actions-brightness-high",label:m.get("colorScheme.light")},dark:{icon:"actions-moon",label:m.get("colorScheme.dark")}}}createRenderRoot(){return this}render(){const e=l`<span class=text-primary><typo3-backend-icon identifier=actions-dot size=small></typo3-backend-icon></span>`,o=l`<typo3-backend-icon identifier=miscellaneous-placeholder size=small></typo3-backend-icon>`,i=[];for(const[c,t]of Object.entries(this.themes))i.push(l`<li><button class="dropdown-item dropdown-item-spaced" data-theme=${c} @click=${this.setTheme}>${c===this.activeTheme?e:o} ${t.label}</button></li>`);return l`<div class=colorscheme-switch>${b.get("colorScheme.selector.label")}<div class=dropdown><button class="btn btn-link dropdown-toggle" type=button data-bs-toggle=dropdown aria-expanded=false><typo3-backend-icon identifier=${this.themes[this.activeTheme].icon} size=small></typo3-backend-icon>${this.themes[this.activeTheme].label}</button><ul class=dropdown-menu>${i}</ul></div></div>`}setTheme(e){this.activeTheme=e.target.dataset.theme??"",this.example.dataset.colorScheme=this.activeTheme}};h([d()],r.prototype,"activeTheme",void 0),h([d({converter:f})],r.prototype,"example",void 0),r=h([p("typo3-styleguide-theme-switcher")],r);export{r as ThemeSwitcherElement};
