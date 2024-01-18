import{Plugin as e}from"@ckeditor/ckeditor5-core";import{ClipboardPipeline as t}from"@ckeditor/ckeditor5-clipboard";import{UpcastWriter as n,Matcher as r,ViewDocument as s,DomConverter as i}from"@ckeditor/ckeditor5-engine";
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */function o(e,t){if(!e.childCount)return;const s=new n(e.document),i=function(e,t){const n=t.createRangeIn(e),s=new r({name:/^p|h\d+$/,styles:{"mso-list":/.*/}}),i=[];for(const e of n)if("elementStart"===e.type&&s.match(e.item)){const t=a(e.item);i.push({element:e.item,id:t.id,order:t.order,indent:t.indent})}return i}(e,s);if(!i.length)return;let o=null,u=1;i.forEach(((e,n)=>{const a=function(e,t){if(!e)return!0;if(e.id!==t.id)return t.indent-e.indent!=1;const n=t.element.previousSibling;if(!n)return!0;return r=n,!(r.is("element","ol")||r.is("element","ul"));var r}(i[n-1],e),m=a?null:i[n-1],f=(g=e,(d=m)?g.indent-d.indent:g.indent-1);var d,g;if(a&&(o=null,u=1),!o||0!==f){const n=function(e,t){const n=new RegExp(`@list l${e.id}:level${e.indent}\\s*({[^}]*)`,"gi"),r=/mso-level-number-format:([^;]{0,100});/gi,s=/mso-level-start-at:\s{0,100}([0-9]{0,10})\s{0,100};/gi,i=n.exec(t);let o="decimal",l="ol",a=null;if(i&&i[1]){const t=r.exec(i[1]);if(t&&t[1]&&(o=t[1].trim(),l="bullet"!==o&&"image"!==o?"ol":"ul"),"bullet"===o){const t=function(e){const t=function(e){if(e.getChild(0).is("$text"))return null;for(const t of e.getChildren()){if(!t.is("element","span"))continue;const e=t.getChild(0);if(e)return e.is("$text")?e:e.getChild(0)}
/* istanbul ignore next -- @preserve */return null}(e);if(!t)return null;const n=t._data;if("o"===n)return"circle";if("·"===n)return"disc";if("§"===n)return"square";return null}(e.element);t&&(o=t)}else{const e=s.exec(i[1]);e&&e[1]&&(a=parseInt(e[1]))}}return{type:l,startIndex:a,style:c(o)}}(e,t);if(o){if(e.indent>u){const e=o.getChild(o.childCount-1),t=e.getChild(e.childCount-1);o=l(n,t,s),u+=1}else if(e.indent<u){const t=u-e.indent;o=function(e,t){const n=e.getAncestors({parentFirst:!0});let r=null,s=0;for(const e of n)if((e.is("element","ul")||e.is("element","ol"))&&s++,s===t){r=e;break}return r}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(o,t),u=e.indent}}else o=l(n,e.element,s);e.indent<=u&&(o.is("element",n.type)||(o=s.rename(n.type,o)))}const p=function(e,t){return function(e,t){const n=new r({name:"span",styles:{"mso-list":"Ignore"}}),s=t.createRangeIn(e);for(const e of s)"elementStart"===e.type&&n.match(e.item)&&t.remove(e.item)}(e,t),t.removeStyle("text-indent",e),t.rename("li",e)}(e.element,s);s.appendChild(p,o)}))}function c(e){if(e.startsWith("arabic-leading-zero"))return"decimal-leading-zero";switch(e){case"alpha-upper":return"upper-alpha";case"alpha-lower":return"lower-alpha";case"roman-upper":return"upper-roman";case"roman-lower":return"lower-roman";case"circle":case"disc":case"square":return e;default:return null}}function l(e,t,n){const r=t.parent,s=n.createElement(e.type),i=r.getChildIndex(t)+1;return n.insertChild(i,s,r),e.style&&n.setStyle("list-style-type",e.style,s),e.startIndex&&e.startIndex>1&&n.setAttribute("start",e.startIndex,s),s}function a(e){const t={},n=e.getStyle("mso-list");if(n){const e=n.match(/(^|\s{1,100})l(\d+)/i),r=n.match(/\s{0,100}lfo(\d+)/i),s=n.match(/\s{0,100}level(\d+)/i);e&&r&&s&&(t.id=e[2],t.order=r[1],t.indent=parseInt(s[1]))}return t}function u(e,t){if(!e.childCount)return;const s=new n(e.document),i=function(e,t){const n=t.createRangeIn(e),s=new r({name:/v:(.+)/}),i=[];for(const e of n){if("elementStart"!=e.type)continue;const t=e.item,n=t.previousSibling,r=n&&n.is("element")?n.name:null;s.match(t)&&t.getAttribute("o:gfxdata")&&"v:shapetype"!==r&&i.push(e.item.getAttribute("id"))}return i}(e,s);!function(e,t,n){const s=n.createRangeIn(t),i=new r({name:"img"}),o=[];for(const t of s)if(t.item.is("element")&&i.match(t.item)){const n=t.item,r=n.getAttribute("v:shapes")?n.getAttribute("v:shapes").split(" "):[];r.length&&r.every((t=>e.indexOf(t)>-1))?o.push(n):n.getAttribute("src")||o.push(n)}for(const e of o)n.remove(e)}(i,e,s),function(e,t,n){const r=n.createRangeIn(t),s=[];for(const t of r)if("elementStart"==t.type&&t.item.is("element","v:shape")){const n=t.item.getAttribute("id");if(e.includes(n))continue;i(t.item.parent.getChildren(),n)||s.push(t.item)}for(const e of s){const t={src:o(e)};e.hasAttribute("alt")&&(t.alt=e.getAttribute("alt"));const r=n.createElement("img",t);n.insertChild(e.index+1,r,e.parent)}function i(e,t){for(const n of e)
/* istanbul ignore else -- @preserve */
if(n.is("element")){if("img"==n.name&&n.getAttribute("v:shapes")==t)return!0;if(i(n.getChildren(),t))return!0}return!1}function o(e){for(const t of e.getChildren())
/* istanbul ignore else -- @preserve */
if(t.is("element")&&t.getAttribute("src"))return t.getAttribute("src")}}(i,e,s),function(e,t){const n=t.createRangeIn(e),s=new r({name:/v:(.+)/}),i=[];for(const e of n)"elementStart"==e.type&&s.match(e.item)&&i.push(e.item);for(const e of i)t.remove(e)}(e,s);const o=function(e,t){const n=t.createRangeIn(e),s=new r({name:"img"}),i=[];for(const e of n)e.item.is("element")&&s.match(e.item)&&e.item.getAttribute("src").startsWith("file://")&&i.push(e.item);return i}(e,s);o.length&&function(e,t,n){if(e.length===t.length)for(let r=0;r<e.length;r++){const s=`data:${t[r].type};base64,${m(t[r].hex)}`;n.setAttribute("src",s,e[r])}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(o,function(e){if(!e)return[];const t=/{\\pict[\s\S]+?\\bliptag-?\d+(\\blipupi-?\d+)?({\\\*\\blipuid\s?[\da-fA-F]+)?[\s}]*?/,n=new RegExp("(?:("+t.source+"))([\\da-fA-F\\s]+)\\}","g"),r=e.match(n),s=[];if(r)for(const e of r){let n=!1;e.includes("\\pngblip")?n="image/png":e.includes("\\jpegblip")&&(n="image/jpeg"),n&&s.push({hex:e.replace(t,"").replace(/[^\da-fA-F]/g,""),type:n})}return s}(t),s)}function m(e){return btoa(e.match(/\w{2}/g).map((e=>String.fromCharCode(parseInt(e,16)))).join(""))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
const f=/<meta\s*name="?generator"?\s*content="?microsoft\s*word\s*\d+"?\/?>/i,d=/xmlns:o="urn:schemas-microsoft-com/i;class g{constructor(e){this.document=e}isActive(e){return f.test(e)||d.test(e)}execute(e){const{body:t,stylesString:r}=e._parsedData;o(t,r),u(t,e.dataTransfer.getData("text/rtf")),function(e){const t=[],r=new n(e.document);for(const{item:n}of r.createRangeIn(e))if(n.is("element")){for(const e of n.getClassNames())/\bmso/gi.exec(e)&&r.removeClass(e,n);for(const e of n.getStyleNames())/\bmso/gi.exec(e)&&r.removeStyle(e,n);n.is("element","w:sdt")&&t.push(n)}for(const e of t){const t=e.parent,n=t.getChildIndex(e);r.insertChild(n,e.getChildren(),t),r.remove(e)}}(t),e.content=t}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */function p(e,t,n,{blockElements:r,inlineObjectElements:s}){let i=n.createPositionAt(e,"forward"==t?"after":"before");return i=i.getLastMatchingPosition((({item:e})=>e.is("element")&&!r.includes(e.name)&&!s.includes(e.name)),{direction:t}),"forward"==t?i.nodeAfter:i.nodeBefore}function h(e,t){return!!e&&e.is("element")&&t.includes(e.name)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */const b=/id=("|')docs-internal-guid-[-0-9a-f]+("|')/i;class y{constructor(e){this.document=e}isActive(e){return b.test(e)}execute(e){const t=new n(this.document),{body:r}=e._parsedData;!function(e,t){for(const n of e.getChildren())if(n.is("element","b")&&"normal"===n.getStyle("font-weight")){const r=e.getChildIndex(n);t.remove(n),t.insertChild(r,n.getChildren(),e)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(r,t),function(e,t){for(const n of t.createRangeIn(e)){const e=n.item;if(e.is("element","li")){const n=e.getChild(0);n&&n.is("element","p")&&t.unwrapElement(n)}}}(r,t),function(e,t){const n=new s(t.document.stylesProcessor),r=new i(n,{renderingMode:"data"}),o=r.blockElements,c=r.inlineObjectElements,l=[];for(const n of t.createRangeIn(e)){const e=n.item;if(e.is("element","br")){const n=p(e,"forward",t,{blockElements:o,inlineObjectElements:c}),r=p(e,"backward",t,{blockElements:o,inlineObjectElements:c}),s=h(n,o);(h(r,o)||s)&&l.push(e)}}for(const e of l)e.hasClass("Apple-interchange-newline")?t.remove(e):t.replace(e,t.createElement("p"))}(r,t),e.content=r}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
const w=/<google-sheets-html-origin/i;class x{constructor(e){this.document=e}isActive(e){return w.test(e)}execute(e){const t=new n(this.document),{body:r}=e._parsedData;!
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */
function(e,t){for(const n of e.getChildren())if(n.is("element","google-sheets-html-origin")){const r=e.getChildIndex(n);t.remove(n),t.insertChild(r,n.getChildren(),e)}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(r,t),function(e,t){for(const n of e.getChildren())n.is("element","table")&&n.hasAttribute("xmlns")&&t.removeAttribute("xmlns",n)}(r,t),function(e,t){for(const n of e.getChildren())n.is("element","table")&&"0px"===n.getStyle("width")&&t.removeStyle("width",n)}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(r,t),function(e,t){for(const n of Array.from(e.getChildren()))n.is("element","style")&&t.remove(n)}(r,t),e.content=r}}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */function C(e){return e.replace(/<span(?: class="Apple-converted-space"|)>(\s+)<\/span>/g,((e,t)=>1===t.length?" ":Array(t.length+1).join("  ").substr(0,t.length)))}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */function v(e,t){const n=new DOMParser,r=function(e){return C(C(e)).replace(/(<span\s+style=['"]mso-spacerun:yes['"]>[^\S\r\n]*?)[\r\n]+([^\S\r\n]*<\/span>)/g,"$1$2").replace(/<span\s+style=['"]mso-spacerun:yes['"]><\/span>/g,"").replace(/(<span\s+style=['"]letter-spacing:[^'"]+?['"]>)[\r\n]+(<\/span>)/g,"$1 $2").replace(/ <\//g," </").replace(/ <o:p><\/o:p>/g," <o:p></o:p>").replace(/<o:p>(&nbsp;|\u00A0)<\/o:p>/g,"").replace(/>([^\S\r\n]*[\r\n]\s*)</g,"><")}(function(e){const t="</body>",n="</html>",r=e.indexOf(t);if(r<0)return e;const s=e.indexOf(n,r+t.length);return e.substring(0,r+t.length)+(s>=0?e.substring(s):"")}
/**
 * @license Copyright (c) 2003-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */(e=(e=e.replace(/<!--\[if gte vml 1]>/g,"")).replace(/<o:SmartTagType(?:\s+[^\s>=]+(?:="[^"]*")?)*\s*\/?>/gi,""))),o=n.parseFromString(r,"text/html");!function(e){e.querySelectorAll("span[style*=spacerun]").forEach((e=>{const t=e,n=t.innerText.length||0;t.innerText=Array(n+1).join("  ").substr(0,n)}))}(o);const c=o.body.innerHTML,l=function(e,t){const n=new s(t),r=new i(n,{renderingMode:"data"}),o=e.createDocumentFragment(),c=e.body.childNodes;for(;c.length>0;)o.appendChild(c[0]);return r.domToView(o,{skipComments:!0})}(o,t),a=function(e){const t=[],n=[],r=Array.from(e.getElementsByTagName("style"));for(const e of r)e.sheet&&e.sheet.cssRules&&e.sheet.cssRules.length&&(t.push(e.sheet),n.push(e.innerHTML));return{styles:t,stylesString:n.join(" ")}}(o);return{body:l,bodyString:c,styles:a.styles,stylesString:a.stylesString}}class A extends e{static get pluginName(){return"PasteFromOffice"}static get requires(){return[t]}init(){const e=this.editor,t=e.plugins.get("ClipboardPipeline"),n=e.editing.view.document,r=[];r.push(new g(n)),r.push(new y(n)),r.push(new x(n)),t.on("inputTransformation",((t,s)=>{if(s._isTransformedWithPasteFromOffice)return;if(e.model.document.selection.getFirstPosition().parent.is("element","codeBlock"))return;const i=s.dataTransfer.getData("text/html"),o=r.find((e=>e.isActive(i)));o&&(s._parsedData||(s._parsedData=v(i,n.stylesProcessor)),o.execute(s),s._isTransformedWithPasteFromOffice=!0)}),{priority:"high"})}}export{g as MSWordNormalizer,A as PasteFromOffice,v as parseHtml};