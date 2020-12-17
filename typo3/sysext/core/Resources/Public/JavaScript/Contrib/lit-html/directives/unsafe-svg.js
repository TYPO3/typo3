define(["exports","../lib/directive","../lib/dom","../lib/parts","../lit-html"],(function(e,t,n,i,r){"use strict";
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
     */const o=new WeakMap,s=window.navigator.userAgent.indexOf("Trident/")>0,d=t.directive(e=>t=>{if(!(t instanceof i.NodePart))throw new Error("unsafeSVG can only be used in text bindings");const r=o.get(t);if(void 0!==r&&i.isPrimitive(e)&&e===r.value&&t.value===r.fragment)return;const d=document.createElement("template"),a=d.content;let l;s?(d.innerHTML=`<svg>${e}</svg>`,l=a.firstChild):(l=document.createElementNS("http://www.w3.org/2000/svg","svg"),a.appendChild(l),l.innerHTML=e),a.removeChild(l),n.reparentNodes(a,l.firstChild);const c=document.importNode(a,!0);t.setValue(c),o.set(t,{value:e,fragment:c})});e.unsafeSVG=d,Object.defineProperty(e,"__esModule",{value:!0})}));
