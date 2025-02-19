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
import{LitElement as u,css as f,html as y}from"lit";import{property as h,customElement as m}from"lit/decorators.js";import r from"@typo3/backend/notification.js";import{lll as i}from"@typo3/core/lit-helper.js";var d=function(t,o,c,p){var n=arguments.length,e=n<3?o:p===null?p=Object.getOwnPropertyDescriptor(o,c):p,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(t,o,c,p);else for(var s=t.length-1;s>=0;s--)(l=t[s])&&(e=(n<3?l(e):n>3?l(o,c,e):l(o,c))||e);return n>3&&e&&Object.defineProperty(o,c,e),e};function b(t){if(!t.length){console.warn("No text for copy to clipboard given."),r.error(i("copyToClipboard.error"));return}if(navigator.clipboard)navigator.clipboard.writeText(t).then(()=>{r.success(i("copyToClipboard.success"),"",1)}).catch(()=>{r.error(i("copyToClipboard.error"))});else{const o=document.createElement("textarea");o.value=t,document.body.appendChild(o),o.focus(),o.select();try{document.execCommand("copy")?r.success(i("copyToClipboard.success"),"",1):r.error(i("copyToClipboard.error"))}catch{r.error(i("copyToClipboard.error"))}document.body.removeChild(o)}}let a=class extends u{static{this.styles=[f`:host{cursor:pointer;appearance:button}`]}constructor(){super(),this.addEventListener("click",o=>{o.preventDefault(),this.copyToClipboard()}),this.addEventListener("keydown",o=>{(o.key==="Enter"||o.key===" ")&&(o.preventDefault(),this.copyToClipboard())})}connectedCallback(){this.hasAttribute("role")||this.setAttribute("role","button"),this.hasAttribute("tabindex")||this.setAttribute("tabindex","0")}render(){return y`<slot></slot>`}copyToClipboard(){if(typeof this.text!="string"){console.warn("No text for copy to clipboard given."),r.error(i("copyToClipboard.error"));return}b(this.text)}};d([h({type:String})],a.prototype,"text",void 0),a=d([m("typo3-copy-to-clipboard")],a);export{a as CopyToClipboard,b as copyToClipboard};
