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
import{property as d,customElement as c}from"lit/decorators.js";import{LitElement as f,html as l,nothing as u}from"lit";import{repeat as v}from"lit/directives/repeat.js";import{unsafeHTML as b}from"lit/directives/unsafe-html.js";import{nl2br as _}from"@typo3/core/directive/nl2br.js";var p=function(s,e,r,n){var o=arguments.length,t=o<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(s,e,r,n);else for(var m=s.length-1;m>=0;m--)(i=s[m])&&(t=(o<3?i(t):o>3?i(e,r,t):i(e,r))||t);return o>3&&t&&Object.defineProperty(e,r,t),t};let a=class extends f{constructor(){super(...arguments),this.comments=[]}createRenderRoot(){return this}render(){return l`<div>${v(this.comments,e=>e.tstamp,e=>this.renderComment(e))}</div>`}renderComment(e){return l`<div class=media><div class="media-left text-center"><div>${b(e.user_avatar)}</div>${e.user_username}</div><div class="panel panel-default">${e.user_comment?l`<div class=panel-body>${_(e.user_comment)}</div>`:u}<div class=panel-footer><span class="badge badge-success me-2"> ${e.previous_stage_title} ⇾ ${e.stage_title} </span> <span class="badge badge-info">${e.tstamp} </span></div></div></div>`}};p([d({type:Array})],a.prototype,"comments",void 0),a=p([c("typo3-workspaces-comment-view")],a);export{a as CommentViewElement};
