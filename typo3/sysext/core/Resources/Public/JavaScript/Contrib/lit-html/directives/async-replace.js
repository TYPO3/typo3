define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,n,r){"use strict";
/**
     * @license
     * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
     * This code may only be used under the BSD style license found at
     * http://polymer.github.io/LICENSE.txt
     * The complete set of authors may be found at
     * http://polymer.github.io/AUTHORS.txt
     * The complete set of contributors may be found at
     * http://polymer.github.io/CONTRIBUTORS.txt
     * Code distributed by Google as part of the polymer project is also
     * subject to an additional IP rights grant found at
     * http://polymer.github.io/PATENTS.txt
     */var o=function(e){if(!Symbol.asyncIterator)throw new TypeError("Symbol.asyncIterator is not defined.");var t,n=e[Symbol.asyncIterator];return n?n.call(e):(e="function"==typeof __values?__values(e):e[Symbol.iterator](),t={},r("next"),r("throw"),r("return"),t[Symbol.asyncIterator]=function(){return this},t);function r(n){t[n]=e[n]&&function(t){return new Promise((function(r,o){(function(e,t,n,r){Promise.resolve(r).then((function(t){e({value:t,done:n})}),t)})(r,o,(t=e[n](t)).done,t.value)}))}}};const a=t.directive((e,t)=>async r=>{var a,i;if(!(r instanceof n.NodePart))throw new Error("asyncReplace can only be used in text bindings");if(e===r.value)return;const l=new n.NodePart(r.options);r.value=e;let c=0;try{for(var u,s=o(e);!(u=await s.next()).done;){let n=u.value;if(r.value!==e)break;0===c&&(r.clear(),l.appendIntoPart(r)),void 0!==t&&(n=t(n,c)),l.setValue(n),l.commit(),c++}}catch(e){a={error:e}}finally{try{u&&!u.done&&(i=s.return)&&await i.call(s)}finally{if(a)throw a.error}}});e.asyncReplace=a,Object.defineProperty(e,"__esModule",{value:!0})}));
