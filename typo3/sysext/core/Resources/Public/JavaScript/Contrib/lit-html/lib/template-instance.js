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
     */e.TemplateInstance=class{constructor(e,t,s){this.__parts=[],this.template=e,this.processor=t,this.options=s}update(e){let t=0;for(const s of this.__parts)void 0!==s&&s.setValue(e[t]),t++;for(const e of this.__parts)void 0!==e&&e.commit()}_clone(){const e=t.isCEPolyfill?this.template.element.content.cloneNode(!0):document.importNode(this.template.element.content,!0),o=[],n=this.template.parts,i=document.createTreeWalker(e,133,null,!1);let r,p=0,l=0,a=i.nextNode();for(;p<n.length;)if(r=n[p],s.isTemplatePartActive(r)){for(;l<r.index;)l++,"TEMPLATE"===a.nodeName&&(o.push(a),i.currentNode=a.content),null===(a=i.nextNode())&&(i.currentNode=o.pop(),a=i.nextNode());if("node"===r.type){const e=this.processor.handleTextExpression(this.options);e.insertAfterNode(a.previousSibling),this.__parts.push(e)}else this.__parts.push(...this.processor.handleAttributeExpressions(a,r.name,r.strings,this.options));p++}else this.__parts.push(void 0),p++;return t.isCEPolyfill&&(document.adoptNode(e),customElements.upgrade(e)),e}},Object.defineProperty(e,"__esModule",{value:!0})}));
