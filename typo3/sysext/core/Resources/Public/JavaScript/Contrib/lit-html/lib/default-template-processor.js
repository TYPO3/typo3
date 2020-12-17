define(["exports","./parts"],(function(e,t){"use strict";
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
     */class r{handleAttributeExpressions(e,r,n,s){const o=r[0];if("."===o){return new t.PropertyCommitter(e,r.slice(1),n).parts}if("@"===o)return[new t.EventPart(e,r.slice(1),s.eventContext)];if("?"===o)return[new t.BooleanAttributePart(e,r.slice(1),n)];return new t.AttributeCommitter(e,r,n).parts}handleTextExpression(e){return new t.NodePart(e)}}const n=new r;e.DefaultTemplateProcessor=r,e.defaultTemplateProcessor=n,Object.defineProperty(e,"__esModule",{value:!0})}));
