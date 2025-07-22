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
import{property as p,customElement as f}from"lit/decorators.js";import{LitElement as b,html as c}from"lit";import{Task as y}from"@lit/task";import"@typo3/backend/element/spinner-element.js";import"@typo3/backend/element/icon-element.js";var a=function(m,e,n,s){var l=arguments.length,t=l<3?e:s===null?s=Object.getOwnPropertyDescriptor(e,n):s,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(m,e,n,s);else for(var r=m.length-1;r>=0;r--)(i=m[r])&&(t=(l<3?i(t):l>3?i(e,n,t):i(e,n))||t);return l>3&&t&&Object.defineProperty(e,n,t),t};const u={default:"default",small:"small",medium:"medium",large:"large"};let o=class extends b{constructor(){super(...arguments),this.size=u.default,this.keepAspectRatio=!1,this.thumbnailTask=new y(this,{task:async([e,n,s,l,t])=>{const i=new URL(e,window.origin);i.searchParams.set("size",n),i.searchParams.set("keepAspectRatio",s?"1":"0");const r=new Image;return r.src=i.toString(),r.width=l,s||(r.height=t),await new Promise((h,d)=>{r.onload=()=>h(),r.onerror=()=>d()}),c`${r}`},args:()=>[this.url,this.size,this.keepAspectRatio,this.width,this.height]})}createRenderRoot(){return this}render(){return this.thumbnailTask.render({pending:()=>c`<typo3-backend-spinner size=${this.size}></typo3-backend-spinner>`,complete:e=>c`${e}`,error:()=>c`<typo3-backend-icon identifier=default-not-found size=small></typo3-backend-icon>`})}};a([p({type:String,reflect:!0})],o.prototype,"url",void 0),a([p({type:String,reflect:!0})],o.prototype,"size",void 0),a([p({type:Boolean,reflect:!0})],o.prototype,"keepAspectRatio",void 0),a([p({type:Number,reflect:!0})],o.prototype,"width",void 0),a([p({type:Number,reflect:!0})],o.prototype,"height",void 0),o=a([f("typo3-backend-thumbnail")],o);export{o as ThumbnailElement,u as ThumbnailSize};
