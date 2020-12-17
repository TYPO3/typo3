define(["exports","./dom","./parts","./template-factory"],(function(e,t,a,o){"use strict";
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
     */const r=new WeakMap;e.parts=r,e.render=(e,s,n)=>{let d=r.get(s);void 0===d&&(t.removeNodes(s,s.firstChild),r.set(s,d=new a.NodePart(Object.assign({templateFactory:o.templateFactory},n))),d.appendInto(s)),d.setValue(e),d.commit()},Object.defineProperty(e,"__esModule",{value:!0})}));
