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
import{LitElement as f,html as c}from"lit";import{property as s,customElement as g}from"lit/decorators.js";import u from"~labels/form.form_editor_javascript";var a=function(o,e,r,p){var i=arguments.length,t=i<3?e:p===null?p=Object.getOwnPropertyDescriptor(e,r):p,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(o,e,r,p);else for(var m=o.length-1;m>=0;m--)(l=o[m])&&(t=(i<3?l(t):i>3?l(e,r,t):l(e,r))||t);return i>3&&t&&Object.defineProperty(e,r,t),t};let n=class extends f{constructor(){super(...arguments),this.pageTitle=""}createRenderRoot(){return this}render(){const e=this.pageTitle||u.get("formEditor.step.name.empty");return c`<h2 class=formeditor-page-title>${e}</h2>`}};a([s({type:String,attribute:"page-title"})],n.prototype,"pageTitle",void 0),n=a([g("typo3-form-page-stage-item")],n);export{n as PageStageItem};
