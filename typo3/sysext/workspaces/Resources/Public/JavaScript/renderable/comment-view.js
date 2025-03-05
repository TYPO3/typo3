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
var __decorate=function(e,t,r,o){var n,m=arguments.length,s=m<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,r):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,r,o);else for(var i=e.length-1;i>=0;i--)(n=e[i])&&(s=(m<3?n(s):m>3?n(t,r,s):n(t,r))||s);return m>3&&s&&Object.defineProperty(t,r,s),s};import{customElement,property}from"lit/decorators.js";import{html,LitElement,nothing}from"lit";import{repeat}from"lit/directives/repeat.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";let CommentViewElement=class extends LitElement{constructor(){super(...arguments),this.comments=[]}createRenderRoot(){return this}render(){return html`<div>${repeat(this.comments,(e=>e.tstamp),(e=>this.renderComment(e)))}</div>`}renderComment(e){return html`<div class="media"><div class="media-left text-center"><div>${unsafeHTML(e.user_avatar)}</div>${e.user_username}</div><div class="panel panel-default">${e.user_comment?html`<div class="panel-body">${e.user_comment}</div>`:nothing}<div class="panel-footer"><span class="badge badge-success me-2">${e.previous_stage_title} > ${e.stage_title} </span><span class="badge badge-info">${e.tstamp}</span></div></div></div>`}};__decorate([property({type:Array})],CommentViewElement.prototype,"comments",void 0),CommentViewElement=__decorate([customElement("typo3-workspaces-comment-view")],CommentViewElement);export{CommentViewElement};