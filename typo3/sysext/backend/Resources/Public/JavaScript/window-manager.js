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
import Utility from"@typo3/backend/utility.js";class WindowManager{constructor(){this.windows={},this.localOpen=(n,o,e="newTYPO3frontendWindow",t="")=>this._localOpen(n,o,e,t)}open(...n){return this._localOpen.apply(null,n)}globalOpen(...n){return this._localOpen.apply(null,n)}_localOpen(n,o,e="newTYPO3frontendWindow",t=""){if(!n)return null;null===o?o=!window.opener:void 0===o&&(o=!0);const i=this.windows[e]??window.open("",e,t);let a=!1;try{a="Window"===i.constructor.name}catch(n){}const l=a&&!i.closed?i.location.href:null;if(Utility.urlsPointToSameServerSideResource(n,l))return i.location.replace(n),i.location.reload(),i;const r=window.open(n,e,t);return this.windows[e]=r,o&&r.focus(),r}}const windowManager=new WindowManager;top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=windowManager:top.TYPO3.WindowManager=new WindowManager);export default windowManager;