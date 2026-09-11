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
import{IntlMessageFormat as s}from"intl-messageformat";import{DateTime as l}from"luxon";class f{constructor(t){this.labels=t}get(t,e){const r=this.render(t,e);return Array.isArray(r)?r.join(""):r}render(t,e){if(!(t in this.labels))throw new Error("Label is not defined: "+String(t));const r=this.labels[t];if(e===void 0)return r;if(Array.isArray(e))return this.sprintf(r,e);const i=this.getFormatter(r).formatToParts(e);return i.length===1?i[0].value:i.map(o=>o.value)}sprintf(t,e){let r=0;return t.replace(/%[sdf]/g,i=>{const o=e[r++];switch(i){case"%s":return String(o);case"%d":return String(typeof o=="number"?o:parseInt(String(o),10));case"%f":return String(typeof o=="number"?o:parseFloat(o).toFixed(2));default:return i}})}getFormatter(t){return a("message",d)(t)}}const m=p();function d(n){const t=m?.timezone??void 0,e={short:{timeZone:t,dateStyle:"short"},medium:{timeZone:t,dateStyle:"medium"},long:{timeZone:t,dateStyle:"long"},full:{timeZone:t,dateStyle:"full"}},r={short:{timeZone:t,timeStyle:"short"},medium:{timeZone:t,timeStyle:"medium"},long:{timeZone:t,timeStyle:"long"},full:{timeZone:t,timeStyle:"full"}};return new s(n,y(),{date:e,time:r},{formatters:c})}const c={getNumberFormat:a("number",(n,t)=>new Intl.NumberFormat(n,t)),getDateTimeFormat:a("datetime",(n,t)=>{const{dateStyle:e,timeStyle:r,timeZone:i}=t;return m&&(e==="medium"||r==="medium")?{format:u=>l.fromJSDate(new Date(u),{zone:i}).setLocale(n).toFormat(e==="medium"&&r==="medium"?m.formats.datetime:e==="medium"?m.formats.date:m.formats.time)}:new Intl.DateTimeFormat(n,{dateStyle:e,timeStyle:r,timeZone:i})}),getPluralRules:a("date",(n,t)=>new Intl.PluralRules(n,t))},g={};function a(n,t){return((...e)=>{const r=JSON.stringify({context:n,args:e});return g[r]??=t(...e)})}function p(){try{return(typeof opener?.top?.TYPO3<"u"?opener.top:top).TYPO3.settings.DateConfiguration}catch{return null}}function y(){const n=document.documentElement.lang||"en";return n==="ch"?"zh":n}export{f as LabelProvider};
