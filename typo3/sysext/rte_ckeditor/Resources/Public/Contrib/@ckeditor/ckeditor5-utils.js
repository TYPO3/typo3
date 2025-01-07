import{isObject as t,isString as e,isPlainObject as n,cloneDeepWith as r,isElement as o,isFunction as i,merge as s}from"lodash-es";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */let l;try{l={window,document}}catch(t){
/* istanbul ignore next -- @preserve */
l={window:{},document:{}}}var c=l;
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function a(){try{return navigator.userAgent.toLowerCase()}catch(t){return""}}const u=a(),h={isMac:f(u),isWindows:d(u),isGecko:p(u),isSafari:m(u),isiOS:g(u),isAndroid:b(u),isBlink:_(u),get isMediaForcedColors(){return!!c.window.matchMedia&&c.window.matchMedia("(forced-colors: active)").matches},get isMotionReduced(){return!!c.window.matchMedia&&c.window.matchMedia("(prefers-reduced-motion)").matches},features:{isRegExpUnicodePropertySupported:w()}};function f(t){return t.indexOf("macintosh")>-1}function d(t){return t.indexOf("windows")>-1}function p(t){return!!t.match(/gecko\/\d+/)}function m(t){return t.indexOf(" applewebkit/")>-1&&-1===t.indexOf("chrome")}function g(t){return!!t.match(/iphone|ipad/i)||f(t)&&navigator.maxTouchPoints>0}function b(t){return t.indexOf("android")>-1}function _(t){return t.indexOf("chrome/")>-1&&t.indexOf("edge/")<0}function w(){let t=!1;try{t=0==="ć".search(new RegExp("[\\p{L}]","u"))}catch(t){}return t}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
function y(t,e,n,r){n=n||function(t,e){return t===e};const o=Array.isArray(t)?t:Array.prototype.slice.call(t),i=Array.isArray(e)?e:Array.prototype.slice.call(e),s=function(t,e,n){const r=v(t,e,n);if(-1===r)return{firstIndex:-1,lastIndexOld:-1,lastIndexNew:-1};const o=E(t,r),i=E(e,r),s=v(o,i,n),l=t.length-s,c=e.length-s;return{firstIndex:r,lastIndexOld:l,lastIndexNew:c}}(o,i,n),l=r?function(t,e){const{firstIndex:n,lastIndexOld:r,lastIndexNew:o}=t;if(-1===n)return Array(e).fill("equal");let i=[];n>0&&(i=i.concat(Array(n).fill("equal")));o-n>0&&(i=i.concat(Array(o-n).fill("insert")));r-n>0&&(i=i.concat(Array(r-n).fill("delete")));o<e&&(i=i.concat(Array(e-o).fill("equal")));return i}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */(s,i.length):function(t,e){const n=[],{firstIndex:r,lastIndexOld:o,lastIndexNew:i}=e;i-r>0&&n.push({index:r,type:"insert",values:t.slice(r,i)});o-r>0&&n.push({index:r+(i-r),type:"delete",howMany:o-r});return n}(i,s);return l}function v(t,e,n){for(let r=0;r<Math.max(t.length,e.length);r++)if(void 0===t[r]||void 0===e[r]||!n(t[r],e[r]))return r;return-1}function E(t,e){return t.slice(e).reverse()}function T(t,e,n){n=n||function(t,e){return t===e};const r=t.length,o=e.length;if(r>200||o>200||r+o>300)return T.fastDiff(t,e,n,!0);let i,s;if(o<r){const n=t;t=e,e=n,i="delete",s="insert"}else i="insert",s="delete";const l=t.length,c=e.length,a=c-l,u={},h={};function f(r){const o=(void 0!==h[r-1]?h[r-1]:-1)+1,a=void 0!==h[r+1]?h[r+1]:-1,f=o>a?-1:1;u[r+f]&&(u[r]=u[r+f].slice(0)),u[r]||(u[r]=[]),u[r].push(o>a?i:s);let d=Math.max(o,a),p=d-r;for(;p<l&&d<c&&n(t[p],e[d]);)p++,d++,u[r].push("equal");return d}let d,p=0;do{for(d=-p;d<a;d++)h[d]=f(d);for(d=a+p;d>a;d--)h[d]=f(d);h[a]=f(a),p++}while(h[a]!==c);return u[a].slice(1)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
function x(t,e){const n=[];let r=0,o=null;return t.forEach((t=>{"equal"==t?(i(),r++):"insert"==t?(o&&"insert"==o.type?o.values.push(e[r]):(i(),o={type:"insert",index:r,values:[e[r]]}),r++):o&&"delete"==o.type?o.howMany++:(i(),o={type:"delete",index:r,howMany:1})})),i(),n;function i(){o&&(n.push(o),o=null)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function k(t,...e){e.forEach((e=>{const n=Object.getOwnPropertyNames(e),r=Object.getOwnPropertySymbols(e);n.concat(r).forEach((n=>{if(n in t.prototype)return;if("function"==typeof e&&("length"==n||"name"==n||"prototype"==n))return;const r=Object.getOwnPropertyDescriptor(e,n);r.enumerable=!1,Object.defineProperty(t.prototype,n,r)}))}))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */T.fastDiff=y;
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
class I{constructor(t,e){this.source=t,this.name=e,this.path=[],this.stop=function t(){t.called=!0},this.off=function t(){t.called=!0}}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const O=new Array(256).fill("").map(((t,e)=>("0"+e.toString(16)).slice(-2)));function L(){const[t,e,n,r]=crypto.getRandomValues(new Uint32Array(4));return"e"+O[255&t]+O[t>>8&255]+O[t>>16&255]+O[t>>24&255]+O[255&e]+O[e>>8&255]+O[e>>16&255]+O[e>>24&255]+O[255&n]+O[n>>8&255]+O[n>>16&255]+O[n>>24&255]+O[255&r]+O[r>>8&255]+O[r>>16&255]+O[r>>24&255]}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const A={get(t="normal"){return"number"!=typeof t?this[t]||this.normal:t},highest:1e5,high:1e3,normal:0,low:-1e3,lowest:-1e5};
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function C(t,e){const n=A.get(e.priority);let r=0,o=t.length;for(;r<o;){const e=r+o>>1;A.get(t[e].priority)<n?o=e:r=e+1}t.splice(r,0,e)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const M="https://ckeditor.com/docs/ckeditor5/latest/support/error-codes.html";class S extends Error{constructor(t,e,n){super(function(t,e){const n=new WeakSet,r=(t,e)=>{if("object"==typeof e&&null!==e){if(n.has(e))return`[object ${e.constructor.name}]`;n.add(e)}return e},o=e?` ${JSON.stringify(e,r)}`:"",i=j(t);return t+o+i}(t,n)),this.name="CKEditorError",this.context=e,this.data=n}is(t){return"CKEditorError"===t}static rethrowUnexpectedError(t,e){if(t.is&&t.is("CKEditorError"))throw t;const n=new S(t.message,e);throw n.stack=t.stack,n}}function R(t,e){console.warn(...P(t,e))}function N(t,e){console.error(...P(t,e))}function j(t){return`\nRead more: ${M}#error-${t}`}function P(t,e){const n=j(t);return e?[t,e,n]:[t,n]}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const D="44.1.0",B=new Date(2024,11,16);
/* istanbul ignore next -- @preserve */
if(globalThis.CKEDITOR_VERSION)throw new S("ckeditor-duplicated-modules",null);
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */globalThis.CKEDITOR_VERSION=D;const F=Symbol("listeningTo"),V=Symbol("emitterId"),W=Symbol("delegations"),H=K(Object);function K(t){if(!t)return H;return class extends t{on(t,e,n){this.listenTo(this,t,e,n)}once(t,e,n){let r=!1;this.listenTo(this,t,((t,...n)=>{r||(r=!0,t.off(),e.call(this,t,...n))}),n)}off(t,e){this.stopListening(this,t,e)}listenTo(t,e,n,r={}){let o,i;this[F]||(this[F]={});const s=this[F];Y(t)||U(t);const l=Y(t);(o=s[l])||(o=s[l]={emitter:t,callbacks:{}}),(i=o.callbacks[e])||(i=o.callbacks[e]=[]),i.push(n),function(t,e,n,r,o){e._addEventListener?e._addEventListener(n,r,o):t._addEventListener.call(e,n,r,o)}(this,t,e,n,r)}stopListening(t,e,n){const r=this[F];let o=t&&Y(t);const i=r&&o?r[o]:void 0,s=i&&e?i.callbacks[e]:void 0;if(!(!r||t&&!i||e&&!s))if(n){G(this,t,e,n);-1!==s.indexOf(n)&&(1===s.length?delete i.callbacks[e]:G(this,t,e,n))}else if(s){for(;n=s.pop();)G(this,t,e,n);delete i.callbacks[e]}else if(i){for(e in i.callbacks)this.stopListening(t,e);delete r[o]}else{for(o in r)this.stopListening(r[o].emitter);delete this[F]}}fire(t,...e){try{const n=t instanceof I?t:new I(this,t),r=n.name;let o=function(t,e){if(!t._events)return null;let n=e;do{const e=t._events[n];if(e&&e.callbacks&&e.callbacks.length)return e.callbacks;const r=n.lastIndexOf(":");n=r>-1?n.substring(0,r):""}while(n);return null}(this,r);if(n.path.push(this),o){o=o.slice();for(let t=0;t<o.length;t++){const i=o[t].callback;if(i.call(this,n,...e),n.off.called&&(delete n.off.called,this._removeEventListener(r,i)),n.stop.called)break}}const i=this[W];if(i){const t=i.get(r),o=i.get("*");t&&z(t,n,e),o&&z(o,n,e)}return n.return}catch(t){
/* istanbul ignore next -- @preserve */
S.rethrowUnexpectedError(t,this)}}delegate(...t){return{to:(e,n)=>{this[W]||(this[W]=new Map),t.forEach((t=>{const r=this[W].get(t);r?r.set(e,n):this[W].set(t,new Map([[e,n]]))}))}}}stopDelegating(t,e){if(this[W])if(t)if(e){const n=this[W].get(t);n&&n.delete(e)}else this[W].delete(t);else this[W].clear()}_addEventListener(t,e,n){!function(t,e){const n=q(t);if(n[e])return;let r=e,o=null;const i=[];for(;""!==r&&!n[r];)n[r]={callbacks:[],childEvents:[]},i.push(n[r]),o&&n[r].childEvents.push(o),o=r,r=r.substr(0,r.lastIndexOf(":"));if(""!==r){for(const t of i)t.callbacks=n[r].callbacks.slice();n[r].childEvents.push(o)}}(this,t);const r=$(this,t),o={callback:e,priority:A.get(n.priority)};for(const t of r)C(t,o)}_removeEventListener(t,e){const n=$(this,t);for(const t of n)for(let n=0;n<t.length;n++)t[n].callback==e&&(t.splice(n,1),n--)}}}function U(t,e){t[V]||(t[V]=e||L())}function Y(t){return t[V]}function q(t){return t._events||Object.defineProperty(t,"_events",{value:{}}),t._events}function $(t,e){const n=q(t)[e];if(!n)return[];let r=[n.callbacks];for(let e=0;e<n.childEvents.length;e++){const o=$(t,n.childEvents[e]);r=r.concat(o)}return r}function z(t,e,n){for(let[r,o]of t){o?"function"==typeof o&&(o=o(e.name)):o=e.name;const t=new I(e.source,o);t.path=[...e.path],r.fire(t,...n)}}function G(t,e,n,r){e._removeEventListener?e._removeEventListener(n,r):t._removeEventListener.call(e,n,r)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */["on","once","off","listenTo","stopListening","fire","delegate","stopDelegating","_addEventListener","_removeEventListener"].forEach((t=>{K[t]=H.prototype[t]}));const X=Symbol("observableProperties"),J=Symbol("boundObservables"),Q=Symbol("boundProperties"),Z=Symbol("decoratedMethods"),tt=Symbol("decoratedOriginal"),et=nt(K());function nt(e){if(!e)return et;return class extends e{set(e,n){if(t(e))return void Object.keys(e).forEach((t=>{this.set(t,e[t])}),this);rt(this);const r=this[X];if(e in this&&!r.has(e))throw new S("observable-set-cannot-override",this);Object.defineProperty(this,e,{enumerable:!0,configurable:!0,get:()=>r.get(e),set(t){const n=r.get(e);let o=this.fire(`set:${e}`,e,t,n);void 0===o&&(o=t),n===o&&r.has(e)||(r.set(e,o),this.fire(`change:${e}`,e,o,n))}}),this[e]=n}bind(...t){if(!t.length||!st(t))throw new S("observable-bind-wrong-properties",this);if(new Set(t).size!==t.length)throw new S("observable-bind-duplicate-properties",this);rt(this);const e=this[Q];t.forEach((t=>{if(e.has(t))throw new S("observable-bind-rebind",this)}));const n=new Map;return t.forEach((t=>{const r={property:t,to:[]};e.set(t,r),n.set(t,r)})),{to:ot,toMany:it,_observable:this,_bindProperties:t,_to:[],_bindings:n}}unbind(...t){if(!this[X])return;const e=this[Q],n=this[J];if(t.length){if(!st(t))throw new S("observable-unbind-wrong-properties",this);t.forEach((t=>{const r=e.get(t);r&&(r.to.forEach((([t,e])=>{const o=n.get(t),i=o[e];i.delete(r),i.size||delete o[e],Object.keys(o).length||(n.delete(t),this.stopListening(t,"change"))})),e.delete(t))}))}else n.forEach(((t,e)=>{this.stopListening(e,"change")})),n.clear(),e.clear()}decorate(t){rt(this);const e=this[t];if(!e)throw new S("observablemixin-cannot-decorate-undefined",this,{object:this,methodName:t});this.on(t,((t,n)=>{t.return=e.apply(this,n)})),this[t]=function(...e){return this.fire(t,e)},this[t][tt]=e,this[Z]||(this[Z]=[]),this[Z].push(t)}stopListening(t,e,n){if(!t&&this[Z]){for(const t of this[Z])this[t]=this[t][tt];delete this[Z]}super.stopListening(t,e,n)}}}function rt(t){t[X]||(Object.defineProperty(t,X,{value:new Map}),Object.defineProperty(t,J,{value:new Map}),Object.defineProperty(t,Q,{value:new Map}))}function ot(...t){const e=function(...t){if(!t.length)throw new S("observable-bind-to-parse-error",null);const e={to:[]};let n;"function"==typeof t[t.length-1]&&(e.callback=t.pop());return t.forEach((t=>{if("string"==typeof t)n.properties.push(t);else{if("object"!=typeof t)throw new S("observable-bind-to-parse-error",null);n={observable:t,properties:[]},e.to.push(n)}})),e}(...t),n=Array.from(this._bindings.keys()),r=n.length;if(!e.callback&&e.to.length>1)throw new S("observable-bind-to-no-callback",this);if(r>1&&e.callback)throw new S("observable-bind-to-extra-callback",this);var o;
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */e.to.forEach((t=>{if(t.properties.length&&t.properties.length!==r)throw new S("observable-bind-to-properties-length",this);t.properties.length||(t.properties=this._bindProperties)})),this._to=e.to,e.callback&&(this._bindings.get(n[0]).callback=e.callback),o=this._observable,this._to.forEach((t=>{const e=o[J];let n;e.get(t.observable)||o.listenTo(t.observable,"change",((r,i)=>{n=e.get(t.observable)[i],n&&n.forEach((t=>{lt(o,t.property)}))}))})),function(t){let e;t._bindings.forEach(((n,r)=>{t._to.forEach((o=>{e=o.properties[n.callback?0:t._bindProperties.indexOf(r)],n.to.push([o.observable,e]),function(t,e,n,r){const o=t[J],i=o.get(n),s=i||{};s[r]||(s[r]=new Set);s[r].add(e),i||o.set(n,s)}(t._observable,n,o.observable,e)}))}))}(this),this._bindProperties.forEach((t=>{lt(this._observable,t)}))}function it(t,e,n){if(this._bindings.size>1)throw new S("observable-bind-to-many-not-one-binding",this);this.to(...function(t,e){const n=t.map((t=>[t,e]));return Array.prototype.concat.apply([],n)}(t,e),n)}function st(t){return t.every((t=>"string"==typeof t))}function lt(t,e){const n=t[Q].get(e);let r;n.callback?r=n.callback.apply(t,n.to.map((t=>t[0][t[1]]))):(r=n.to[0],r=r[0][r[1]]),Object.prototype.hasOwnProperty.call(t,e)?t[e]=r:t.set(e,r)}["set","bind","unbind","decorate","on","once","off","listenTo","stopListening","fire","delegate","stopDelegating","_addEventListener","_removeEventListener"].forEach((t=>{nt[t]=et.prototype[t]}));class ct{constructor(){this._replacedElements=[]}replace(t,e){this._replacedElements.push({element:t,newElement:e}),t.style.display="none",e&&t.parentNode.insertBefore(e,t.nextSibling)}restore(){this._replacedElements.forEach((({element:t,newElement:e})=>{t.style.display="",e&&e.remove()})),this._replacedElements=[]}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function at(t){let e=new AbortController;function n(...n){return e.abort(),e=new AbortController,t(e.signal,...n)}return n.abort=()=>e.abort(),n}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function ut(t){let e=0;for(const n of t)e++;return e}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function ht(t,e){const n=Math.min(t.length,e.length);for(let r=0;r<n;r++)if(t[r]!=e[r])return r;return t.length==e.length?"same":t.length<e.length?"prefix":"extension"}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function ft(t){return!(!t||!t[Symbol.iterator])}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function dt(t,n,r={},o=[]){const i=r&&r.xmlns,s=i?t.createElementNS(i,n):t.createElement(n);for(const t in r)s.setAttribute(t,r[t]);!e(o)&&ft(o)||(o=[o]);for(let n of o)e(n)&&(n=t.createTextNode(n)),s.appendChild(n);return s}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class pt{constructor(t,e){this._config=Object.create(null),e&&this.define(mt(e)),t&&this._setObjectToTarget(this._config,t)}set(t,e){this._setToTarget(this._config,t,e)}define(t,e){this._setToTarget(this._config,t,e,!0)}get(t){return this._getFromSource(this._config,t)}*names(){for(const t of Object.keys(this._config))yield t}_setToTarget(t,e,r,o=!1){if(n(e))return void this._setObjectToTarget(t,e,o);const i=e.split(".");e=i.pop();for(const e of i)n(t[e])||(t[e]=Object.create(null)),t=t[e];if(n(r))return n(t[e])||(t[e]=Object.create(null)),t=t[e],void this._setObjectToTarget(t,r,o);o&&void 0!==t[e]||(t[e]=r)}_getFromSource(t,e){const r=e.split(".");e=r.pop();for(const e of r){if(!n(t[e])){t=null;break}t=t[e]}return t?mt(t[e]):void 0}_setObjectToTarget(t,e,n){Object.keys(e).forEach((r=>{this._setToTarget(t,r,e[r],n)}))}}function mt(t){return r(t,gt)}function gt(t){return o(t)||"function"==typeof t?t:void 0}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function bt(t){if(t){if(t.defaultView)return t instanceof t.defaultView.Document;if(t.ownerDocument&&t.ownerDocument.defaultView)return t instanceof t.ownerDocument.defaultView.Node}return!1}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function _t(t){const e=Object.prototype.toString.apply(t);return"[object Window]"==e||"[object global]"==e}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const wt=yt(K());function yt(t){if(!t)return wt;return class extends t{listenTo(t,e,n,r={}){if(bt(t)||_t(t)){const o={capture:!!r.useCapture,passive:!!r.usePassive},i=this._getProxyEmitter(t,o)||new vt(t,o);this.listenTo(i,e,n,r)}else super.listenTo(t,e,n,r)}stopListening(t,e,n){if(bt(t)||_t(t)){const r=this._getAllProxyEmitters(t);for(const t of r)this.stopListening(t,e,n)}else super.stopListening(t,e,n)}_getProxyEmitter(t,e){return function(t,e){const n=t[F];return n&&n[e]?n[e].emitter:null}(this,Et(t,e))}_getAllProxyEmitters(t){return[{capture:!1,passive:!1},{capture:!1,passive:!0},{capture:!0,passive:!1},{capture:!0,passive:!0}].map((e=>this._getProxyEmitter(t,e))).filter((t=>!!t))}}}["_getProxyEmitter","_getAllProxyEmitters","on","once","off","listenTo","stopListening","fire","delegate","stopDelegating","_addEventListener","_removeEventListener"].forEach((t=>{yt[t]=wt.prototype[t]}));class vt extends(K()){constructor(t,e){super(),U(this,Et(t,e)),this._domNode=t,this._options=e}attach(t){if(this._domListeners&&this._domListeners[t])return;const e=this._createDomListener(t);this._domNode.addEventListener(t,e,this._options),this._domListeners||(this._domListeners={}),this._domListeners[t]=e}detach(t){let e;!this._domListeners[t]||(e=this._events[t])&&e.callbacks.length||this._domListeners[t].removeListener()}_addEventListener(t,e,n){this.attach(t),K().prototype._addEventListener.call(this,t,e,n)}_removeEventListener(t,e){K().prototype._removeEventListener.call(this,t,e),this.detach(t)}_createDomListener(t){const e=e=>{this.fire(t,e)};return e.removeListener=()=>{this._domNode.removeEventListener(t,e,this._options),delete this._domListeners[t]},e}}function Et(t,e){let n=function(t){return t["data-ck-expando"]||(t["data-ck-expando"]=L())}(t);for(const t of Object.keys(e).sort())e[t]&&(n+="-"+t);return n}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Tt(t){let e=t.parentElement;if(!e)return null;for(;"BODY"!=e.tagName;){const t=e.style.overflowY||c.window.getComputedStyle(e).overflowY;if("auto"===t||"scroll"===t)break;if(e=e.parentElement,!e)return null}return e}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function xt(t){const e=[];let n=t;for(;n&&n.nodeType!=Node.DOCUMENT_NODE;)e.unshift(n),n=n.parentNode;return e}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function kt(t){return t instanceof HTMLTextAreaElement?t.value:t.innerHTML}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function It(t){const e=t.ownerDocument.defaultView.getComputedStyle(t);return{top:parseInt(e.borderTopWidth,10),right:parseInt(e.borderRightWidth,10),bottom:parseInt(e.borderBottomWidth,10),left:parseInt(e.borderLeftWidth,10)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Ot(t){if(!t.target)return null;const e=t.target.ownerDocument,n=t.clientX,r=t.clientY;let o=null;return e.caretRangeFromPoint&&e.caretRangeFromPoint(n,r)?o=e.caretRangeFromPoint(n,r):t.rangeParent&&(o=e.createRange(),o.setStart(t.rangeParent,t.rangeOffset),o.collapse(!0)),o}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Lt(t){return"[object Text]"==Object.prototype.toString.call(t)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function At(t){return"[object Range]"==Object.prototype.toString.apply(t)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Ct(t){return t&&t.parentNode?t.offsetParent===c.document.body?null:t.offsetParent:null}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */const Mt=["top","right","bottom","left","width","height"];class St{constructor(t){const e=At(t);if(Object.defineProperty(this,"_source",{value:t._source||t,writable:!0,enumerable:!1}),jt(t)||e)if(e){const e=St.getDomRangeRects(t);Rt(this,St.getBoundingRect(e))}else Rt(this,t.getBoundingClientRect());else if(_t(t)){const{innerWidth:e,innerHeight:n}=t;Rt(this,{top:0,right:e,bottom:n,left:0,width:e,height:n})}else Rt(this,t)}clone(){return new St(this)}moveTo(t,e){return this.top=e,this.right=t+this.width,this.bottom=e+this.height,this.left=t,this}moveBy(t,e){return this.top+=e,this.right+=t,this.left+=t,this.bottom+=e,this}getIntersection(t){const e={top:Math.max(this.top,t.top),right:Math.min(this.right,t.right),bottom:Math.min(this.bottom,t.bottom),left:Math.max(this.left,t.left),width:0,height:0};if(e.width=e.right-e.left,e.height=e.bottom-e.top,e.width<0||e.height<0)return null;{const t=new St(e);return t._source=this._source,t}}getIntersectionArea(t){const e=this.getIntersection(t);return e?e.getArea():0}getArea(){return this.width*this.height}getVisible(){const t=this._source;let e=this.clone();if(Nt(t))return e;let n,r=t,o=t.parentNode||t.commonAncestorContainer;for(;o&&!Nt(o);){const t="visible"===((i=o)instanceof HTMLElement?i.ownerDocument.defaultView.getComputedStyle(i).overflow:"visible");r instanceof HTMLElement&&"absolute"===Pt(r)&&(n=r);const s=Pt(o);if(t||n&&("relative"===s&&t||"relative"!==s)){r=o,o=o.parentNode;continue}const l=new St(o),c=e.getIntersection(l);if(!c)return null;c.getArea()<e.getArea()&&(e=c),r=o,o=o.parentNode}var i;return e}isEqual(t){for(const e of Mt)if(this[e]!==t[e])return!1;return!0}contains(t){const e=this.getIntersection(t);return!(!e||!e.isEqual(t))}toAbsoluteRect(){const{scrollX:t,scrollY:e}=c.window,n=this.clone().moveBy(t,e);if(jt(n._source)){const t=Ct(n._source);t&&function(t,e){const n=new St(e),r=It(e);let o=0,i=0;o-=n.left,i-=n.top,o+=e.scrollLeft,i+=e.scrollTop,o-=r.left,i-=r.top,t.moveBy(o,i)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */(n,t)}return n}excludeScrollbarsAndBorders(){const t=this._source;let e,n,r;if(_t(t))e=t.innerWidth-t.document.documentElement.clientWidth,n=t.innerHeight-t.document.documentElement.clientHeight,r=t.getComputedStyle(t.document.documentElement).direction;else{const o=It(t);e=t.offsetWidth-t.clientWidth-o.left-o.right,n=t.offsetHeight-t.clientHeight-o.top-o.bottom,r=t.ownerDocument.defaultView.getComputedStyle(t).direction,this.left+=o.left,this.top+=o.top,this.right-=o.right,this.bottom-=o.bottom,this.width=this.right-this.left,this.height=this.bottom-this.top}return this.width-=e,"ltr"===r?this.right-=e:this.left+=e,this.height-=n,this.bottom-=n,this}static getDomRangeRects(t){const e=[],n=Array.from(t.getClientRects());if(n.length)for(const t of n)e.push(new St(t));else{let n=t.startContainer;Lt(n)&&(n=n.parentNode);const r=new St(n.getBoundingClientRect());r.right=r.left,r.width=0,e.push(r)}return e}static getBoundingRect(t){const e={left:Number.POSITIVE_INFINITY,top:Number.POSITIVE_INFINITY,right:Number.NEGATIVE_INFINITY,bottom:Number.NEGATIVE_INFINITY,width:0,height:0};let n=0;for(const r of t)n++,e.left=Math.min(e.left,r.left),e.top=Math.min(e.top,r.top),e.right=Math.max(e.right,r.right),e.bottom=Math.max(e.bottom,r.bottom);return 0==n?null:(e.width=e.right-e.left,e.height=e.bottom-e.top,new St(e))}}function Rt(t,e){for(const n of Mt)t[n]=e[n]}function Nt(t){return!!jt(t)&&t===t.ownerDocument.body}function jt(t){return null!==t&&"object"==typeof t&&1===t.nodeType&&"function"==typeof t.getBoundingClientRect}function Pt(t){return t instanceof HTMLElement?t.ownerDocument.defaultView.getComputedStyle(t).position:"static"}class Dt{constructor(t,e){Dt._observerInstance||Dt._createObserver(),this._element=t,this._callback=e,Dt._addElementCallback(t,e),Dt._observerInstance.observe(t)}get element(){return this._element}destroy(){Dt._deleteElementCallback(this._element,this._callback)}static _addElementCallback(t,e){Dt._elementCallbacks||(Dt._elementCallbacks=new Map);let n=Dt._elementCallbacks.get(t);n||(n=new Set,Dt._elementCallbacks.set(t,n)),n.add(e)}static _deleteElementCallback(t,e){const n=Dt._getElementCallbacks(t);n&&(n.delete(e),n.size||(Dt._elementCallbacks.delete(t),Dt._observerInstance.unobserve(t))),Dt._elementCallbacks&&!Dt._elementCallbacks.size&&(Dt._observerInstance=null,Dt._elementCallbacks=null)}static _getElementCallbacks(t){return Dt._elementCallbacks?Dt._elementCallbacks.get(t):null}static _createObserver(){Dt._observerInstance=new c.window.ResizeObserver((t=>{for(const e of t){const t=Dt._getElementCallbacks(e.target);if(t)for(const n of t)n(e)}}))}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
function Bt(t,e){t instanceof HTMLTextAreaElement&&(t.value=e),t.innerHTML=e}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Ft(t){return e=>e+t}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Vt(t){let e=0;for(;t.previousSibling;)t=t.previousSibling,e++;return e}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Wt(t,e,n){t.insertBefore(n,t.childNodes[e]||null)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Ht(t){return t&&t.nodeType===Node.COMMENT_NODE}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Kt(t){try{c.document.createAttribute(t)}catch(t){return!1}return!0}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Ut(t){return!!t&&(Lt(t)?Ut(t.parentElement):!!t.getClientRects&&!!t.getClientRects().length)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Yt({element:t,target:e,positions:n,limiter:r,fitInViewport:o,viewportOffsetConfig:s}){i(e)&&(e=e()),i(r)&&(r=r());const l=Ct(t),a=function(t){t=Object.assign({top:0,bottom:0,left:0,right:0},t);const e=new St(c.window);return e.top+=t.top,e.height-=t.top,e.bottom-=t.bottom,e.height-=t.bottom,e}(s),u=new St(t),h=qt(e,a);let f;if(!h||!a.getIntersection(h))return null;const d={targetRect:h,elementRect:u,positionedElementAncestor:l,viewportRect:a};if(r||o){if(r){const t=qt(r,a);t&&(d.limiterRect=t)}f=function(t,e){const{elementRect:n}=e,r=n.getArea(),o=t.map((t=>new $t(t,e))).filter((t=>!!t.name));let i=0,s=null;for(const t of o){const{limiterIntersectionArea:e,viewportIntersectionArea:n}=t;if(e===r)return t;const o=n**2+e**2;o>i&&(i=o,s=t)}return s}(n,d)}else f=new $t(n[0],d);return f}function qt(t,e){const n=new St(t).getVisible();return n?n.getIntersection(e):null}Dt._observerInstance=null,Dt._elementCallbacks=null;class $t{constructor(t,e){const n=t(e.targetRect,e.elementRect,e.viewportRect,e.limiterRect);if(!n)return;const{left:r,top:o,name:i,config:s}=n;this.name=i,this.config=s,this._positioningFunctionCoordinates={left:r,top:o},this._options=e}get left(){return this._absoluteRect.left}get top(){return this._absoluteRect.top}get limiterIntersectionArea(){const t=this._options.limiterRect;return t?t.getIntersectionArea(this._rect):0}get viewportIntersectionArea(){return this._options.viewportRect.getIntersectionArea(this._rect)}get _rect(){return this._cachedRect||(this._cachedRect=this._options.elementRect.clone().moveTo(this._positioningFunctionCoordinates.left,this._positioningFunctionCoordinates.top)),this._cachedRect}get _absoluteRect(){return this._cachedAbsoluteRect||(this._cachedAbsoluteRect=this._rect.toAbsoluteRect()),this._cachedAbsoluteRect}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function zt(t){const e=t.parentNode;e&&e.removeChild(t)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Gt({target:t,viewportOffset:e=0,ancestorOffset:n=0,alignToTop:r,forceScroll:o}){const i=re(t);let s=i,l=null;for(e=function(t){if("number"==typeof t)return{top:t,bottom:t,left:t,right:t};return t}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */(e);s;){let c;c=oe(s==i?t:l),Qt({parent:c,getRect:()=>ie(t,s),alignToTop:r,ancestorOffset:n,forceScroll:o});let a=ie(t,s);const u=ie(c,s);if(a.height>u.height){const t=a.getIntersection(u);t&&(a=t)}if(Jt({window:s,rect:a,viewportOffset:e,alignToTop:r,forceScroll:o}),s.parent!=s){if(l=s.frameElement,s=s.parent,!l)return}else s=null}}function Xt(t,e,n){Qt({parent:oe(t),getRect:()=>new St(t),ancestorOffset:e,limiterElement:n})}function Jt({window:t,rect:e,alignToTop:n,forceScroll:r,viewportOffset:o}){const i=e.clone().moveBy(0,o.bottom),s=e.clone().moveBy(0,-o.top),l=new St(t).excludeScrollbarsAndBorders(),c=n&&r,a=[s,i].every((t=>l.contains(t)));let{scrollX:u,scrollY:h}=t;const f=u,d=h;c?h-=l.top-e.top+o.top:a||(te(s,l)?h-=l.top-e.top+o.top:Zt(i,l)&&(h+=n?e.top-l.top-o.top:e.bottom-l.bottom+o.bottom)),a||(ee(e,l)?u-=l.left-e.left+o.left:ne(e,l)&&(u+=e.right-l.right+o.right)),u==f&&h===d||t.scrollTo(u,h)}function Qt({parent:t,getRect:e,alignToTop:n,forceScroll:r,ancestorOffset:o=0,limiterElement:i}){const s=re(t),l=n&&r;let c,a,u;const h=i||s.document.body;for(;t!=h;)a=e(),c=new St(t).excludeScrollbarsAndBorders(),u=c.contains(a),l?t.scrollTop-=c.top-a.top+o:u||(te(a,c)?t.scrollTop-=c.top-a.top+o:Zt(a,c)&&(t.scrollTop+=n?a.top-c.top-o:a.bottom-c.bottom+o)),u||(ee(a,c)?t.scrollLeft-=c.left-a.left+o:ne(a,c)&&(t.scrollLeft+=a.right-c.right+o)),t=t.parentNode}function Zt(t,e){return t.bottom>e.bottom}function te(t,e){return t.top<e.top}function ee(t,e){return t.left<e.left}function ne(t,e){return t.right>e.right}function re(t){return At(t)?t.startContainer.ownerDocument.defaultView:t.ownerDocument.defaultView}function oe(t){if(At(t)){let e=t.commonAncestorContainer;return Lt(e)&&(e=e.parentNode),e}return t.parentNode}function ie(t,e){const n=re(t),r=new St(t);if(n===e)return r;{let t=n;for(;t!=e;){const e=t.frameElement,n=new St(e).excludeScrollbarsAndBorders();r.moveBy(n.left,n.top),t=t.parent}}return r}const se={ctrl:"⌃",cmd:"⌘",alt:"⌥",shift:"⇧"},le={ctrl:"Ctrl+",alt:"Alt+",shift:"Shift+"},ce={37:"←",38:"↑",39:"→",40:"↓",9:"⇥",33:"Page Up",34:"Page Down"},ae=be(),ue=Object.fromEntries(Object.entries(ae).map((([t,e])=>{let n;return n=e in ce?ce[e]:t.charAt(0).toUpperCase()+t.slice(1),[e,n]})));function he(t){let e;if("string"==typeof t){if(e=ae[t.toLowerCase()],!e)throw new S("keyboard-unknown-key",null,{key:t})}else e=t.keyCode+(t.altKey?ae.alt:0)+(t.ctrlKey?ae.ctrl:0)+(t.shiftKey?ae.shift:0)+(t.metaKey?ae.cmd:0);return e}function fe(t){return"string"==typeof t&&(t=function(t){return t.split("+").map((t=>t.trim()))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */(t)),t.map((t=>"string"==typeof t?function(t){if(t.endsWith("!"))return he(t.slice(0,-1));const e=he(t);return(h.isMac||h.isiOS)&&e==ae.ctrl?ae.cmd:e}(t):t)).reduce(((t,e)=>e+t),0)}function de(t){let e=fe(t);return Object.entries(h.isMac||h.isiOS?se:le).reduce(((t,[n,r])=>(e&ae[n]&&(e&=~ae[n],t+=r),t)),"")+(e?ue[e]:"")}function pe(t){return t==ae.arrowright||t==ae.arrowleft||t==ae.arrowup||t==ae.arrowdown}function me(t,e){const n="ltr"===e;switch(t){case ae.arrowleft:return n?"left":"right";case ae.arrowright:return n?"right":"left";case ae.arrowup:return"up";case ae.arrowdown:return"down"}}function ge(t,e){const n=me(t,e);return"down"===n||"right"===n}function be(){const t={pageup:33,pagedown:34,arrowleft:37,arrowup:38,arrowright:39,arrowdown:40,backspace:8,delete:46,enter:13,space:32,esc:27,tab:9,ctrl:1114112,shift:2228224,alt:4456448,cmd:8912896};for(let e=65;e<=90;e++){t[String.fromCharCode(e).toLowerCase()]=e}for(let e=48;e<=57;e++)t[e-48]=e;for(let e=112;e<=123;e++)t["f"+(e-111)]=e;return Object.assign(t,{"'":222,",":108,"-":109,".":110,"/":111,";":186,"=":187,"[":219,"\\":220,"]":221,"`":223}),t}const _e=["ar","ara","dv","div","fa","per","fas","he","heb","ku","kur","ug","uig"];function we(t){return _e.includes(t)?"rtl":"ltr"}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function ye(t){return Array.isArray(t)?t:[t]}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
/* istanbul ignore else -- @preserve */function ve(t,e,n=1,r){if("number"!=typeof n)throw new S("translation-service-quantity-not-a-number",null,{quantity:n});const o=r||c.window.CKEDITOR_TRANSLATIONS,i=function(t){return Object.keys(t).length}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */(o);1===i&&(t=Object.keys(o)[0]);const s=e.id||e.string;if(0===i||!function(t,e,n){return!!n[t]&&!!n[t].dictionary[e]}(t,s,o))return 1!==n?e.plural:e.string;const l=o[t].dictionary,a=o[t].getPluralForm||(t=>1===t?0:1),u=l[s];if("string"==typeof u)return u;return u[Number(a(n))]}c.window.CKEDITOR_TRANSLATIONS||(c.window.CKEDITOR_TRANSLATIONS={});class Ee{constructor({uiLanguage:t="en",contentLanguage:e,translations:n}={}){this.uiLanguage=t,this.contentLanguage=e||this.uiLanguage,this.uiLanguageDirection=we(this.uiLanguage),this.contentLanguageDirection=we(this.contentLanguage),this.translations=function(t){return Array.isArray(t)?t.reduce(((t,e)=>s(t,e))):t}(n),this.t=(t,e)=>this._t(t,e)}get language(){return console.warn("locale-deprecated-language-property: The Locale#language property has been deprecated and will be removed in the near future. Please use #uiLanguage and #contentLanguage properties instead."),this.uiLanguage}_t(t,e=[]){e=ye(e),"string"==typeof t&&(t={string:t});const n=!!t.plural?e[0]:1;return function(t,e){return t.replace(/%(\d+)/g,((t,n)=>n<e.length?e[n]:t))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */(ve(this.uiLanguage,t,n,this.translations),e)}}class Te extends(K()){constructor(t={},e={}){super();const n=ft(t);if(n||(e=t),this._items=[],this._itemMap=new Map,this._idProperty=e.idProperty||"id",this._bindToExternalToInternalMap=new WeakMap,this._bindToInternalToExternalMap=new WeakMap,this._skippedIndexesFromExternal=[],n)for(const e of t)this._items.push(e),this._itemMap.set(this._getItemIdBeforeAdding(e),e)}get length(){return this._items.length}get first(){return this._items[0]||null}get last(){return this._items[this.length-1]||null}add(t,e){return this.addMany([t],e)}addMany(t,e){if(void 0===e)e=this._items.length;else if(e>this._items.length||e<0)throw new S("collection-add-item-invalid-index",this);let n=0;for(const r of t){const t=this._getItemIdBeforeAdding(r),o=e+n;this._items.splice(o,0,r),this._itemMap.set(t,r),this.fire("add",r,o),n++}return this.fire("change",{added:t,removed:[],index:e}),this}get(t){let e;if("string"==typeof t)e=this._itemMap.get(t);else{if("number"!=typeof t)throw new S("collection-get-invalid-arg",this);e=this._items[t]}return e||null}has(t){if("string"==typeof t)return this._itemMap.has(t);{const e=t[this._idProperty];return e&&this._itemMap.has(e)}}getIndex(t){let e;return e="string"==typeof t?this._itemMap.get(t):t,e?this._items.indexOf(e):-1}remove(t){const[e,n]=this._remove(t);return this.fire("change",{added:[],removed:[e],index:n}),e}map(t,e){return this._items.map(t,e)}forEach(t,e){this._items.forEach(t,e)}find(t,e){return this._items.find(t,e)}filter(t,e){return this._items.filter(t,e)}clear(){this._bindToCollection&&(this.stopListening(this._bindToCollection),this._bindToCollection=null);const t=Array.from(this._items);for(;this.length;)this._remove(0);this.fire("change",{added:[],removed:t,index:0})}bindTo(t){if(this._bindToCollection)throw new S("collection-bind-to-rebind",this);return this._bindToCollection=t,{as:t=>{this._setUpBindToBinding((e=>new t(e)))},using:t=>{"function"==typeof t?this._setUpBindToBinding(t):this._setUpBindToBinding((e=>e[t]))}}}_setUpBindToBinding(t){const e=this._bindToCollection,n=(n,r,o)=>{const i=e._bindToCollection==this,s=e._bindToInternalToExternalMap.get(r);if(i&&s)this._bindToExternalToInternalMap.set(r,s),this._bindToInternalToExternalMap.set(s,r);else{const n=t(r);if(!n)return void this._skippedIndexesFromExternal.push(o);let i=o;for(const t of this._skippedIndexesFromExternal)o>t&&i--;for(const t of e._skippedIndexesFromExternal)i>=t&&i++;this._bindToExternalToInternalMap.set(r,n),this._bindToInternalToExternalMap.set(n,r),this.add(n,i);for(let t=0;t<e._skippedIndexesFromExternal.length;t++)i<=e._skippedIndexesFromExternal[t]&&e._skippedIndexesFromExternal[t]++}};for(const t of e)n(0,t,e.getIndex(t));this.listenTo(e,"add",n),this.listenTo(e,"remove",((t,e,n)=>{const r=this._bindToExternalToInternalMap.get(e);r&&this.remove(r),this._skippedIndexesFromExternal=this._skippedIndexesFromExternal.reduce(((t,e)=>(n<e&&t.push(e-1),n>e&&t.push(e),t)),[])}))}_getItemIdBeforeAdding(t){const e=this._idProperty;let n;if(e in t){if(n=t[e],"string"!=typeof n)throw new S("collection-add-invalid-id",this);if(this.get(n))throw new S("collection-add-item-already-exists",this)}else t[e]=n=L();return n}_remove(t){let e,n,r,o=!1;const i=this._idProperty;if("string"==typeof t?(n=t,r=this._itemMap.get(n),o=!r,r&&(e=this._items.indexOf(r))):"number"==typeof t?(e=t,r=this._items[e],o=!r,r&&(n=r[i])):(r=t,n=r[i],e=this._items.indexOf(r),o=-1==e||!this._itemMap.get(n)),o)throw new S("collection-remove-404",this);this._items.splice(e,1),this._itemMap.delete(n);const s=this._bindToInternalToExternalMap.get(r);return this._bindToInternalToExternalMap.delete(r),this._bindToExternalToInternalMap.delete(s),this.fire("remove",r,e),[r,e]}[Symbol.iterator](){return this._items[Symbol.iterator]()}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function xe(t){const e=t.next();return e.done?null:e.value}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class ke extends(yt(nt())){constructor(){super(),this._elements=new Set,this._externalViews=new Set,this._blurTimeout=null,this.set("isFocused",!1),this.set("focusedElement",null)}get elements(){return Array.from(this._elements.values())}get externalViews(){return Array.from(this._externalViews.values())}add(t){if(Oe(t))this._addElement(t);else if(Ie(t))this._addView(t);else{if(!t.element)throw new S("focustracker-add-view-missing-element",{focusTracker:this,view:t});this._addElement(t.element)}}remove(t){Oe(t)?this._removeElement(t):Ie(t)?this._removeView(t):this._removeElement(t.element)}_addElement(t){if(this._elements.has(t))throw new S("focustracker-add-element-already-exist",this);this.listenTo(t,"focus",(()=>{const e=this.externalViews.find((e=>function(t,e){if(Le(t,e))return!0;return!!e.focusTracker.externalViews.find((e=>Le(t,e)))}(t,e)));e?this._focus(e.element):this._focus(t)}),{useCapture:!0}),this.listenTo(t,"blur",(()=>{this._blur()}),{useCapture:!0}),this._elements.add(t)}_removeElement(t){this._elements.has(t)&&(this.stopListening(t),this._elements.delete(t)),t===this.focusedElement&&this._blur()}_addView(t){t.element&&this._addElement(t.element),this.listenTo(t.focusTracker,"change:focusedElement",(()=>{t.focusTracker.focusedElement?t.element&&this._focus(t.element):this._blur()})),this._externalViews.add(t)}_removeView(t){t.element&&this._removeElement(t.element),this.stopListening(t.focusTracker),this._externalViews.delete(t)}destroy(){this.stopListening(),this._elements.clear(),this._externalViews.clear(),this.isFocused=!1,this.focusedElement=null}_focus(t){this._clearBlurTimeout(),this.focusedElement=t,this.isFocused=!0}_blur(){if(this.elements.find((t=>t.contains(document.activeElement))))return;this.externalViews.find((t=>t.focusTracker.isFocused&&!t.focusTracker._blurTimeout))||(this._clearBlurTimeout(),this._blurTimeout=setTimeout((()=>{this.focusedElement=null,this.isFocused=!1}),0))}_clearBlurTimeout(){clearTimeout(this._blurTimeout),this._blurTimeout=null}}function Ie(t){return"focusTracker"in t&&t.focusTracker instanceof ke}function Oe(t){return o(t)}function Le(t,e){return!!e.element&&e.element.contains(document.activeElement)&&t.contains(e.element)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */class Ae{constructor(){this._listener=new(yt())}listenTo(t){this._listener.listenTo(t,"keydown",((t,e)=>{this._listener.fire("_keydown:"+he(e),e)}))}set(t,e,n={}){const r=fe(t),o=n.priority;this._listener.listenTo(this._listener,"_keydown:"+r,((t,r)=>{n.filter&&!n.filter(r)||(e(r,(()=>{r.preventDefault(),r.stopPropagation(),t.stop()})),t.return=!0)}),{priority:o})}press(t){return!!this._listener.fire("_keydown:"+he(t),t)}stopListening(t){this._listener.stopListening(t)}destroy(){this.stopListening()}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */
function Ce(t){return ft(t)?new Map(t):function(t){const e=new Map;for(const n in t)e.set(n,t[n]);return e}(t)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Me(t,e={}){return new Promise(((n,r)=>{const o=e.signal||(new AbortController).signal;o.throwIfAborted();const i=setTimeout((function(){o.removeEventListener("abort",s),n()}),t);function s(){clearTimeout(i),r(o.reason)}o.addEventListener("abort",s,{once:!0})}))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */async function Se(t,e={}){const{maxAttempts:n=4,retryDelay:r=Re(),signal:o=(new AbortController).signal}=e;o.throwIfAborted();for(let e=0;;e++){try{return await t()}catch(t){if(e+1>=n)throw t}await Me(r(e),{signal:o})}}function Re(t={}){const{delay:e=1e3,factor:n=2,maxDelay:r=1e4}=t;return t=>Math.min(n**t*e,r)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Ne(t,e,n){const r=t.length,o=e.length;for(let e=r-1;e>=n;e--)t[e+o]=t[e];for(let r=0;r<o;r++)t[n+r]=e[r]}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function je(t,e){let n;function r(...o){r.cancel(),n=setTimeout((()=>t(...o)),e)}return r.cancel=()=>{clearTimeout(n)},r}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Pe(t){try{if(!t.startsWith("ey"))return null;const e=atob(t.replace(/-/g,"+").replace(/_/g,"/"));return JSON.parse(e)}catch(t){return null}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function De(t){const e=Array.isArray(t)?t:[t],n=function(){const t=[];for(let e=0;e<256;e++){let n=e;for(let t=0;t<8;t++)1&n?n=3988292384^n>>>1:n>>>=1;t[e]=n}return t}();let r=~0;const o=e.map((t=>Array.isArray(t)?t.join(""):String(t))).join("");for(let t=0;t<o.length;t++){r=r>>>8^n[255&(r^o.charCodeAt(t))]}return r=~r>>>0,r.toString(16).padStart(8,"0")}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-licensing-options
 */function Be(t){return!!t&&1==t.length&&/[\u0300-\u036f\u1ab0-\u1aff\u1dc0-\u1dff\u20d0-\u20ff\ufe20-\ufe2f]/.test(t)}function Fe(t){return!!t&&1==t.length&&/[\ud800-\udbff]/.test(t)}function Ve(t){return!!t&&1==t.length&&/[\udc00-\udfff]/.test(t)}function We(t,e){return Fe(t.charAt(e-1))&&Ve(t.charAt(e))}function He(t,e){return Be(t.charAt(e))}const Ke=Ye();function Ue(t,e){const n=String(t).matchAll(Ke);return Array.from(n).some((t=>t.index<e&&e<t.index+t[0].length))}function Ye(){const t=/\p{Regional_Indicator}{2}/u.source,e="(?:"+[/\p{Emoji}[\u{E0020}-\u{E007E}]+\u{E007F}/u,/\p{Emoji}\u{FE0F}?\u{20E3}/u,/\p{Emoji}\u{FE0F}/u,/(?=\p{General_Category=Other_Symbol})\p{Emoji}\p{Emoji_Modifier}*/u].map((t=>t.source)).join("|")+")";return new RegExp(`${t}|${e}(?:‍${e})*`,"ug")}export{S as CKEditorError,Te as Collection,pt as Config,yt as DomEmitterMixin,ct as ElementReplacer,K as EmitterMixin,I as EventInfo,ke as FocusTracker,Ae as KeystrokeHandler,Ee as Locale,nt as ObservableMixin,St as Rect,Dt as ResizeObserver,at as abortableDebounce,ht as compareArrays,ut as count,De as crc32,dt as createElement,je as delay,T as diff,x as diffToChanges,h as env,Re as exponentialDelay,y as fastDiff,Tt as findClosestScrollableAncestor,xe as first,xt as getAncestors,It as getBorderWidths,he as getCode,kt as getDataFromElement,de as getEnvKeystrokeText,we as getLanguageDirection,me as getLocalizedArrowKeyCodeDirection,Yt as getOptimalPosition,Ot as getRangeFromMouseEvent,c as global,Vt as indexOf,Wt as insertAt,C as insertToPriorityArray,pe as isArrowKeyCode,Be as isCombiningMark,Ht as isComment,ge as isForwardArrowKeyCode,Fe as isHighSurrogateHalf,He as isInsideCombinedSymbol,Ue as isInsideEmojiSequence,We as isInsideSurrogatePair,ft as isIterable,Ve as isLowSurrogateHalf,bt as isNode,At as isRange,Lt as isText,Kt as isValidAttributeName,Ie as isViewWithFocusTracker,Ut as isVisible,ae as keyCodes,N as logError,R as logWarning,k as mix,Pe as parseBase64EncodedObject,fe as parseKeystroke,A as priorities,B as releaseDate,zt as remove,Se as retry,Xt as scrollAncestorsToShowTarget,Gt as scrollViewportToShowTarget,Bt as setDataInElement,Ne as spliceArray,ye as toArray,Ce as toMap,Ft as toUnit,L as uid,D as version,Me as wait};