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
import{property as d,customElement as c}from"lit/decorators.js";import{LitElement as u,html as m,nothing as v}from"lit";import{repeat as f}from"lit/directives/repeat.js";import{unsafeHTML as _}from"lit/directives/unsafe-html.js";var p=function(s,e,r,n){var i=arguments.length,t=i<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,o;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(s,e,r,n);else for(var l=s.length-1;l>=0;l--)(o=s[l])&&(t=(i<3?o(t):i>3?o(e,r,t):o(e,r))||t);return i>3&&t&&Object.defineProperty(e,r,t),t};let a=class extends u{constructor(){super(...arguments),this.comments=[]}createRenderRoot(){return this}render(){return m`<div>${f(this.comments,e=>e.tstamp,e=>this.renderComment(e))}</div>`}renderComment(e){return m`<div class=media><div class="media-left text-center"><div>${_(e.user_avatar)}</div>${e.user_username}</div><div class="panel panel-default">${e.user_comment?m`<div class=panel-body>${e.user_comment}</div>`:v}<div class=panel-footer><span class="badge badge-success me-2"> ${e.previous_stage_title} > ${e.stage_title} </span> <span class="badge badge-info">${e.tstamp} </span></div></div></div>`}};p([d({type:Array})],a.prototype,"comments",void 0),a=p([c("typo3-workspaces-comment-view")],a);export{a as CommentViewElement};
