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
import*as csstree from"css-tree";export function prefixAndRebaseCss(e,r,t){const s=csstree.parse(e),n=cssPrefixer(t),c=cssRelocator(r);return csstree.walk(s,(e=>{n(e),c(e)})),csstree.generate(s)}export function cssRelocator(e){return r=>{if("Url"!==r.type)return;const t=r;if(t.value.startsWith("data:")||t.value.startsWith("/")||t.value.includes("://"))return;const s=new URL(e.replace(/\?.+/,"")+"/../"+t.value,document.baseURI);t.value=s.pathname+s.search}}export function cssPrefixer(e){return""===e?()=>{}:r=>{if("Selector"!==r.type)return;const t=r;if(t.children.isEmpty)return;const s=csstree.parse(e+"{}"),n=csstree.find(s,(e=>"Selector"===e.type));if(null===n)throw new Error(`Failed to parse "${e}" as CSS prefix`);if("PseudoClassSelector"===t.children.first.type&&"root"===t.children.first.name)return t.children.shift(),void t.children.prependList(n.children);let c=!1;t.children.forEach(((e,r)=>{"TypeSelector"===e.type&&["html","body"].includes(e.name.toLowerCase())&&(c?t.children.remove(r):(t.children.replace(r,n.children),c=!0))})),c||(t.children.unshift({type:"Combinator",loc:null,name:" "}),t.children.prependList(n.children))}}