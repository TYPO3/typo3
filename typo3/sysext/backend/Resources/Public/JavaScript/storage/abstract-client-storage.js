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
class r{constructor(){this.keyPrefix="t3-",this.storage=null}get(t){return this.storage===null?null:this.storage.getItem(this.keyPrefix+t)}getByPrefix(t){if(this.storage===null)return{};const e=Object.entries(this.storage).filter(s=>s[0].startsWith(this.keyPrefix+t)).map(s=>[s[0].substring(this.keyPrefix.length),s[1]]);return Object.fromEntries(e)}set(t,e){this.storage!==null&&this.storage.setItem(this.keyPrefix+t,e)}unset(t){this.storage!==null&&this.storage.removeItem(this.keyPrefix+t)}unsetByPrefix(t){this.storage!==null&&(t=this.keyPrefix+t,Object.keys(this.storage).filter(e=>e.startsWith(t)).forEach(e=>this.storage.removeItem(e)))}clear(){this.storage!==null&&this.storage.clear()}isset(t){return this.storage===null?!1:this.get(t)!==null}}export{r as default};
