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
const p=2,v=16,l=["__proto__","prototype","constructor"],f=["assign","invoke","instance"];function o(t){if(!t.name)throw new Error("JavaScript module name is required");if((t.flags&2)===2)if(t.flags&16){const n=new CustomEvent("typo3:import-javascript-module",{detail:{specifier:t.name,importPromise:null}});return top.document.dispatchEvent(n),n.detail.importPromise||Promise.reject(new Error("Top-level import failed"))}else return import(t.name);throw new Error("Unknown JavaScript module type")}function s(t,n){const e=n.exportName;return typeof e=="string"?t[e]:t.default}function a(t){if(!t.name)throw new Error("JavaScript module name is required");if(!t.items)return o(t);const n=t.items.filter(e=>f.includes(e.type)).map(e=>e.type==="assign"?r=>{const i=s(r,t);c(i,e.assignments)}:e.type==="invoke"?r=>{const i=s(r,t);return"method"in e&&e.method?i[e.method](...e.args):i(...e.args)}:e.type==="instance"?r=>{const i=[null].concat(e.args),u=s(r,t);return new(u.bind(...i))}:()=>{});return o(t).then(e=>n.map(r=>r.call(null,e)))}function d(t){return t instanceof Object&&!(t instanceof Array)}function c(t,n){Object.keys(n).forEach(e=>{if(l.indexOf(e)!==-1)throw new Error("Property "+e+" is not allowed");!d(n[e])||typeof t[e]>"u"?Object.assign(t,{[e]:n[e]}):c(t[e],n[e])})}class m{constructor(){this.invokableNames=["globalAssignment","javaScriptModuleInstruction"]}processItems(n){n.forEach(e=>this.invoke(e.type,e.payload))}invoke(n,e){if(!this.invokableNames.includes(n)||typeof this[n]!="function")throw new Error('Unknown handler name "'+n+'"');this[n].call(this,e)}globalAssignment(n){c(window,n)}javaScriptModuleInstruction(n){a(n)}}export{m as JavaScriptItemProcessor,a as executeJavaScriptModuleInstruction,o as loadModule,s as resolveSubjectRef};
