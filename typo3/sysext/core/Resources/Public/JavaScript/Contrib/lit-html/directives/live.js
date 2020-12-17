define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,i,n){"use strict";
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
     */const r=t.directive(e=>t=>{let n;if(t instanceof i.EventPart||t instanceof i.NodePart)throw new Error("The `live` directive is not allowed on text or event bindings");if(t instanceof i.BooleanAttributePart)o(t.strings),n=t.element.hasAttribute(t.name),t.value=n;else{const{element:r,name:s,strings:a}=t.committer;if(o(a),t instanceof i.PropertyPart){if(n=r[s],n===e)return}else t instanceof i.AttributePart&&(n=r.getAttribute(s));if(n===String(e))return}t.setValue(e)}),o=e=>{if(2!==e.length||""!==e[0]||""!==e[1])throw new Error("`live` bindings can only contain a single expression")};e.live=r,Object.defineProperty(e,"__esModule",{value:!0})}));
