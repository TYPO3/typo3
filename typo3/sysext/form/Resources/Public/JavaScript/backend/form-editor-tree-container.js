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
import{LitElement as c,html as f}from"lit";import{query as p,customElement as h}from"lit/decorators.js";import"@typo3/backend/tree/tree-toolbar.js";import"@typo3/form/backend/form-editor-tree.js";var l=function(i,e,t,n){var a=arguments.length,r=a<3?e:n===null?n=Object.getOwnPropertyDescriptor(e,t):n,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")r=Reflect.decorate(i,e,t,n);else for(var d=i.length-1;d>=0;d--)(s=i[d])&&(r=(a<3?s(r):a>3?s(e,t,r):s(e,t))||r);return a>3&&r&&Object.defineProperty(e,t,r),r};const m="typo3-backend-navigation-component-formeditortree";let o=class extends c{async setNodes(e){await this.updateComplete,this.tree&&this.tree.setNodes(e)}setSelectedNode(e){this.tree&&this.tree.setSelectedNode(e)}search(e){this.tree&&this.tree.search(e)}setNodeValidationError(e,t=!0){this.tree&&this.tree.setNodeValidationError(e,t)}setNodeChildHasError(e,t=!0){this.tree&&this.tree.setNodeChildHasError(e,t)}clearAllValidationErrors(){this.tree&&this.tree.clearAllValidationErrors()}createRenderRoot(){return this}render(){return f`<typo3-backend-tree-toolbar .tree=${this.tree} .showRefresh=${!1} id=typo3-formeditortree-toolbar></typo3-backend-tree-toolbar><typo3-backend-navigation-component-formeditor-tree id=typo3-formeditortree-tree></typo3-backend-navigation-component-formeditor-tree>`}firstUpdated(){this.toolbar&&this.tree&&(this.toolbar.tree=this.tree),this.dispatchEvent(new CustomEvent("typo3:tree-container:ready",{bubbles:!0,composed:!0}))}};l([p("typo3-backend-navigation-component-formeditor-tree")],o.prototype,"tree",void 0),l([p("typo3-backend-tree-toolbar")],o.prototype,"toolbar",void 0),o=l([h("typo3-backend-navigation-component-formeditortree")],o);export{o as FormEditorTreeContainer,m as navigationComponentName};
