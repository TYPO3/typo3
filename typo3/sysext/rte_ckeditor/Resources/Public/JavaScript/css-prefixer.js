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
import*as r from"css-tree";function p(n,s,e){const t=r.parse(n),i=u(e),a=o(s);return r.walk(t,l=>(a(l),i(l))),r.generate(t)}function o(n){return s=>{if(s.type!=="Url")return;const e=s;if(e.value.startsWith("data:")||e.value.startsWith("/")||e.value.includes("://"))return;const t=new URL(n.replace(/\?.+/,"")+"/../"+e.value,document.baseURI);e.value=t.pathname+t.search+t.hash}}function u(n){return n===""?()=>{}:s=>{if(s.type!=="Selector")return;const e=s;if(e.children.isEmpty)return r.walk.skip;const t=r.parse(n+"{}"),i=r.find(t,l=>l.type==="Selector");if(i===null)throw new Error(`Failed to parse "${n}" as CSS prefix`);if(e.children.first.type==="PseudoClassSelector"&&e.children.first.name==="root")return e.children.shift(),e.children.prependList(i.children),r.walk.skip;let a=!1;return e.children.forEach((l,c)=>{l.type==="TypeSelector"&&["html","body"].includes(l.name.toLowerCase())&&(a?e.children.remove(c):(e.children.replace(c,i.children),a=!0))}),a||(e.children.unshift({type:"Combinator",loc:null,name:" "}),e.children.prependList(i.children)),r.walk.skip}}export{u as cssPrefixer,o as cssRelocator,p as prefixAndRebaseCss};
