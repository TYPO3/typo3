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
export class CompletionResult{constructor(e,t){this.tsRef=e,this.tsTreeNode=t}getType(){const e=this.tsTreeNode.getValue();return this.tsRef.isType(e)?this.tsRef.getType(e):null}getFilteredProposals(e){const t={},s=[],o=this.tsTreeNode.getChildNodes(),r=this.tsTreeNode.getValue();for(const e in o)if(void 0!==o[e].value&&null!==o[e].value){const l={};l.word=e,this.tsRef.typeHasProperty(r,o[e].name)?(this.tsRef.cssClass="definedTSREFProperty",l.type=o[e].value):(l.cssClass="userProperty",this.tsRef.isType(o[e].value)?l.type=o[e].value:l.type=""),s.push(l),t[e]=!0}const l=this.tsRef.getPropertiesFromTypeId(this.tsTreeNode.getValue());for(const e in l)if(void 0!==l[e].value&&!0!==t[e]){const t={word:e,cssClass:"undefinedTSREFProperty",type:l[e].value};s.push(t)}const i=[];let n="";for(let t=0;t<s.length;t++)0!==e.length?(n=s[t].word.substring(0,e.length),n.toLowerCase()===e.toLowerCase()&&i.push(s[t])):i.push(s[t]);return i}}