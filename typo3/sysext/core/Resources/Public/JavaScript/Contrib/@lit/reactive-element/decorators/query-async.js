import{desc as t}from"@lit/reactive-element/decorators/base.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
function r(r){return(n,e)=>t(n,e,{async get(){return await this.updateComplete,this.renderRoot?.querySelector(r)??null}})}export{r as queryAsync};
