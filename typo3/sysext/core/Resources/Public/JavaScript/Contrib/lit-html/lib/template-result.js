define(["exports","./dom","./template"],(function(e,t,s){"use strict";
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
     */const r=window.trustedTypes&&trustedTypes.createPolicy("lit-html",{createHTML:e=>e}),n=` ${s.marker} `;class l{constructor(e,t,s,r){this.strings=e,this.values=t,this.type=s,this.processor=r}getHTML(){const e=this.strings.length-1;let t="",r=!1;for(let l=0;l<e;l++){const e=this.strings[l],i=e.lastIndexOf("\x3c!--");r=(i>-1||r)&&-1===e.indexOf("--\x3e",i+1);const o=s.lastAttributeNameRegex.exec(e);t+=null===o?e+(r?n:s.nodeMarker):e.substr(0,o.index)+o[1]+o[2]+s.boundAttributeSuffix+o[3]+s.marker}return t+=this.strings[e],t}getTemplateElement(){const e=document.createElement("template");let t=this.getHTML();return void 0!==r&&(t=r.createHTML(t)),e.innerHTML=t,e}}e.SVGTemplateResult=class extends l{getHTML(){return`<svg>${super.getHTML()}</svg>`}getTemplateElement(){const e=super.getTemplateElement(),s=e.content,r=s.firstChild;return s.removeChild(r),t.reparentNodes(s,r.firstChild),e}},e.TemplateResult=l,Object.defineProperty(e,"__esModule",{value:!0})}));
