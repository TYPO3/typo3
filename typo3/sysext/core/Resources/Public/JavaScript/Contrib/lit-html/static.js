import{html as A,svg as C,mathml as L}from"lit-html/lit-html.js";
/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const $e=Symbol.for(""),xe=t=>{if(t?.r===$e)return t?._$litStatic$},tr=t=>({_$litStatic$:t,r:$e}),er=(t,...r)=>({_$litStatic$:r.reduce(((r,e,a)=>r+(t=>{if(void 0!==t._$litStatic$)return t._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${t}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(e)+t[a+1]),t[0]),r:$e}),Te=new Map,Ee=t=>(r,...e)=>{const a=e.length;let o,s;const i=[],l=[];let n,u=0,c=!1;for(;u<a;){for(n=r[u];u<a&&void 0!==(s=e[u],o=xe(s));)n+=o+r[++u],c=!0;u!==a&&l.push(s),i.push(n),u++}if(u===a&&i.push(r[a]),c){const t=i.join("$$lit$$");void 0===(r=Te.get(t))&&(i.raw=i,Te.set(t,r=i)),e=l}return t(r,...e)},ke=Ee(A),Oe=Ee(C),Se=Ee(L);export{ke as html,er as literal,Se as mathml,Oe as svg,tr as unsafeStatic,Ee as withStatic};
