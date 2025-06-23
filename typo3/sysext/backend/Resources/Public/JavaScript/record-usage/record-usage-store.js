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
import s from"@typo3/backend/storage/client.js";const r="record-usage/";class a{constructor(e){this.storeName=e}track(e){const t=this.load();t.usage[e]={lastUsed:Date.now(),count:(t.usage[e]?.count??0)+1},this.save(t)}getUsage(){return this.load().usage}load(){const e=s.get(this.localStorageKey());return e===null?{usage:{}}:this.removeOldItems(JSON.parse(e))}save(e){s.set(this.localStorageKey(),JSON.stringify(e))}removeOldItems(e){const t=Date.now()-2592e6;return e.usage=Object.fromEntries(Object.entries(e.usage).filter(([,o])=>o.lastUsed>=t)),e}localStorageKey(){return r+this.storeName}}export{a as RecordUsageStore};
