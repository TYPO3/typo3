define(["exports","../lib/directive","../lib/dom","../lib/template","../lib/parts","../lit-html"],(function(e,t,o,n,r,s){"use strict";
/**
     * @license
     * Copyright (c) 2017 The Polymer Project Authors. All rights reserved.
     * This code may only be used under the BSD style license found at
     * http://polymer.github.io/LICENSE.txt
     * The complete set of authors may be found at
     * http://polymer.github.io/AUTHORS.txt
     * The complete set of contributors may be found at
     * http://polymer.github.io/CONTRIBUTORS.txt
     * Code distributed by Google as part of the polymer project is also
     * subject to an additional IP rights grant found at
     * http://polymer.github.io/PATENTS.txt
     */const i=(e,t)=>{const o=e.startNode.parentNode,s=void 0===t?e.endNode:t.startNode,i=o.insertBefore(n.createMarker(),s);o.insertBefore(n.createMarker(),s);const l=new r.NodePart(e.options);return l.insertAfterNode(i),l},l=(e,t)=>(e.setValue(t),e.commit(),e),d=(e,t,n)=>{const r=e.startNode.parentNode,s=n?n.startNode:e.endNode,i=t.endNode.nextSibling;i!==s&&o.reparentNodes(r,t.startNode,i,s)},a=e=>{o.removeNodes(e.startNode.parentNode,e.startNode,e.endNode.nextSibling)},f=(e,t,o)=>{const n=new Map;for(let r=t;r<=o;r++)n.set(e[r],r);return n},c=new WeakMap,N=new WeakMap,u=t.directive((e,t,o)=>{let n;return void 0===o?o=t:void 0!==t&&(n=t),t=>{if(!(t instanceof r.NodePart))throw new Error("repeat can only be used in text bindings");const s=c.get(t)||[],u=N.get(t)||[],p=[],b=[],v=[];let g,h,m=0;for(const t of e)v[m]=n?n(t,m):m,b[m]=o(t,m),m++;let w=0,M=s.length-1,k=0,x=b.length-1;for(;w<=M&&k<=x;)if(null===s[w])w++;else if(null===s[M])M--;else if(u[w]===v[k])p[k]=l(s[w],b[k]),w++,k++;else if(u[M]===v[x])p[x]=l(s[M],b[x]),M--,x--;else if(u[w]===v[x])p[x]=l(s[w],b[x]),d(t,s[w],p[x+1]),w++,x--;else if(u[M]===v[k])p[k]=l(s[M],b[k]),d(t,s[M],s[w]),M--,k++;else if(void 0===g&&(g=f(v,k,x),h=f(u,w,M)),g.has(u[w]))if(g.has(u[M])){const e=h.get(v[k]),o=void 0!==e?s[e]:null;if(null===o){const e=i(t,s[w]);l(e,b[k]),p[k]=e}else p[k]=l(o,b[k]),d(t,o,s[w]),s[e]=null;k++}else a(s[M]),M--;else a(s[w]),w++;for(;k<=x;){const e=i(t,p[x+1]);l(e,b[k]),p[k++]=e}for(;w<=M;){const e=s[w++];null!==e&&a(e)}c.set(t,p),N.set(t,v)}});e.repeat=u,Object.defineProperty(e,"__esModule",{value:!0})}));
