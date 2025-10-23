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
import{property as u,customElement as y}from"lit/decorators.js";import{PseudoButtonLitElement as b}from"@typo3/backend/element/pseudo-button.js";import t from"@typo3/backend/notification.js";import{lll as c}from"@typo3/core/lit-helper.js";var f=function(r,o,p,i){var n=arguments.length,e=n<3?o:i===null?i=Object.getOwnPropertyDescriptor(o,p):i,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(r,o,p,i);else for(var d=r.length-1;d>=0;d--)(l=r[d])&&(e=(n<3?l(e):n>3?l(o,p,e):l(o,p))||e);return n>3&&e&&Object.defineProperty(o,p,e),e};function s(r){if(!r.length){console.warn("No text for copy to clipboard given."),t.error(c("copyToClipboard.error"));return}if(navigator.clipboard)navigator.clipboard.writeText(r).then(()=>{t.success(c("copyToClipboard.success"),"",1)}).catch(()=>{t.error(c("copyToClipboard.error"))});else{const o=document.createElement("textarea");o.value=r,document.body.appendChild(o),o.focus(),o.select();try{document.execCommand("copy")?t.success(c("copyToClipboard.success"),"",1):t.error(c("copyToClipboard.error"))}catch{t.error(c("copyToClipboard.error"))}document.body.removeChild(o)}}let a=class extends b{buttonActivated(){if(typeof this.text!="string"){console.warn("No text for copy to clipboard given."),t.error(c("copyToClipboard.error"));return}s(this.text)}};f([u({type:String})],a.prototype,"text",void 0),a=f([y("typo3-copy-to-clipboard")],a);export{a as CopyToClipboard,s as copyToClipboard};
