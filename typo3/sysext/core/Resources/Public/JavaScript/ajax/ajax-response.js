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
export class AjaxResponse{constructor(s){this.response=s}async resolve(s){if(void 0!==this.resolvedBody)return this.resolvedBody;const e=this.response.headers.get("Content-Type")??"";return"json"===s||e.startsWith("application/json")?this.resolvedBody=await this.response.json():this.resolvedBody=await this.response.text(),this.resolvedBody}raw(){return this.response}async dereference(){const s=new Map;return this.response.headers.forEach(((e,t)=>s.set(t,e))),{status:this.response.status,headers:s,body:await this.resolve()}}}