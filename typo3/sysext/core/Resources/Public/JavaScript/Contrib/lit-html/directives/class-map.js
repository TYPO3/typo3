import{noChange as R}from"lit-html/lit-html.js";import{directive as e,Directive as i,PartType as t}from"lit-html/directive.js";
/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Rt=e(class extends i{constructor(s){if(super(s),s.type!==t.ATTRIBUTE||"class"!==s.name||s.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter((s=>t[s])).join(" ")+" "}update(t,[s]){if(void 0===this.st){this.st=new Set,void 0!==t.strings&&(this.nt=new Set(t.strings.join(" ").split(/\s/).filter((t=>""!==t))));for(const t in s)s[t]&&!this.nt?.has(t)&&this.st.add(t);return this.render(s)}const i=t.element.classList;for(const t of this.st)t in s||(i.remove(t),this.st.delete(t));for(const t in s){const r=!!s[t];r===this.st.has(t)||this.nt?.has(t)||(r?(i.add(t),this.st.add(t)):(i.remove(t),this.st.delete(t)))}return R}});export{Rt as classMap};
