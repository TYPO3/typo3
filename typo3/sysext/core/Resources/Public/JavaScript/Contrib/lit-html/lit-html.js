define(["exports","./lib/directive","./lib/dom","./lib/part","./lib/template","./lib/template-instance","./lib/template-result","./lib/parts","./lib/default-template-processor","./lib/template-factory","./lib/render"],(function(e,t,r,a,l,i,o,s,m,p,n){"use strict";
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
     */"undefined"!=typeof window&&(window.litHtmlVersions||(window.litHtmlVersions=[])).push("1.3.0");e.directive=t.directive,e.isDirective=t.isDirective,e.removeNodes=r.removeNodes,e.reparentNodes=r.reparentNodes,e.noChange=a.noChange,e.nothing=a.nothing,e.Template=l.Template,e.createMarker=l.createMarker,e.isTemplatePartActive=l.isTemplatePartActive,e.TemplateInstance=i.TemplateInstance,e.SVGTemplateResult=o.SVGTemplateResult,e.TemplateResult=o.TemplateResult,e.AttributeCommitter=s.AttributeCommitter,e.AttributePart=s.AttributePart,e.BooleanAttributePart=s.BooleanAttributePart,e.EventPart=s.EventPart,e.NodePart=s.NodePart,e.PropertyCommitter=s.PropertyCommitter,e.PropertyPart=s.PropertyPart,e.isIterable=s.isIterable,e.isPrimitive=s.isPrimitive,e.DefaultTemplateProcessor=m.DefaultTemplateProcessor,e.defaultTemplateProcessor=m.defaultTemplateProcessor,e.templateCaches=p.templateCaches,e.templateFactory=p.templateFactory,e.parts=n.parts,e.render=n.render,e.html=(e,...t)=>new o.TemplateResult(e,t,"html",m.defaultTemplateProcessor),e.svg=(e,...t)=>new o.SVGTemplateResult(e,t,"svg",m.defaultTemplateProcessor),Object.defineProperty(e,"__esModule",{value:!0})}));
