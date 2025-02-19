/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import*as r from"css-tree";function p(t,n,e){const s=r.parse(t),i=u(e),c=o(n);return r.walk(s,l=>(c(l),i(l))),r.generate(s)}function o(t){return n=>{if(n.type!=="Url")return;const e=n;if(e.value.startsWith("data:")||e.value.startsWith("/")||e.value.includes("://"))return;const s=new URL(t.replace(/\?.+/,"")+"/../"+e.value,document.baseURI);e.value=s.pathname+s.search}}function u(t){return t===""?()=>{}:n=>{if(n.type!=="Selector")return;const e=n;if(e.children.isEmpty)return r.walk.skip;const s=r.parse(t+"{}"),i=r.find(s,l=>l.type==="Selector");if(i===null)throw new Error(`Failed to parse "${t}" as CSS prefix`);if(e.children.first.type==="PseudoClassSelector"&&e.children.first.name==="root")return e.children.shift(),e.children.prependList(i.children),r.walk.skip;let c=!1;return e.children.forEach((l,a)=>{l.type==="TypeSelector"&&["html","body"].includes(l.name.toLowerCase())&&(c?e.children.remove(a):(e.children.replace(a,i.children),c=!0))}),c||(e.children.unshift({type:"Combinator",loc:null,name:" "}),e.children.prependList(i.children)),r.walk.skip}}export{u as cssPrefixer,o as cssRelocator,p as prefixAndRebaseCss};
