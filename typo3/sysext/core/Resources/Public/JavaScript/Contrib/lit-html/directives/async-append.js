define(["exports","../lib/directive","../lib/template","../lib/parts","../lit-html"],(function(e,t,r,n,o){"use strict";
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
     */var a=function(e){if(!Symbol.asyncIterator)throw new TypeError("Symbol.asyncIterator is not defined.");var t,r=e[Symbol.asyncIterator];return r?r.call(e):(e="function"==typeof __values?__values(e):e[Symbol.iterator](),t={},n("next"),n("throw"),n("return"),t[Symbol.asyncIterator]=function(){return this},t);function n(r){t[r]=e[r]&&function(t){return new Promise((function(n,o){(function(e,t,r,n){Promise.resolve(n).then((function(t){e({value:t,done:r})}),t)})(n,o,(t=e[r](t)).done,t.value)}))}}};const i=t.directive((e,t)=>async o=>{var i,l;if(!(o instanceof n.NodePart))throw new Error("asyncAppend can only be used in text bindings");if(e===o.value)return;let c;o.value=e;let d=0;try{for(var s,u=a(e);!(s=await u.next()).done;){let a=s.value;if(o.value!==e)break;0===d&&o.clear(),void 0!==t&&(a=t(a,d));let i=o.startNode;void 0!==c&&(i=r.createMarker(),c.endNode=i,o.endNode.parentNode.insertBefore(i,o.endNode)),c=new n.NodePart(o.options),c.insertAfterNode(i),c.setValue(a),c.commit(),d++}}catch(e){i={error:e}}finally{try{s&&!s.done&&(l=u.return)&&await l.call(u)}finally{if(i)throw i.error}}});e.asyncAppend=i,Object.defineProperty(e,"__esModule",{value:!0})}));
