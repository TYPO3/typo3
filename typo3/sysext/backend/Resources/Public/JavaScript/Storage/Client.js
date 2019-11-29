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
define(["require","exports"],(function(e,t){"use strict";class s{constructor(){this.keyPrefix="t3-",this.get=e=>s.isCapable()?localStorage.getItem(this.keyPrefix+e):null,this.set=(e,t)=>{s.isCapable()&&localStorage.setItem(this.keyPrefix+e,t)},this.unset=e=>{s.isCapable()&&localStorage.removeItem(this.keyPrefix+e)},this.unsetByPrefix=e=>{if(!s.isCapable())return;e=this.keyPrefix+e;const t=[];for(let s=0;s<localStorage.length;++s)if(localStorage.key(s).substring(0,e.length)===e){const e=localStorage.key(s).substr(this.keyPrefix.length);t.push(e)}for(let e of t)this.unset(e)},this.clear=()=>{s.isCapable()&&localStorage.clear()},this.isset=e=>{if(s.isCapable()){const t=this.get(e);return null!=t}return!1}}static isCapable(){return null!==localStorage}}return new s}));