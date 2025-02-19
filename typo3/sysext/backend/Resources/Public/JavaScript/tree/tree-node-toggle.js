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
import{property as a,customElement as f}from"lit/decorators.js";import{LitElement as u,html as s}from"lit";import"@typo3/backend/element/icon-element.js";var d=function(o,t,r,n){var i=arguments.length,e=i<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,r):n,c;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(o,t,r,n);else for(var p=o.length-1;p>=0;p--)(c=o[p])&&(e=(i<3?c(e):i>3?c(t,r,e):c(t,r))||e);return i>3&&e&&Object.defineProperty(t,r,e),e};let l=class extends u{constructor(){super(...arguments),this.expanded="false"}render(){return s`<typo3-backend-icon size=small identifier=${this.expanded==="true"?"actions-chevron-down":"actions-chevron-right"}></typo3-backend-icon>`}};d([a({type:String,reflect:!0,attribute:"aria-expanded"})],l.prototype,"expanded",void 0),l=d([f("typo3-backend-tree-node-toggle")],l);var m=l;export{m as default};
