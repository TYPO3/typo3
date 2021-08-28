define(["exports","./lit-html"],(function(exports,litHtml){"use strict";
/**
	 * @license
	 * Copyright 2020 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const i=new Map,a=t=>(e,...o)=>{var r;const a=o.length;let l,s;const n=[],u=[];let c,$=0,v=!1;for(;$<a;){for(c=e[$];$<a&&void 0!==(s=o[$],l=null===(r=s)||void 0===r?void 0:r._$litStatic$);)c+=l+e[++$],v=!0;u.push(s),n.push(c),$++}if($===a&&n.push(e[a]),v){const t=n.join("$$lit$$");void 0===(e=i.get(t))&&i.set(t,e=n),o=u}return t(e,...o)},l=a(litHtml.html),s=a(litHtml.svg);exports.html=l,exports.literal=(t,...e)=>({_$litStatic$:e.reduce((e,o,r)=>e+(t=>{if(void 0!==t._$litStatic$)return t._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${t}. Use 'unsafeStatic' to pass non-literal values, but\n            take care to ensure page security.`)})(o)+t[r+1],t[0])}),exports.svg=s,exports.unsafeStatic=t=>({_$litStatic$:t}),exports.withStatic=a,Object.defineProperty(exports,"__esModule",{value:!0})}));
