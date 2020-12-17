define(["exports","../lib/directive","../lib/dom","../lib/template-instance","../lib/template-result","../lib/parts","../lit-html"],(function(e,t,n,a,i,o,s){"use strict";
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
     */const l=new WeakMap,c=t.directive(e=>t=>{if(!(t instanceof o.NodePart))throw new Error("cache can only be used in text bindings");let s=l.get(t);void 0===s&&(s=new WeakMap,l.set(t,s));const c=t.value;if(c instanceof a.TemplateInstance){if(e instanceof i.TemplateResult&&c.template===t.options.templateFactory(e))return void t.setValue(e);{let e=s.get(c.template);void 0===e&&(e={instance:c,nodes:document.createDocumentFragment()},s.set(c.template,e)),n.reparentNodes(e.nodes,t.startNode.nextSibling,t.endNode)}}if(e instanceof i.TemplateResult){const n=t.options.templateFactory(e),a=s.get(n);void 0!==a&&(t.setValue(a.nodes),t.commit(),t.value=a.instance)}t.setValue(e)});e.cache=c,Object.defineProperty(e,"__esModule",{value:!0})}));
