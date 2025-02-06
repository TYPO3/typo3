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
import{marked}from"marked";import dompurify from"dompurify";import{html}from"lit";import{until}from"lit/directives/until.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";export const markdown=(r,t="default")=>html`${until(render(r,profiles[t]),r)}`;dompurify.addHook("afterSanitizeAttributes",(r=>{"target"in r&&!r.hasAttribute("target")&&r.setAttribute("target","_blank")}));const profiles={minimal:{markdown:{gfm:!0,pedantic:!1},dompurify:{ALLOWED_TAGS:["a","blockquote","br","code","li","p","pre","strong","ul","ol"],ALLOWED_ATTR:["href","target","title","role"]}},default:{markdown:{gfm:!0,pedantic:!1},dompurify:{USE_PROFILES:{html:!0}}}};async function render(r,t){let e,i;try{e=await marked.parse(r,{async:!0,...t.markdown})}catch(t){return console.error("Invalid Markdown",r,t),r}try{i=dompurify.sanitize(e,t.dompurify)}catch(t){return console.error("Invalid HTML",e,t),r}return unsafeHTML(i)}