import{noChange as R}from"lit-html/lit-html.js";import{directive as e,Directive as i}from"lit-html/directive.js";
/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const Vt={},It=e(class extends i{constructor(){super(...arguments),this.ot=Vt}render(r,t){return t()}update(r,[t,s]){if(Array.isArray(t)){if(Array.isArray(this.ot)&&this.ot.length===t.length&&t.every(((r,t)=>r===this.ot[t])))return R}else if(this.ot===t)return R;return this.ot=Array.isArray(t)?Array.from(t):t,this.render(t,s)}});export{It as guard};
