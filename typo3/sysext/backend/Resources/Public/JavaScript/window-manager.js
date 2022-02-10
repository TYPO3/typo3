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
class WindowManager{constructor(){this.windows={},this.open=(...n)=>this._localOpen.apply(this,n),this.globalOpen=(...n)=>this._localOpen.apply(this,n),this.localOpen=(n,o,e="newTYPO3frontendWindow",i="")=>this._localOpen(n,o,e,i)}_localOpen(n,o,e="newTYPO3frontendWindow",i=""){if(!n)return null;null===o?o=!window.opener:void 0===o&&(o=!0);const a=this.windows[e];if((a instanceof Window&&!a.closed?a.location.href:null)===n)return a.location.reload(),a;const t=window.open(n,e,i);return this.windows[e]=t,o&&t.focus(),t}}const windowManager=new WindowManager;top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=windowManager:top.TYPO3.WindowManager=new WindowManager);export default windowManager;