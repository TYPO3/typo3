define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(t,e,s,i){"use strict";
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
     */class c{constructor(t){this.classes=new Set,this.changed=!1,this.element=t;const e=(t.getAttribute("class")||"").split(/\s+/);for(const t of e)this.classes.add(t)}add(t){this.classes.add(t),this.changed=!0}remove(t){this.classes.delete(t),this.changed=!0}commit(){if(this.changed){let t="";this.classes.forEach(e=>t+=e+" "),this.element.setAttribute("class",t)}}}const a=new WeakMap,n=e.directive(t=>e=>{if(!(e instanceof s.AttributePart)||e instanceof s.PropertyPart||"class"!==e.committer.name||e.committer.parts.length>1)throw new Error("The `classMap` directive must be used in the `class` attribute and must be the only part in the attribute.");const{committer:i}=e,{element:n}=i;let o=a.get(e);void 0===o&&(n.setAttribute("class",i.strings.join(" ")),a.set(e,o=new Set));const r=n.classList||new c(n);o.forEach(e=>{e in t||(r.remove(e),o.delete(e))});for(const e in t){const s=t[e];s!=o.has(e)&&(s?(r.add(e),o.add(e)):(r.remove(e),o.delete(e)))}"function"==typeof r.commit&&r.commit()});t.classMap=n,Object.defineProperty(t,"__esModule",{value:!0})}));
