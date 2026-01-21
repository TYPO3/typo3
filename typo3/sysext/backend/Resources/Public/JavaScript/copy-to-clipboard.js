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
import{property as f,customElement as y}from"lit/decorators.js";import{PseudoButtonLitElement as m}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/notification.js";import{lll as i}from"@typo3/core/lit-helper.js";var d=function(t,o,e,n){var l=arguments.length,r=l<3?o:n===null?n=Object.getOwnPropertyDescriptor(o,e):n,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")r=Reflect.decorate(t,o,e,n);else for(var s=t.length-1;s>=0;s--)(a=t[s])&&(r=(l<3?a(r):l>3?a(o,e,r):a(o,e))||r);return l>3&&r&&Object.defineProperty(o,e,r),r};function u(t,o=!1){if(!t.length){console.warn("No text for copy to clipboard given."),o||c.error(i("copyToClipboard.error"));return}if(navigator.clipboard)navigator.clipboard.writeText(t).then(()=>{document.dispatchEvent(new CustomEvent("copy-to-clipboard-success")),o||c.success(i("copyToClipboard.success"),"",1)}).catch(()=>{o||c.error(i("copyToClipboard.error"))});else{const e=document.createElement("textarea");e.value=t,document.body.appendChild(e),e.focus(),e.select();try{document.execCommand("copy")?(document.dispatchEvent(new CustomEvent("copy-to-clipboard-success")),o||c.success(i("copyToClipboard.success"),"",1)):o||c.error(i("copyToClipboard.error"))}catch{o||c.error(i("copyToClipboard.error"))}document.body.removeChild(e)}}let p=class extends m{constructor(){super(...arguments),this.silent=!1}buttonActivated(){if(typeof this.text!="string"){console.warn("No text for copy to clipboard given."),this.silent||c.error(i("copyToClipboard.error"));return}u(this.text,this.silent)}};d([f({type:String})],p.prototype,"text",void 0),d([f({type:Boolean})],p.prototype,"silent",void 0),p=d([y("typo3-copy-to-clipboard")],p);export{p as CopyToClipboard,u as copyToClipboard};
