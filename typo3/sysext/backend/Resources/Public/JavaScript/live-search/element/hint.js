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
import{property as m,customElement as h}from"lit/decorators.js";import{LitElement as a,nothing as u,html as d}from"lit";import{markdown as s}from"@typo3/core/directive/markdown.js";import"@typo3/backend/element/icon-element.js";var f=function(i,e,n,r){var o=arguments.length,t=o<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,n):r,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(i,e,n,r);else for(var p=i.length-1;p>=0;p--)(l=i[p])&&(t=(o<3?l(t):o>3?l(e,n,t):l(e,n))||t);return o>3&&t&&Object.defineProperty(e,n,t),t};let c=class extends a{createRenderRoot(){return this}render(){return this.hint===""?u:d`<typo3-backend-icon identifier=actions-lightbulb-on size=small></typo3-backend-icon>${s(this.hint,"minimal")}`}};f([m({type:String})],c.prototype,"hint",void 0),c=f([h("typo3-backend-live-search-hint")],c);export{c as Hint};
