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
class n{constructor(){this.promises={},this.results={}}async get(t,e,r){if(r?.aborted&&r.throwIfAborted(),t in this.results)return this.results[t];const s=this.getPromise(t,e);return r?await this.getAbortablePromise(t,s,r):await s}getPromise(t,e){if(t in this.promises)return this.promises[t].refCount++,this.promises[t].promise;const r=new AbortController,s=1,o=e(r.signal).then(i=>(this.results[t]=i,i)).finally(()=>{t in this.promises&&delete this.promises[t]});return this.promises[t]={promise:o,abortController:r,refCount:s},o}async getAbortablePromise(t,e,r){const s=new AbortController,o=new Promise(i=>{r.addEventListener("abort",()=>{t in this.promises&&--this.promises[t].refCount<1&&(this.promises[t].abortController.abort(),delete this.promises[t]),i()},{once:!0,signal:s.signal})});try{return await Promise.race([e,o]),r.throwIfAborted(),await e}finally{s.abort()}}}export{n as DedupeAsyncTask};
