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
import h from"@typo3/core/ajax/ajax-request.js";class d{constructor(e,s,t){this.properties={},this.typeId=e,this.extends=s,this.properties=t}}class o{constructor(e,s,t){this.parentType=e,this.name=s,this.value=t}}class f{constructor(){this.typeTree={},this.doc=null}async loadTsrefAsync(){const e=await new h(TYPO3.settings.ajaxUrls.codeeditor_tsref).get();this.doc=await e.resolve(),this.buildTree()}buildTree(){for(const e of Object.keys(this.doc)){const s=this.doc[e];this.typeTree[e]=new d(e,s.extends||void 0,Object.fromEntries(Object.entries(s.properties).map(([t,r])=>[t,new o(e,t,r.type)])))}for(const e of Object.keys(this.typeTree))typeof this.typeTree[e].extends<"u"&&this.addPropertiesToType(this.typeTree[e],this.typeTree[e].extends,100)}addPropertiesToType(e,s,t){if(t<0)throw"Maximum recursion depth exceeded while trying to resolve the extends in the TSREF!";const r=s.split(",");for(let i=0;i<r.length;i++)if(typeof this.typeTree[r[i]]<"u"){typeof this.typeTree[r[i]].extends<"u"&&this.addPropertiesToType(this.typeTree[r[i]],this.typeTree[r[i]].extends,t-1);const y=this.typeTree[r[i]].properties;for(const p in y)typeof e.properties[p]>"u"&&(e.properties[p]=y[p])}}getPropertiesFromTypeId(e){return typeof this.typeTree[e]<"u"?(this.typeTree[e].properties.clone=function(){const s={};for(const t of Object.keys(this))s[t]=new o(this[t].parentType,this[t].name,this[t].value);return s},this.typeTree[e].properties):{}}typeHasProperty(e,s){return typeof this.typeTree[e]<"u"&&typeof this.typeTree[e].properties[s]<"u"}getType(e){return this.typeTree[e]}isType(e){return typeof this.typeTree[e]<"u"}}export{f as TsRef,o as TsRefProperty,d as TsRefType};
