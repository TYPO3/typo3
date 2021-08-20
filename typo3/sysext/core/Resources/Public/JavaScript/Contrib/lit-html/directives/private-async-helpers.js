define(["exports"],(function(exports){"use strict";
/**
	 * @license
	 * Copyright 2021 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */exports.Pauser=class{constructor(){this.G=void 0,this.K=void 0}get(){return this.G}pause(){var t;null!==(t=this.G)&&void 0!==t||(this.G=new Promise(t=>this.K=t))}resume(){var t;null===(t=this.K)||void 0===t||t.call(this),this.G=this.K=void 0}},exports.PseudoWeakRef=class{constructor(t){this.q=t}disconnect(){this.q=void 0}reconnect(t){this.q=t}deref(){return this.q}},exports.forAwaitOf=async(t,s)=>{for await(const i of t)if(!1===await s(i))return},Object.defineProperty(exports,"__esModule",{value:!0})}));
