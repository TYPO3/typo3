define(["exports","../lib/directive","../lit-html"],(function(e,r,t){"use strict";
/**
     * @license
     * Copyright (c) 2018 The Polymer Project Authors. All rights reserved.
     * This code may only be used under the BSD style license found at
     * http://polymer.github.io/LICENSE.txt
     * The complete set of authors may be found at
     * http://polymer.github.io/AUTHORS.txt
     * The complete set of contributors may be found at
     * http://polymer.github.io/CONTRIBUTORS.txt
     * Code distributed by Google as part of the polymer project is also
     * subject to an additional IP rights grant found at
     * http://polymer.github.io/PATENTS.txt
     */const i=new WeakMap,a=r.directive((e,r)=>t=>{const a=i.get(t);if(Array.isArray(e)){if(Array.isArray(a)&&a.length===e.length&&e.every((e,r)=>e===a[r]))return}else if(a===e&&(void 0!==e||i.has(t)))return;t.setValue(r()),i.set(t,Array.isArray(e)?Array.from(e):e)});e.guard=a,Object.defineProperty(e,"__esModule",{value:!0})}));
