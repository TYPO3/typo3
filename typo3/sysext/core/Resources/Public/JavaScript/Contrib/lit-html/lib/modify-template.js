define(["exports","./template"],(function(e,n){"use strict";
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
     */const t=e=>{let n=11===e.nodeType?0:1;const t=document.createTreeWalker(e,133,null,!1);for(;t.nextNode();)n++;return n},r=(e,t=-1)=>{for(let r=t+1;r<e.length;r++){const t=e[r];if(n.isTemplatePartActive(t))return r}return-1};e.insertNodeIntoTemplate=function(e,n,o=null){const{element:{content:l},parts:u}=e;if(null==o)return void l.appendChild(n);const d=document.createTreeWalker(l,133,null,!1);let c=r(u),i=0,s=-1;for(;d.nextNode();){s++;for(d.currentNode===o&&(i=t(n),o.parentNode.insertBefore(n,o));-1!==c&&u[c].index===s;){if(i>0){for(;-1!==c;)u[c].index+=i,c=r(u,c);return}c=r(u,c)}}},e.removeNodesFromTemplate=function(e,n){const{element:{content:t},parts:o}=e,l=document.createTreeWalker(t,133,null,!1);let u=r(o),d=o[u],c=-1,i=0;const s=[];let a=null;for(;l.nextNode();){c++;const e=l.currentNode;for(e.previousSibling===a&&(a=null),n.has(e)&&(s.push(e),null===a&&(a=e)),null!==a&&i++;void 0!==d&&d.index===c;)d.index=null!==a?-1:d.index-i,u=r(o,u),d=o[u]}s.forEach(e=>e.parentNode.removeChild(e))},Object.defineProperty(e,"__esModule",{value:!0})}));
