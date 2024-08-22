import{noChange as R}from"lit-html/lit-html.js";import{AsyncDirective as $t}from"lit-html/async-directive.js";import{PseudoWeakRef as Tt,Pauser as Et,forAwaitOf as ti}from"lit-html/directives/private-async-helpers.js";import{directive as e}from"lit-html/directive.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */class kt extends $t{constructor(){super(...arguments),this.K=new Tt(this),this.X=new Et}render(t,i){return R}update(t,[i,s]){if(this.isConnected||this.disconnected(),i===this.J)return R;this.J=i;let r=0;const{K:e,X:n}=this;return ti(i,(async t=>{for(;n.get();)await n.get();const o=e.deref();if(void 0!==o){if(o.J!==i)return!1;void 0!==s&&(t=s(t,r)),o.commitValue(t,r),r++}return!0})),R}commitValue(t,i){this.setValue(t)}disconnected(){this.K.disconnect(),this.X.pause()}reconnected(){this.K.reconnect(this),this.X.resume()}}const Ot=e(kt);export{kt as AsyncReplaceDirective,Ot as asyncReplace};
