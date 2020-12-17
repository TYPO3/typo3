define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,n,i){"use strict";
/**
     * @license
     * Copyright (c) 2020 The Polymer Project Authors. All rights reserved.
     * This code may only be used under the BSD style license found at
     * http://polymer.github.io/LICENSE.txt
     * The complete set of authors may be found at
     * http://polymer.github.io/AUTHORS.txt
     * The complete set of contributors may be found at
     * http://polymer.github.io/CONTRIBUTORS.txt
     * Code distributed by Google as part of the polymer project is also
     * subject to an additional IP rights grant found at
     * http://polymer.github.io/PATENTS.txt
     */const o=new WeakMap,r=t.directive(e=>t=>{if(!(t instanceof n.NodePart))throw new Error("templateContent can only be used in text bindings");const i=o.get(t);if(void 0!==i&&e===i.template&&t.value===i.fragment)return;const r=document.importNode(e.content,!0);t.setValue(r),o.set(t,{template:e,fragment:r})});e.templateContent=r,Object.defineProperty(e,"__esModule",{value:!0})}));
