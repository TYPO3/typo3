define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,n,d){"use strict";
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
     */const l=new WeakMap,s=2147483647,i=t.directive((...e)=>t=>{let d=l.get(t);void 0===d&&(d={lastRenderedIndex:s,values:[]},l.set(t,d));const i=d.values;let r=i.length;d.values=e;for(let l=0;l<e.length&&!(l>d.lastRenderedIndex);l++){const a=e[l];if(n.isPrimitive(a)||"function"!=typeof a.then){t.setValue(a),d.lastRenderedIndex=l;break}l<r&&a===i[l]||(d.lastRenderedIndex=s,r=0,Promise.resolve(a).then(e=>{const n=d.values.indexOf(a);n>-1&&n<d.lastRenderedIndex&&(d.lastRenderedIndex=n,t.setValue(e),t.commit())}))}});e.until=i,Object.defineProperty(e,"__esModule",{value:!0})}));
