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
import{property as c,customElement as f}from"lit/decorators.js";import{LitElement as u,html as p,nothing as l}from"lit";import{repeat as v}from"lit/directives/repeat.js";import{unsafeHTML as b}from"lit/directives/unsafe-html.js";import{nl2br as _}from"@typo3/core/directive/nl2br.js";import g from"~labels/workspaces.messages";var d=function(s,e,r,n){var a=arguments.length,t=a<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,r):n,o;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(s,e,r,n);else for(var m=s.length-1;m>=0;m--)(o=s[m])&&(t=(a<3?o(t):a>3?o(e,r,t):o(e,r))||t);return a>3&&t&&Object.defineProperty(e,r,t),t};let i=class extends u{constructor(){super(...arguments),this.comments=[]}createRenderRoot(){return this}render(){return p`<div>${v(this.comments,e=>e.tstamp,e=>this.renderComment(e))}</div>`}renderComment(e){return p`<div class=media><div class="media-left text-center"><div>${b(e.user_avatar)}</div>${e.user_username}</div><div class="panel panel-default">${e.user_comment?p`<div class=panel-body>${_(e.user_comment)}</div>`:l}<div class=panel-footer><span class="badge badge-success me-2"> ${e.previous_stage_title} ⇾ ${e.stage_title} </span> <span class="badge badge-info">${e.tstamp?g.get("comment.tstamp.value",{tstamp:new Date(e.tstamp)}):l} </span></div></div></div>`}};d([c({type:Array})],i.prototype,"comments",void 0),i=d([f("typo3-workspaces-comment-view")],i);export{i as CommentViewElement};
