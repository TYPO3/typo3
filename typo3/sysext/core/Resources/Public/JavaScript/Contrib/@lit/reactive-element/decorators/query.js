import{desc as t}from"@lit/reactive-element/decorators/base.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function e(e,r){return(n,s,i)=>{const o=t=>t.renderRoot?.querySelector(e)??null;if(r){const{get:e,set:u}="object"==typeof s?n:i??(()=>{const t=Symbol();return{get(){return this[t]},set(e){this[t]=e}}})();return t(n,s,{get(){if(r){let t=e.call(this);return void 0===t&&(t=o(this),u.call(this,t)),t}return o(this)}})}return t(n,s,{get(){return o(this)}})}}export{e as query};
