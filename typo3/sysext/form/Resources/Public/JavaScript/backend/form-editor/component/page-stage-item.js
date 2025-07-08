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
import{LitElement as f,html as c}from"lit";import{property as s,customElement as g}from"lit/decorators.js";var m=function(o,e,r,n){var p=arguments.length,t=p<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(o,e,r,n);else for(var a=o.length-1;a>=0;a--)(i=o[a])&&(t=(p<3?i(t):p>3?i(e,r,t):i(e,r))||t);return p>3&&t&&Object.defineProperty(e,r,t),t};let l=class extends f{constructor(){super(...arguments),this.pageTitle=""}createRenderRoot(){return this}render(){const e=this.pageTitle||TYPO3.lang["formEditor.step.name.empty"];return c`<h2 class=formeditor-page-title>${e}</h2>`}};m([s({type:String,attribute:"page-title"})],l.prototype,"pageTitle",void 0),l=m([g("typo3-form-page-stage-item")],l);export{l as PageStageItem};
