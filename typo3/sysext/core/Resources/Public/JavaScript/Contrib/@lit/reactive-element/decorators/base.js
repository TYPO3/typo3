define(["exports"],(function(exports){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */exports.decorateProperty=({finisher:e,descriptor:t})=>(o,n)=>{var r;if(void 0===n){const n=null!==(r=o.originalKey)&&void 0!==r?r:o.key,i=null!=t?{kind:"method",placement:"prototype",key:n,descriptor:t(o.key)}:{...o,key:n};return null!=e&&(i.finisher=function(t){e(t,n)}),i}{const r=o.constructor;void 0!==t&&Object.defineProperty(o,n,t(n)),null==e||e(r,n)}},exports.legacyPrototypeMethod=(e,t,o)=>{Object.defineProperty(t,o,e)},exports.standardPrototypeMethod=(e,t)=>({kind:"method",placement:"prototype",key:t.key,descriptor:e}),Object.defineProperty(exports,"__esModule",{value:!0})}));
