define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,n,i){"use strict";
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
     */const r=new WeakMap,o=t.directive(e=>t=>{if(!(t instanceof n.NodePart))throw new Error("unsafeHTML can only be used in text bindings");const i=r.get(t);if(void 0!==i&&n.isPrimitive(e)&&e===i.value&&t.value===i.fragment)return;const o=document.createElement("template");o.innerHTML=e;const a=document.importNode(o.content,!0);t.setValue(a),r.set(t,{value:e,fragment:a})});e.unsafeHTML=o,Object.defineProperty(e,"__esModule",{value:!0})}));
