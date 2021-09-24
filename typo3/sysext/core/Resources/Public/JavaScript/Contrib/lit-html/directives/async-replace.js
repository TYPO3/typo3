define(["exports","../lit-html","../directive","../async-directive","./private-async-helpers"],(function(exports,litHtml,directive,asyncDirective,privateAsyncHelpers){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */class o extends asyncDirective.AsyncDirective{constructor(){super(...arguments),this._$CG=new privateAsyncHelpers.PseudoWeakRef(this),this._$CK=new privateAsyncHelpers.Pauser}render(i,s){return litHtml.noChange}update(i,[s,r]){if(this.isConnected||this.disconnected(),s===this._$CJ)return;this._$CJ=s;let e=0;const{_$CG:o,_$CK:h}=this;return privateAsyncHelpers.forAwaitOf(s,async t=>{for(;h.get();)await h.get();const i=o.deref();if(void 0!==i){if(i._$CJ!==s)return!1;void 0!==r&&(t=r(t,e)),i.commitValue(t,e),e++}return!0}),litHtml.noChange}commitValue(t,i){this.setValue(t)}disconnected(){this._$CG.disconnect(),this._$CK.pause()}reconnected(){this._$CG.reconnect(this),this._$CK.resume()}}const h=directive.directive(o);exports.AsyncReplaceDirective=o,exports.asyncReplace=h,Object.defineProperty(exports,"__esModule",{value:!0})}));
