import{directive as e,PartType as t}from"lit-html/directive.js";import{AsyncReplaceDirective as kt}from"lit-html/directives/async-replace.js";import{clearPart as ft,insertPart as at,setChildPartValue as ct}from"lit-html/directive-helpers.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const jt=e(class extends kt{constructor(r){if(super(r),r.type!==t.CHILD)throw Error("asyncAppend can only be used in child expressions")}update(r,e){return this.tt=r,super.update(r,e)}commitValue(r,e){0===e&&ft(this.tt);const s=at(this.tt);ct(s,r)}});export{jt as asyncAppend};
