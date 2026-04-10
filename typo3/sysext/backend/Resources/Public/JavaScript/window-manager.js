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
import c from"@typo3/backend/utility.js";import{topWindow as t}from"@typo3/core/utility/top-window.js";class d{constructor(){this.windows={},this.localOpen=(n,o,i="newTYPO3frontendWindow",l="")=>this._localOpen(n,o,i,l)}open(...n){return this._localOpen.apply(null,n)}globalOpen(...n){return this._localOpen.apply(null,n)}_localOpen(n,o,i="newTYPO3frontendWindow",l=""){if(!n)return null;o===null?o=!window.opener:o===void 0&&(o=!0);const e=this.windows[i]??window.open("",i,l);let a=!1;try{a=e.constructor.name==="Window"}catch{}const w=a&&!e.closed?e.location.href:null;if(c.urlsPointToSameServerSideResource(n,w))return e.location.replace(n),e.location.reload(),e.focus(),e;const r=window.open(n,i,l);return this.windows[i]=r,o&&r.focus(),r}}const s=new d;t.TYPO3.WindowManager||(t.document===window.document?t.TYPO3.WindowManager=s:t.TYPO3.WindowManager=new d);export{s as default};
