define(["exports","./template"],(function(e,t){"use strict";
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
     */const r=new Map;e.templateCaches=r,e.templateFactory=function(e){let n=r.get(e.type);void 0===n&&(n={stringsArray:new WeakMap,keyString:new Map},r.set(e.type,n));let s=n.stringsArray.get(e.strings);if(void 0!==s)return s;const i=e.strings.join(t.marker);return s=n.keyString.get(i),void 0===s&&(s=new t.Template(e,e.getTemplateElement()),n.keyString.set(i,s)),n.stringsArray.set(e.strings,s),s},Object.defineProperty(e,"__esModule",{value:!0})}));
