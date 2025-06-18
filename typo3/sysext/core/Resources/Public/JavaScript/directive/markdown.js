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
import{marked as n}from"marked";import o from"dompurify";import{html as l}from"lit";import{until as m}from"lit/directives/until.js";import{unsafeHTML as u}from"lit/directives/unsafe-html.js";const f=(t,r="default")=>l`${m(d(t,s[r]),t)}`;o.addHook("afterSanitizeAttributes",t=>{"target"in t&&!t.hasAttribute("target")&&t.setAttribute("target","_blank")});const s={minimal:{markdown:{gfm:!0,pedantic:!1},dompurify:{ALLOWED_TAGS:["a","blockquote","br","code","kbd","li","p","pre","strong","ul","ol"],ALLOWED_ATTR:["href","target","title","role"]}},default:{markdown:{gfm:!0,pedantic:!1},dompurify:{USE_PROFILES:{html:!0}}}};async function d(t,r){let e,i;try{e=await n.parse(t,{async:!0,...r.markdown})}catch(a){return console.error("Invalid Markdown",t,a),t}try{i=o.sanitize(e,r.dompurify)}catch(a){return console.error("Invalid HTML",e,a),t}return u(i)}export{f as markdown};
