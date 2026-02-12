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
class i{constructor(e){this.labels=e}get(e,...n){if(!(e in this.labels))throw new Error("Label is not defined: "+String(e));let s=0;return this.labels[e].replace(/%[sdf]/g,t=>{const r=n[s++];switch(t){case"%s":return String(r);case"%d":return String(typeof r=="number"?r:parseInt(String(r),10));case"%f":return String(typeof r=="number"?r:parseFloat(r).toFixed(2));default:return t}})}}export{i as LabelProvider};
