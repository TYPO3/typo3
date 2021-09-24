define(["exports","../lit-html","../directive"],(function(exports,litHtml,directive){"use strict";
/**
	 * @license
	 * Copyright 2018 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const o=directive.directive(class extends directive.Directive{constructor(t){var i;if(super(t),t.type!==directive.PartType.ATTRIBUTE||"class"!==t.name||(null===(i=t.strings)||void 0===i?void 0:i.length)>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter(i=>t[i]).join(" ")+" "}update(i,[s]){var r,o;if(void 0===this.st){this.st=new Set,void 0!==i.strings&&(this.et=new Set(i.strings.join(" ").split(/\s/).filter(t=>""!==t)));for(const t in s)s[t]&&!(null===(r=this.et)||void 0===r?void 0:r.has(t))&&this.st.add(t);return this.render(s)}const e=i.element.classList;this.st.forEach(t=>{t in s||(e.remove(t),this.st.delete(t))});for(const t in s){const i=!!s[t];i===this.st.has(t)||(null===(o=this.et)||void 0===o?void 0:o.has(t))||(i?(e.add(t),this.st.add(t)):(e.remove(t),this.st.delete(t)))}return litHtml.noChange}});exports.classMap=o,Object.defineProperty(exports,"__esModule",{value:!0})}));
