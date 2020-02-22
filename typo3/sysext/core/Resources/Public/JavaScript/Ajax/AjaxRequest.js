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
define(["require","exports","../BackwardCompat/JQueryNativePromises","./AjaxResponse","./ResponseError","./InputTransformer"],(function(e,t,s,n,r,o){"use strict";class a{constructor(e){this.queryArguments="",this.url=e,this.abortController=new AbortController,s.default.support()}withQueryArguments(e){const t=this.clone();return t.queryArguments=(""!==t.queryArguments?"&":"")+o.InputTransformer.toSearchParams(e),t}async get(e={}){const t=await this.send(Object.assign(Object.assign({},{method:"GET"}),e));return new n.AjaxResponse(t)}async post(e,t={}){const s={body:"string"==typeof e?e:o.InputTransformer.byHeader(e,null==t?void 0:t.headers),cache:"no-cache",method:"POST"},r=await this.send(Object.assign(Object.assign({},s),t));return new n.AjaxResponse(r)}async put(e,t={}){const s={body:"string"==typeof e?e:o.InputTransformer.byHeader(e,null==t?void 0:t.headers),cache:"no-cache",method:"PUT"},r=await this.send(Object.assign(Object.assign({},s),t));return new n.AjaxResponse(r)}async delete(e={},t={}){const s={cache:"no-cache",method:"DELETE"};"object"==typeof e&&Object.keys(e).length>0?s.body=o.InputTransformer.byHeader(e,null==t?void 0:t.headers):"string"==typeof e&&e.length>0&&(s.body=e);const r=await this.send(Object.assign(Object.assign({},s),t));return new n.AjaxResponse(r)}abort(){this.abortController.abort()}clone(){return Object.assign(Object.create(this),this)}async send(e={}){const t=await fetch(this.composeRequestUrl(),this.getMergedOptions(e));if(!t.ok)throw new r.ResponseError(t);return t}composeRequestUrl(){let e=this.url;if("?"===e.charAt(0)&&(e=window.location.origin+window.location.pathname+e),e=new URL(e,window.location.origin).toString(),""!==this.queryArguments){e+=(this.url.includes("?")?"&":"?")+this.queryArguments}return e}getMergedOptions(e){return Object.assign(Object.assign(Object.assign({},a.defaultOptions),e),{signal:this.abortController.signal})}}return a.defaultOptions={credentials:"same-origin"},a}));