import{noChange as R}from"lit-html/lit-html.js";import{directive as e,Directive as i,PartType as t}from"lit-html/directive.js";
/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const oe=e(class extends i{constructor(r){if(super(r),r.type!==t.CHILD)throw Error("templateContent can only be used in child bindings")}render(t){return this.vt===t?R:(this.vt=t,document.importNode(t.content,!0))}});export{oe as templateContent};
