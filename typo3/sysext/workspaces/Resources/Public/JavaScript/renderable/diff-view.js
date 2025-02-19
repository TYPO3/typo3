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
import{property as c,customElement as p}from"lit/decorators.js";import{LitElement as u,html as d}from"lit";import{repeat as v}from"lit/directives/repeat.js";import{unsafeHTML as a}from"lit/directives/unsafe-html.js";var m=function(i,e,r,f){var o=arguments.length,t=o<3?e:f===null?f=Object.getOwnPropertyDescriptor(e,r):f,n;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,r,f);else for(var s=i.length-1;s>=0;s--)(n=i[s])&&(t=(o<3?n(t):o>3?n(e,r,t):n(e,r))||t);return o>3&&t&&Object.defineProperty(e,r,t),t};let l=class extends u{constructor(){super(...arguments),this.diffs=[]}createRenderRoot(){return this}render(){return d`<div class=diff>${v(this.diffs,e=>e.field,e=>this.renderDiffItem(e))}</div>`}renderDiffItem(e){return d`<div class=diff-item><div class=diff-item-title>${e.label}</div><div class=diff-item-result>${a(e.content)}</div></div>`}};m([c({type:Array})],l.prototype,"diffs",void 0),l=m([p("typo3-workspaces-diff-view")],l);export{l as DiffViewElement};
