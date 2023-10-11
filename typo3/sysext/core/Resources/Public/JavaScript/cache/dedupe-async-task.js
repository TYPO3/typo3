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
export class DedupeAsyncTask{constructor(){this.promises={},this.results={}}async get(t,e,s){if(s?.aborted&&s.throwIfAborted(),t in this.results)return this.results[t];const r=this.getPromise(t,e);return s?await this.getAbortablePromise(t,r,s):await r}getPromise(t,e){if(t in this.promises)return this.promises[t].refCount++,this.promises[t].promise;const s=new AbortController,r=e(s.signal).then((e=>(this.results[t]=e,e))).finally((()=>{t in this.promises&&delete this.promises[t]}));return this.promises[t]={promise:r,abortController:s,refCount:1},r}getAbortablePromise(t,e,s){return new Promise(((r,o)=>{const i=()=>{t in this.promises&&--this.promises[t].refCount<1&&(this.promises[t].abortController.abort(),delete this.promises[t]);try{s.throwIfAborted()}catch(t){o(t)}};s.addEventListener("abort",i,{once:!0}),e.then((t=>{s.removeEventListener("abort",i),s.aborted||r(t)}),(t=>{s.removeEventListener("abort",i),o(t)}))}))}}