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
import h from"@typo3/core/ajax/ajax-request.js";class d{constructor(e,r,t){this.properties={},this.typeId=e,this.extends=r,this.properties=t}}class o{constructor(e,r,t){this.parentType=e,this.name=r,this.value=t}}class f{constructor(){this.typeTree={},this.doc=null}async loadTsrefAsync(){const e=await new h(TYPO3.settings.ajaxUrls.codeeditor_tsref).get();this.doc=await e.resolve(),this.buildTree()}buildTree(){for(const e of Object.keys(this.doc)){const r=this.doc[e];this.typeTree[e]=new d(e,r.extends||void 0,Object.fromEntries(Object.entries(r.properties).map(([t,s])=>[t,new o(e,t,s.type)])))}for(const e of Object.keys(this.typeTree))typeof this.typeTree[e].extends<"u"&&this.addPropertiesToType(this.typeTree[e],this.typeTree[e].extends,100)}addPropertiesToType(e,r,t){if(t<0)throw new Error("Maximum recursion depth exceeded while trying to resolve the extends in the TSREF!");const s=r.split(",");for(let i=0;i<s.length;i++)if(typeof this.typeTree[s[i]]<"u"){typeof this.typeTree[s[i]].extends<"u"&&this.addPropertiesToType(this.typeTree[s[i]],this.typeTree[s[i]].extends,t-1);const y=this.typeTree[s[i]].properties;for(const p in y)typeof e.properties[p]>"u"&&(e.properties[p]=y[p])}}getPropertiesFromTypeId(e){return typeof this.typeTree[e]<"u"?(this.typeTree[e].properties.clone=function(){const r={};for(const t of Object.keys(this))r[t]=new o(this[t].parentType,this[t].name,this[t].value);return r},this.typeTree[e].properties):{}}typeHasProperty(e,r){return typeof this.typeTree[e]<"u"&&typeof this.typeTree[e].properties[r]<"u"}getType(e){return this.typeTree[e]}isType(e){return typeof this.typeTree[e]<"u"}}export{f as TsRef,o as TsRefProperty,d as TsRefType};
