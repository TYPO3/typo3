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
import{LitElement as a,html as m}from"lit";import{property as f,customElement as u}from"lit/decorators.js";import{Sizes as h}from"@typo3/backend/enum/icon-types.js";import{IconStyles as v}from"@typo3/backend/icons.js";var l=function(r,t,n,s){var o=arguments.length,e=o<3?t:s===null?s=Object.getOwnPropertyDescriptor(t,n):s,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(r,t,n,s);else for(var c=r.length-1;c>=0;c--)(i=r[c])&&(e=(o<3?i(e):o>3?i(t,n,e):i(t,n))||e);return o>3&&e&&Object.defineProperty(t,n,e),e};let p=class extends a{constructor(){super(...arguments),this.size=h.default}static{this.styles=v.getStyles()}render(){return m`<span class="icon icon-size-${this.size} icon-state-default icon-spin"> <span class=icon-markup><svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16"><g fill="currentColor"><path d="M8 15c-3.86 0-7-3.141-7-7s3.14-7 7-7 7 3.14 7 7-3.141 7-7 7M8 3C5.243 3 3 5.243 3 8s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5" opacity=".3"/><path d="M14 9a1 1 0 0 1-1-1c0-2.757-2.243-5-5-5a1 1 0 0 1 0-2c3.859 0 7 3.14 7 7a1 1 0 0 1-1 1"/></g></svg> </span> </span>`}};l([f({type:String})],p.prototype,"size",void 0),p=l([u("typo3-backend-spinner")],p);export{p as SpinnerElement};
