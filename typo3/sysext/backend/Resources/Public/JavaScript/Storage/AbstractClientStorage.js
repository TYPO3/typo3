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
define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0});t.default=class{constructor(){this.keyPrefix="t3-",this.storage=null}get(e){return null===this.storage?null:this.storage.getItem(this.keyPrefix+e)}set(e,t){null!==this.storage&&this.storage.setItem(this.keyPrefix+e,t)}unset(e){null!==this.storage&&this.storage.removeItem(this.keyPrefix+e)}unsetByPrefix(e){null!==this.storage&&(e=this.keyPrefix+e,Object.keys(this.storage).filter(t=>t.startsWith(e)).forEach(e=>this.storage.removeItem(e)))}clear(){null!==this.storage&&this.storage.clear()}isset(e){return null!==this.storage&&null!==this.get(e)}}}));