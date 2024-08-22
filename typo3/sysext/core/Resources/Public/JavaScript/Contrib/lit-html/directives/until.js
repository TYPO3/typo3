import{noChange as R}from"lit-html/lit-html.js";import{isPrimitive as st}from"lit-html/directive-helpers.js";import{AsyncDirective as $t}from"lit-html/async-directive.js";import{PseudoWeakRef as Tt,Pauser as Et}from"lit-html/directives/private-async-helpers.js";import{directive as e}from"lit-html/directive.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const me=t=>!st(t)&&"function"==typeof t.then,_e=1073741823;class we extends $t{constructor(){super(...arguments),this.wt=_e,this.bt=[],this.K=new Tt(this),this.X=new Et}render(...t){return t.find((t=>!me(t)))??R}update(t,s){const i=this.bt;let e=i.length;this.bt=s;const r=this.K,o=this.X;this.isConnected||this.disconnected();for(let t=0;t<s.length&&!(t>this.wt);t++){const n=s[t];if(!me(n))return this.wt=t,n;t<e&&n===i[t]||(this.wt=_e,e=0,Promise.resolve(n).then((async t=>{for(;o.get();)await o.get();const s=r.deref();if(void 0!==s){const i=s.bt.indexOf(n);i>-1&&i<s.wt&&(s.wt=i,s.setValue(t))}})))}return R}disconnected(){this.K.disconnect(),this.X.pause()}reconnected(){this.K.reconnect(this),this.X.resume()}}const be=e(we);export{we as UntilDirective,be as until};
