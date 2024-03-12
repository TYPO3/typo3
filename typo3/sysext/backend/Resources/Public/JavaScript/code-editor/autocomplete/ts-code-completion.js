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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{TsRef}from"@typo3/backend/code-editor/autocomplete/ts-ref.js";import{TsParser}from"@typo3/backend/code-editor/autocomplete/ts-parser.js";import{CompletionResult}from"@typo3/backend/code-editor/autocomplete/completion-result.js";export class TsCodeCompletion{constructor(e){this.extTsObjTree={},this.parser=null,this.proposals=null,this.compResult=null,this.tsRef=new TsRef,this.parser=new TsParser(this.tsRef,this.extTsObjTree),this.tsRef.loadTsrefAsync(),this.loadExtTemplatesAsync(e)}refreshCodeCompletion(e){const t=this.getFilter(e),s=this.parser.buildTsObjTree(e);this.compResult=new CompletionResult(this.tsRef,s),this.proposals=this.compResult.getFilteredProposals(t);const r=[];for(let e=0;e<this.proposals.length;e++)r[e]=this.proposals[e].word;return r}loadExtTemplatesAsync(e){if(Number.isNaN(e)||0===e)return null;new AjaxRequest(TYPO3.settings.ajaxUrls.codeeditor_codecompletion_loadtemplates).withQueryArguments({pageId:e}).get().then((async e=>{this.extTsObjTree.c=await e.resolve(),this.resolveExtReferencesRec(this.extTsObjTree.c)}))}resolveExtReferencesRec(e){for(const t of Object.keys(e)){let s;if(e[t].v&&e[t].v.startsWith("<")&&!e[t].v.includes(">")){const r=e[t].v.replace(/</,"").trim();-1===r.indexOf(" ")&&(s=this.getExtChildNode(r),null!==s&&(e[t]=s))}!s&&e[t].c&&this.resolveExtReferencesRec(e[t].c)}}getExtChildNode(e){let t=this.extTsObjTree;const s=e.split(".");for(let e=0;e<s.length;e++){const r=s[e];if(void 0===t.c||void 0===t.c[r])return null;t=t.c[r]}return t}getFilter(e){return e.completingAfterDot?"":e.token.string.replace(".","").replace(/\s/g,"")}}