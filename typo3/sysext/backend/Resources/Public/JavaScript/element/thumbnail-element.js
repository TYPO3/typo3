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
import{property as l,customElement as f}from"lit/decorators.js";import{LitElement as b,html as c}from"lit";import{Task as y}from"@lit/task";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/element/icon-element.js";var p=function(m,t,o,s){var a=arguments.length,e=a<3?t:s===null?s=Object.getOwnPropertyDescriptor(t,o):s,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(m,t,o,s);else for(var r=m.length-1;r>=0;r--)(i=m[r])&&(e=(a<3?i(e):a>3?i(t,o,e):i(t,o))||e);return a>3&&e&&Object.defineProperty(t,o,e),e};const u={default:"default",small:"small",medium:"medium",large:"large"};let n=class extends b{constructor(){super(...arguments),this.size=u.default,this.keepAspectRatio=!1,this.thumbnailTask=new y(this,{task:async([t,o,s,a,e])=>{const i=new URL(t,window.origin);i.searchParams.set("size",o),i.searchParams.set("keepAspectRatio",s?"1":"0"),i.searchParams.set("bust",Date.now().toString(10));const r=new Image;return r.src=i.toString(),a>0&&(r.width=a),e>0&&!s&&(r.height=e),await new Promise(((h,d)=>{r.onload=()=>h(),r.onerror=()=>d()})),c`${r}`},args:()=>[this.url,this.size,this.keepAspectRatio,this.width,this.height]})}createRenderRoot(){return this}render(){return this.thumbnailTask.render({initial:()=>c`<typo3-backend-spinner size=${this.size}></typo3-backend-spinner>`,pending:()=>c`<typo3-backend-spinner size=${this.size}></typo3-backend-spinner>`,complete:t=>c`${t}`,error:()=>c`<typo3-backend-icon identifier=default-not-found size=small></typo3-backend-icon>`})}};p([l({type:String,reflect:!0})],n.prototype,"url",void 0),p([l({type:String,reflect:!0})],n.prototype,"size",void 0),p([l({type:Boolean,reflect:!0})],n.prototype,"keepAspectRatio",void 0),p([l({type:Number,reflect:!0})],n.prototype,"width",void 0),p([l({type:Number,reflect:!0})],n.prototype,"height",void 0),n=p([f("typo3-backend-thumbnail")],n);export{n as ThumbnailElement,u as ThumbnailSize};
