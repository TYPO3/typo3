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
define(["require","exports","TYPO3/CMS/Backend/ModuleMenu","TYPO3/CMS/Backend/Viewport","TYPO3/CMS/Core/Contrib/document-register-element-polyfill"],(function(e,t,n,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.ImmediateActionElement=void 0;class i extends HTMLElement{static getDelegate(e){switch(e){case"TYPO3.ModuleMenu.App.refreshMenu":return n.App.refreshMenu.bind(n);case"TYPO3.Backend.Topbar.refresh":return r.Topbar.refresh.bind(r.Topbar);default:throw Error('Unknown action "'+e+'"')}}static get observedAttributes(){return["action"]}attributeChangedCallback(e,t,n){"action"===e&&(this.action=n)}connectedCallback(){if(!this.action)throw new Error("Missing mandatory action attribute");i.getDelegate(this.action).apply(null,[])}}t.ImmediateActionElement=i,window.customElements.define("typo3-immediate-action",i)}));