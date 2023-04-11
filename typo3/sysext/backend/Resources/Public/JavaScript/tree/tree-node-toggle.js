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
var __decorate=function(e,t,o,r){var n,c=arguments.length,d=c<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,o):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)d=Reflect.decorate(e,t,o,r);else for(var l=e.length-1;l>=0;l--)(n=e[l])&&(d=(c<3?n(d):c>3?n(t,o,d):n(t,o))||d);return c>3&&d&&Object.defineProperty(t,o,d),d};import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";import"@typo3/backend/element/icon-element.js";let TreeNodeToggle=class extends LitElement{constructor(){super(...arguments),this.expanded="false"}render(){return html`<typo3-backend-icon size="small" identifier="${"true"===this.expanded?"actions-chevron-down":"actions-chevron-right"}"></typo3-backend-icon>`}};__decorate([property({type:String,reflect:!0,attribute:"aria-expanded"})],TreeNodeToggle.prototype,"expanded",void 0),TreeNodeToggle=__decorate([customElement("typo3-backend-tree-node-toggle")],TreeNodeToggle);export default TreeNodeToggle;