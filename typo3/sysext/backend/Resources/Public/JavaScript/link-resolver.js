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
import c from"@typo3/core/ajax/ajax-request.js";const i=new Map,o=new Map;function s(t){return!/^(https?|mailto|tel|ftp|sms):|^[/#?]/i.test(t.trim())}function r(t){const e=t.replace(/^(mailto|tel):/i,""),n=/^mailto:/i.test(t)?"actions-envelope":/^tel:/i.test(t)?"actions-phone":"actions-link";return{type:null,title:e,path:null,url:t,icon:n}}function u(t){if(t=t.trim(),!s(t))return Promise.resolve(r(t));const e=i.get(t);if(e!==void 0)return e;const n=new c(TYPO3.settings.ajaxUrls.link_preview).withQueryArguments({href:t}).get().then(l=>l.resolve()).then(l=>(o.set(t,l),l)).catch(()=>(i.delete(t),{type:null,title:null,path:null,url:null,icon:"actions-link"}));return i.set(t,n),n}function a(){i.clear(),o.clear()}function p(t){if(o.get(t.trim())?.url===null)return;const e=window.open("","_blank");e!==null&&(e.opener=null,u(t).then(n=>{n.url?e.location.href=n.url:e.close()}))}export{a as clearResolvedLinks,s as needsResolving,p as openResolvedLink,u as resolveLink};
