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
export default class AbstractClientStorage{constructor(){this.keyPrefix="t3-",this.storage=null}get(t){return null===this.storage?null:this.storage.getItem(this.keyPrefix+t)}getByPrefix(t){if(null===this.storage)return{};const e=Object.entries(this.storage).filter((e=>e[0].startsWith(this.keyPrefix+t))).map((t=>[t[0].substring(this.keyPrefix.length),t[1]]));return Object.fromEntries(e)}set(t,e){null!==this.storage&&this.storage.setItem(this.keyPrefix+t,e)}unset(t){null!==this.storage&&this.storage.removeItem(this.keyPrefix+t)}unsetByPrefix(t){null!==this.storage&&(t=this.keyPrefix+t,Object.keys(this.storage).filter((e=>e.startsWith(t))).forEach((t=>this.storage.removeItem(t))))}clear(){null!==this.storage&&this.storage.clear()}isset(t){return null!==this.storage&&null!==this.get(t)}}