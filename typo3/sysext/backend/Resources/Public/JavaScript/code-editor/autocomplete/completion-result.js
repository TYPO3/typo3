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
class d{constructor(t,n){this.tsRef=t,this.tsTreeNode=n}getType(){const t=this.tsTreeNode.getValue();return this.tsRef.isType(t)?this.tsRef.getType(t):null}getFilteredProposals(t){const n={},r=[],o=this.tsTreeNode.getChildNodes(),u=this.tsTreeNode.getValue();for(const e in o)if(typeof o[e].value<"u"&&o[e].value!==null){const s={};s.word=e,this.tsRef.typeHasProperty(u,o[e].name)?(this.tsRef.cssClass="definedTSREFProperty",s.type=o[e].value):(s.cssClass="userProperty",this.tsRef.isType(o[e].value)?s.type=o[e].value:s.type=""),r.push(s),n[e]=!0}const i=this.tsRef.getPropertiesFromTypeId(this.tsTreeNode.getValue());for(const e in i)if(typeof i[e].value<"u"&&n[e]!==!0){const s={word:e,cssClass:"undefinedTSREFProperty",type:i[e].value};r.push(s)}const l=[];let p="";for(let e=0;e<r.length;e++){if(t.length===0){l.push(r[e]);continue}p=r[e].word.substring(0,t.length),p.toLowerCase()===t.toLowerCase()&&l.push(r[e])}return l}}export{d as CompletionResult};
