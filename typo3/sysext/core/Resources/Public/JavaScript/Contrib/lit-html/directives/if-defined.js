define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,i,n){"use strict";
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
     */const o=new WeakMap,r=t.directive(e=>t=>{const n=o.get(t);if(void 0===e&&t instanceof i.AttributePart){if(void 0!==n||!o.has(t)){const e=t.committer.name;t.committer.element.removeAttribute(e)}}else e!==n&&t.setValue(e);o.set(t,e)});e.ifDefined=r,Object.defineProperty(e,"__esModule",{value:!0})}));
