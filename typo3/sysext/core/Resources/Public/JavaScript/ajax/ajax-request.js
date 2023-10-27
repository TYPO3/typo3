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
import{AjaxResponse}from"@typo3/core/ajax/ajax-response.js";import{InputTransformer}from"@typo3/core/ajax/input-transformer.js";class AjaxRequest{constructor(e){this.queryArguments="",this.url=e,this.abortController=new AbortController}withQueryArguments(e){const t=this.clone();return t.queryArguments=(""!==t.queryArguments?"&":"")+InputTransformer.toSearchParams(e),t}async get(e={}){const t=await this.send({method:"GET",...e});return new AjaxResponse(t)}async post(e,t={}){const r={body:"string"==typeof e||e instanceof FormData?e:InputTransformer.byHeader(e,t?.headers),cache:"no-cache",method:"POST"},n=await this.send({...r,...t});return new AjaxResponse(n)}async put(e,t={}){const r={body:"string"==typeof e||e instanceof FormData?e:InputTransformer.byHeader(e,t?.headers),cache:"no-cache",method:"PUT"},n=await this.send({...r,...t});return new AjaxResponse(n)}async delete(e={},t={}){const r={cache:"no-cache",method:"DELETE"};"string"==typeof e&&e.length>0||e instanceof FormData?r.body=e:"object"==typeof e&&Object.keys(e).length>0&&(r.body=InputTransformer.byHeader(e,t?.headers));const n=await this.send({...r,...t});return new AjaxResponse(n)}abort(){this.abortController.abort()}clone(){return Object.assign(Object.create(this),this)}async send(e={}){const t=await fetch(this.composeRequestUrl(),this.getMergedOptions(e));if(!t.ok)throw new AjaxResponse(t);return t}composeRequestUrl(){let e=this.url;if("?"===e.charAt(0)&&(e=window.location.origin+window.location.pathname+e),e=new URL(e,window.location.origin).toString(),""!==this.queryArguments){e+=(this.url.includes("?")?"&":"?")+this.queryArguments}return e}getMergedOptions(e){const{signal:t,...r}=e;return t?.addEventListener("abort",(()=>this.abortController.abort())),{...AjaxRequest.defaultOptions,...r,signal:this.abortController.signal}}}AjaxRequest.defaultOptions={credentials:"same-origin"};export default AjaxRequest;