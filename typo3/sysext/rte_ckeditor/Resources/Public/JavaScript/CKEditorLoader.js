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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","module"],(function(e,t,o){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.loadCKEditor=void 0,o=__importDefault(o);let r=null;t.loadCKEditor=function(){if(null===r){const t=o.default.uri.replace(/\/[^\/]+\.js/,"/Contrib/ckeditor.js");r=(e=t,new Promise((t,o)=>{const r=document.createElement("script");r.async=!0,r.onerror=o,r.onload=e=>t(e),r.src=e,document.head.appendChild(r)})).then(()=>window.CKEDITOR)}var e;return r}}));