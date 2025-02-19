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
import{AjaxResponse as s}from"@typo3/core/ajax/ajax-response.js";import{InputTransformer as r}from"@typo3/core/ajax/input-transformer.js";class a{static{this.defaultOptions={credentials:"same-origin"}}constructor(e){this.url=e instanceof URL?e:new URL(e,window.location.origin+window.location.pathname),this.abortController=new AbortController}withQueryArguments(e){const t=this.clone();e instanceof URLSearchParams||(e=new URLSearchParams(r.toSearchParams(e)));for(const[o,n]of e.entries())this.url.searchParams.append(o,n);return t}async get(e={}){const t={method:"GET"},o=await this.send({...t,...e});return new s(o)}async post(e,t={}){const o={body:typeof e=="string"||e instanceof FormData?e:r.byHeader(e,t?.headers),cache:"no-cache",method:"POST"},n=await this.send({...o,...t});return new s(n)}async put(e,t={}){const o={body:typeof e=="string"||e instanceof FormData?e:r.byHeader(e,t?.headers),cache:"no-cache",method:"PUT"},n=await this.send({...o,...t});return new s(n)}async delete(e={},t={}){const o={cache:"no-cache",method:"DELETE"};typeof e=="string"&&e.length>0||e instanceof FormData?o.body=e:typeof e=="object"&&Object.keys(e).length>0&&(o.body=r.byHeader(e,t?.headers));const n=await this.send({...o,...t});return new s(n)}abort(){this.abortController.abort()}clone(){return Object.assign(Object.create(this),this)}async send(e={}){const t=await fetch(this.url,this.getMergedOptions(e));if(!t.ok)throw new s(t);return t}getMergedOptions(e){const{signal:t,...o}=e;return t?.addEventListener("abort",()=>this.abortController.abort()),{...a.defaultOptions,...o,signal:this.abortController.signal}}}export{a as default};
