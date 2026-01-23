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
import{property as u,customElement as m}from"lit/decorators.js";import{PseudoButtonLitElement as y}from"@typo3/backend/element/pseudo-button.js";import c from"@typo3/backend/notification.js";import{lll as p}from"@typo3/core/lit-helper.js";var l=function(t,o,e,n){var a=arguments.length,r=a<3?o:n===null?n=Object.getOwnPropertyDescriptor(o,e):n,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")r=Reflect.decorate(t,o,e,n);else for(var d=t.length-1;d>=0;d--)(s=t[d])&&(r=(a<3?s(r):a>3?s(o,e,r):s(o,e))||r);return a>3&&r&&Object.defineProperty(o,e,r),r};function f(t,o=!1){if(!t.length){console.warn("No text for copy to clipboard given."),o||c.error(p("copyToClipboard.error"));return}if(navigator.clipboard)navigator.clipboard.writeText(t).then(()=>{document.dispatchEvent(new CustomEvent("copy-to-clipboard-success")),o||c.success(p("copyToClipboard.success"),"",1)}).catch(()=>{document.dispatchEvent(new CustomEvent("copy-to-clipboard-error")),o||c.error(p("copyToClipboard.error"))});else{const e=document.createElement("textarea");e.value=t,document.body.appendChild(e),e.focus(),e.select();try{document.execCommand("copy")?(document.dispatchEvent(new CustomEvent("copy-to-clipboard-success")),o||c.success(p("copyToClipboard.success"),"",1)):o||(document.dispatchEvent(new CustomEvent("copy-to-clipboard-error")),c.error(p("copyToClipboard.error")))}catch{o||(document.dispatchEvent(new CustomEvent("copy-to-clipboard-error")),c.error(p("copyToClipboard.error")))}document.body.removeChild(e)}}let i=class extends y{constructor(){super(...arguments),this.silent=!1}buttonActivated(){if(typeof this.text!="string"){console.warn("No text for copy to clipboard given."),this.silent||(document.dispatchEvent(new CustomEvent("copy-to-clipboard-error")),c.error(p("copyToClipboard.error")));return}f(this.text,this.silent)}};l([u({type:String})],i.prototype,"text",void 0),l([u({type:Boolean})],i.prototype,"silent",void 0),i=l([m("typo3-copy-to-clipboard")],i);export{i as CopyToClipboard,f as copyToClipboard};
