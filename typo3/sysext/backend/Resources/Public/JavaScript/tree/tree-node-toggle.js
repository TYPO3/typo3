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
import{property as a,customElement as f}from"lit/decorators.js";import{LitElement as u,html as s}from"lit";import"@typo3/backend/element/icon-element.js";var p=function(r,t,o,n){var c=arguments.length,e=c<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,o):n,d;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(r,t,o,n);else for(var l=r.length-1;l>=0;l--)(d=r[l])&&(e=(c<3?d(e):c>3?d(t,o,e):d(t,o))||e);return c>3&&e&&Object.defineProperty(t,o,e),e};let i=class extends u{constructor(){super(...arguments),this.expanded="false"}render(){return s`<typo3-backend-icon size=small identifier=${this.expanded==="true"?"actions-chevron-down":"actions-chevron-end"}></typo3-backend-icon>`}};p([a({type:String,reflect:!0,attribute:"aria-expanded"})],i.prototype,"expanded",void 0),i=p([f("typo3-backend-tree-node-toggle")],i);var m=i;export{m as default};
