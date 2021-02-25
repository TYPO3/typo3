define(["exports","../lit-html","../directive","../async-directive"],(function(exports,litHtml,directive,asyncDirective){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const s=directive.directive(class extends asyncDirective.AsyncDirective{render(i,e){return litHtml.noChange}update(i,[e,s]){if(e!==this.vt)return this.vt=e,this.Σft(s),litHtml.noChange}async Σft(t){let i=0;const{vt:e}=this;for await(let s of e){if(this.vt!==e)break;this.wt&&await this.wt,void 0!==t&&(s=t(s,i)),this.setValue(s),i++}}disconnected(){this.wt=new Promise(t=>this.yt=t)}reconnected(){this.wt=void 0,this.yt()}});exports.asyncReplace=s,Object.defineProperty(exports,"__esModule",{value:!0})}));
