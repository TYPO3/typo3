define(["exports","../lib/directive","../lib/parts","../lit-html"],(function(e,t,r,i){"use strict";
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
     */const n=new WeakMap,s=t.directive(e=>t=>{if(!(t instanceof r.AttributePart)||t instanceof r.PropertyPart||"style"!==t.committer.name||t.committer.parts.length>1)throw new Error("The `styleMap` directive must be used in the style attribute and must be the only part in the attribute.");const{committer:i}=t,{style:s}=i.element;let o=n.get(t);void 0===o&&(s.cssText=i.strings.join(" "),n.set(t,o=new Set)),o.forEach(t=>{t in e||(o.delete(t),-1===t.indexOf("-")?s[t]=null:s.removeProperty(t))});for(const t in e)o.add(t),-1===t.indexOf("-")?s[t]=e[t]:s.setProperty(t,e[t])});e.styleMap=s,Object.defineProperty(e,"__esModule",{value:!0})}));
