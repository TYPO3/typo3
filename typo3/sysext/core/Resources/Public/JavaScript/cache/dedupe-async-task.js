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
class h{constructor(){this.promises={},this.results={}}async get(t,o,r){if(r?.aborted&&r.throwIfAborted(),t in this.results)return this.results[t];const s=this.getPromise(t,o);return r?await this.getAbortablePromise(t,s,r):await s}getPromise(t,o){if(t in this.promises)return this.promises[t].refCount++,this.promises[t].promise;const r=new AbortController,s=1,i=o(r.signal).then(e=>(this.results[t]=e,e)).finally(()=>{t in this.promises&&delete this.promises[t]});return this.promises[t]={promise:i,abortController:r,refCount:s},i}getAbortablePromise(t,o,r){return new Promise((s,i)=>{const e=()=>{t in this.promises&&--this.promises[t].refCount<1&&(this.promises[t].abortController.abort(),delete this.promises[t]);try{r.throwIfAborted()}catch(n){i(n)}};r.addEventListener("abort",e,{once:!0}),o.then(n=>{r.removeEventListener("abort",e),r.aborted||s(n)},n=>{r.removeEventListener("abort",e),i(n)})})}}export{h as DedupeAsyncTask};
