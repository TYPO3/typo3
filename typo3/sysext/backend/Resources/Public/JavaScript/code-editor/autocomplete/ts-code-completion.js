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
import i from"@typo3/core/ajax/ajax-request.js";import{TsRef as l}from"@typo3/backend/code-editor/autocomplete/ts-ref.js";import{TsParser as n}from"@typo3/backend/code-editor/autocomplete/ts-parser.js";import{CompletionResult as p}from"@typo3/backend/code-editor/autocomplete/completion-result.js";class a{constructor(e){this.extTsObjTree={},this.parser=null,this.proposals=null,this.compResult=null,this.tsRef=new l,this.parser=new n(this.tsRef,this.extTsObjTree),this.tsRef.loadTsrefAsync(),this.loadExtTemplatesAsync(e)}refreshCodeCompletion(e){const t=this.getFilter(e),s=this.parser.buildTsObjTree(e);this.compResult=new p(this.tsRef,s),this.proposals=this.compResult.getFilteredProposals(t);const r=[];for(let o=0;o<this.proposals.length;o++)r[o]=this.proposals[o].word;return r}loadExtTemplatesAsync(e){if(Number.isNaN(e)||e===0)return null;new i(TYPO3.settings.ajaxUrls.codeeditor_codecompletion_loadtemplates).withQueryArguments({pageId:e}).get().then(async t=>{this.extTsObjTree.c=await t.resolve(),this.resolveExtReferencesRec(this.extTsObjTree.c)})}resolveExtReferencesRec(e){for(const t of Object.keys(e)){let s;if(e[t].v&&e[t].v.startsWith("<")&&!e[t].v.includes(">")){const r=e[t].v.replace(/</,"").trim();r.indexOf(" ")===-1&&(s=this.getExtChildNode(r),s!==null&&(e[t]=s))}!s&&e[t].c&&this.resolveExtReferencesRec(e[t].c)}}getExtChildNode(e){let t=this.extTsObjTree;const s=e.split(".");for(let r=0;r<s.length;r++){const o=s[r];if(typeof t.c>"u"||typeof t.c[o]>"u")return null;t=t.c[o]}return t}getFilter(e){return e.completingAfterDot?"":e.token.string.replace(".","").replace(/\s/g,"")}}export{a as TsCodeCompletion};
