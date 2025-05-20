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
import{AjaxResponse as o}from"@typo3/core/ajax/ajax-response.js";import{InputTransformer as r}from"@typo3/core/ajax/input-transformer.js";class i{static{this.defaultOptions={credentials:"same-origin"}}constructor(e){this.url=e instanceof URL?e:new URL(e,window.location.origin+window.location.pathname),this.abortController=new AbortController,this.fetch=t=>fetch(t)}withQueryArguments(e){const t=this.clone();e instanceof URLSearchParams||(e=new URLSearchParams(r.toSearchParams(e)));for(const[s,n]of e.entries())this.url.searchParams.append(s,n);return t}async get(e={}){const t={method:"GET"},s=await this.send({...t,...e});return new o(s)}async post(e,t={}){const s={body:typeof e=="string"||e instanceof FormData?e:Object.keys(e).length?r.byHeader(e,t?.headers):"",cache:"no-cache",method:"POST"},n=await this.send({...s,...t});return new o(n)}async put(e,t={}){const s={body:typeof e=="string"||e instanceof FormData?e:r.byHeader(e,t?.headers),cache:"no-cache",method:"PUT"},n=await this.send({...s,...t});return new o(n)}async delete(e={},t={}){const s={cache:"no-cache",method:"DELETE"};typeof e=="string"&&e.length>0||e instanceof FormData?s.body=e:typeof e=="object"&&Object.keys(e).length>0&&(s.body=r.byHeader(e,t?.headers));const n=await this.send({...s,...t});return new o(n)}abort(){this.abortController.abort()}addMiddleware(e){if(Array.isArray(e))return e.forEach(s=>this.addMiddleware(s)),this;const t=this.fetch;return this.fetch=s=>e(s,t),this}clone(){return Object.assign(Object.create(this),this)}async send(e={}){const t=await this.fetch(new Request(this.url,this.getMergedOptions(e)));if(!t.ok)throw new o(t);return t}getMergedOptions(e){const{signal:t,...s}=e;return t?.addEventListener("abort",()=>this.abortController.abort()),{...i.defaultOptions,...s,signal:this.abortController.signal}}}export{i as default};
