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
define(["require","exports"],(function(e,n){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.JavaScriptItemProcessor=void 0;const t=["__proto__","prototype","constructor"],o=["assign","invoke","instance"];function r(e){if(!e.name)throw new Error("JavaScript module name is required");if(1==(1&e.flags))return new Promise((n,t)=>{(16==(16&e.flags)?top.window:window).require([e.name],e=>n(e),e=>t(e))});throw new Error("Unknown JavaScript module type")}function i(e,n){Object.keys(n).forEach(o=>{if(-1!==t.indexOf(o))throw new Error("Property "+o+" is not allowed");var r;!((r=n[o])instanceof Object)||r instanceof Array||void 0===e[o]?Object.assign(e,{[o]:n[o]}):i(e[o],n[o])})}n.JavaScriptItemProcessor=class{constructor(){this.invokableNames=["globalAssignment","javaScriptModuleInstruction"]}processItems(e){e.forEach(e=>this.invoke(e.type,e.payload))}invoke(e,n){if(!this.invokableNames.includes(e)||"function"!=typeof this[e])throw new Error('Unknown handler name "'+e+'"');this[e].call(this,n)}globalAssignment(e){i(window,e)}javaScriptModuleInstruction(e){!function(e){if(!e.name)throw new Error("JavaScript module name is required");if(!e.items)return void r(e);const n=e.exportName,t=e=>"string"==typeof n?e[n]:e,s=e.items.filter(e=>o.includes(e.type)).map(e=>"assign"===e.type?n=>{i(t(n),e.assignments)}:"invoke"===e.type?n=>{const o=t(n);o[e.method].apply(o,e.args)}:"instance"===e.type?n=>{const o=[null].concat(e.args),r=t(n);new(r.bind.apply(r,o))}:e=>{});r(e).then(e=>s.forEach(n=>n.call(null,e)))}(e)}}}));