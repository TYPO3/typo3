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
import{LitElement as c,html as f}from"lit";import{property as d,customElement as g}from"lit/decorators.js";import m from"~labels/form.form_editor_javascript";var s=function(r,e,o,i){var n=arguments.length,t=n<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,o):i,p;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(r,e,o,i);else for(var l=r.length-1;l>=0;l--)(p=r[l])&&(t=(n<3?p(t):n>3?p(e,o,t):p(e,o))||t);return n>3&&t&&Object.defineProperty(e,o,t),t};let a=class extends c{constructor(){super(...arguments),this.pageTitle=""}createRenderRoot(){return this}render(){const e=this.pageTitle||m.get("formEditor.step.name.empty");return f`<div class=formeditor-page-header><h2 class=formeditor-page-title>${e}</h2><button type=button class="btn btn-sm btn-default"><typo3-backend-icon identifier=actions-open size=small></typo3-backend-icon>${m.get("formEditor.stage.page.edit")}</button></div>`}};s([d({type:String,attribute:"page-title"})],a.prototype,"pageTitle",void 0),a=s([g("typo3-form-page-stage-item")],a);export{a as PageStageItem};
