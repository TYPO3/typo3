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
import{css as h,html as m}from"lit";import{property as c,state as f,customElement as g}from"lit/decorators.js";import{PseudoButtonLitElement as b}from"@typo3/backend/element/pseudo-button.js";import{ScaffoldState as s,ScaffoldSidebarToggleEvent as u}from"@typo3/backend/viewport/scaffold-state.js";import"@typo3/backend/element/icon-element.js";var r=function(n,e,a,i){var o=arguments.length,t=o<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,a):i,d;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")t=Reflect.decorate(n,e,a,i);else for(var p=n.length-1;p>=0;p--)(d=n[p])&&(t=(o<3?d(t):o>3?d(e,a,t):d(e,a))||t);return o>3&&t&&Object.defineProperty(e,a,t),t};let l=class extends b{constructor(){super(...arguments),this.labelCollapse="Collapse sidebar",this.labelExpand="Expand sidebar",this.expanded=!1,this.handleSidebarToggle=e=>{this.expanded=e.detail.expanded}}static{this.styles=[...b.styles,h`:host{display:inline-flex;align-items:center;justify-content:center;user-select:none}:host([disabled]){pointer-events:none;opacity:.5;cursor:default}`]}connectedCallback(){super.connectedCallback(),this.expanded=s.isLargeScreen()?s.isSidebarExpanded():s.isSidebarVisible(),this.updateLabels(),document.addEventListener(u.eventName,this.handleSidebarToggle)}disconnectedCallback(){super.disconnectedCallback(),document.removeEventListener(u.eventName,this.handleSidebarToggle)}updated(){this.updateLabels()}render(){const e=this.expanded?"actions-menu-sidebar-collapsed":"actions-menu-sidebar-expanded",a=this.disabled?"overlay-readonly":null;return m`<typo3-backend-icon identifier=${e} size=small overlay=${a}></typo3-backend-icon>`}buttonActivated(){s.toggleSidebar()}updateLabels(){const e=this.expanded?this.labelCollapse:this.labelExpand;this.title=e,this.setAttribute("aria-label",e)}};r([c({type:String,attribute:"label-collapse"})],l.prototype,"labelCollapse",void 0),r([c({type:String,attribute:"label-expand"})],l.prototype,"labelExpand",void 0),r([f()],l.prototype,"expanded",void 0),l=r([g("typo3-backend-sidebar-toggle")],l);export{l as SidebarToggleElement};
