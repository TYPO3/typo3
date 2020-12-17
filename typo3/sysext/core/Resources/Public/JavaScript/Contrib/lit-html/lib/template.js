define(["exports"],(function(e){"use strict";
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
     */const t=`{{lit-${String(Math.random()).slice(2)}}}`,n=`\x3c!--${t}--\x3e`,s=new RegExp(`${t}|${n}`),r="$lit$";const o=(e,t)=>{const n=e.length-t.length;return n>=0&&e.slice(n)===t},i=()=>document.createComment(""),a=/([ \x09\x0a\x0c\x0d])([^\0-\x1F\x7F-\x9F "'>=/]+)([ \x09\x0a\x0c\x0d]*=[ \x09\x0a\x0c\x0d]*(?:[^ \x09\x0a\x0c\x0d"'`<>=]*|"[^"]*|'[^']*))$/;e.Template=class{constructor(e,n){this.parts=[],this.element=n;const l=[],c=[],d=document.createTreeWalker(n.content,133,null,!1);let x=0,u=-1,p=0;const{strings:f,values:{length:h}}=e;for(;p<h;){const e=d.nextNode();if(null!==e){if(u++,1===e.nodeType){if(e.hasAttributes()){const t=e.attributes,{length:n}=t;let i=0;for(let e=0;e<n;e++)o(t[e].name,r)&&i++;for(;i-- >0;){const t=f[p],n=a.exec(t)[2],o=n.toLowerCase()+r,i=e.getAttribute(o);e.removeAttribute(o);const l=i.split(s);this.parts.push({type:"attribute",index:u,name:n,strings:l}),p+=l.length-1}}"TEMPLATE"===e.tagName&&(c.push(e),d.currentNode=e.content)}else if(3===e.nodeType){const n=e.data;if(n.indexOf(t)>=0){const t=e.parentNode,c=n.split(s),d=c.length-1;for(let n=0;n<d;n++){let s,l=c[n];if(""===l)s=i();else{const e=a.exec(l);null!==e&&o(e[2],r)&&(l=l.slice(0,e.index)+e[1]+e[2].slice(0,-r.length)+e[3]),s=document.createTextNode(l)}t.insertBefore(s,e),this.parts.push({type:"node",index:++u})}""===c[d]?(t.insertBefore(i(),e),l.push(e)):e.data=c[d],p+=d}}else if(8===e.nodeType)if(e.data===t){const t=e.parentNode;null!==e.previousSibling&&u!==x||(u++,t.insertBefore(i(),e)),x=u,this.parts.push({type:"node",index:u}),null===e.nextSibling?e.data="":(l.push(e),u--),p++}else{let n=-1;for(;-1!==(n=e.data.indexOf(t,n+1));)this.parts.push({type:"node",index:-1}),p++}}else d.currentNode=c.pop()}for(const e of l)e.parentNode.removeChild(e)}},e.boundAttributeSuffix=r,e.createMarker=i,e.isTemplatePartActive=e=>-1!==e.index,e.lastAttributeNameRegex=a,e.marker=t,e.markerRegex=s,e.nodeMarker=n,Object.defineProperty(e,"__esModule",{value:!0})}));
