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
define(["require","exports","../BackwardCompat/JQueryNativePromises","./AjaxResponse","./ResponseError","./InputTransformer"],(function(t,e,s,n,r,o){"use strict";class a{constructor(t){this.queryArguments="",this.url=t,this.abortController=new AbortController,s.default.support()}withQueryArguments(t){const e=this.clone();return e.queryArguments=(""!==e.queryArguments?"&":"")+o.InputTransformer.toSearchParams(t),e}async get(t={}){const e=await this.send(Object.assign(Object.assign({},{method:"GET"}),t));return new n.AjaxResponse(e)}async post(t,e={}){const s={body:"string"==typeof t?t:o.InputTransformer.toFormData(t),cache:"no-cache",method:"POST"},r=await this.send(Object.assign(Object.assign({},s),e));return new n.AjaxResponse(r)}async put(t,e={}){const s={body:"string"==typeof t?t:o.InputTransformer.toFormData(t),cache:"no-cache",method:"PUT"},r=await this.send(Object.assign(Object.assign({},s),e));return new n.AjaxResponse(r)}async delete(t={},e={}){const s={cache:"no-cache",method:"DELETE"};"object"==typeof t&&Object.keys(t).length>0?s.body=o.InputTransformer.toFormData(t):"string"==typeof t&&(s.body=t);const r=await this.send(Object.assign(Object.assign({},s),e));return new n.AjaxResponse(r)}getAbort(){return this.abortController}clone(){return Object.assign(Object.create(this),this)}async send(t={}){const e=await fetch(this.composeRequestUrl(),this.getMergedOptions(t));if(!e.ok)throw new r.ResponseError(e);return e}composeRequestUrl(){let t=this.url;if("?"===t.charAt(0)&&(t=window.location.origin+window.location.pathname+t),t=new URL(t,window.location.origin).toString(),""!==this.queryArguments){t+=(this.url.includes("?")?"&":"?")+this.queryArguments}return t}getMergedOptions(t){return Object.assign(Object.assign(Object.assign({},a.defaultOptions),t),{signal:this.abortController.signal})}}return a.defaultOptions={credentials:"same-origin"},a}));