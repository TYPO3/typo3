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
import{property as l,customElement as N}from"lit/decorators.js";import{LitElement as R,html as o,nothing as p}from"lit";import g from"@typo3/backend/modal.js";import"@typo3/backend/element/icon-element.js";import M from"@typo3/core/ajax/ajax-request.js";import E from"@typo3/backend/notification.js";import f from"@typo3/backend/viewport.js";import S from"@typo3/core/event/regular-event.js";import{KeyTypesEnum as v}from"@typo3/backend/enum/key-types.js";import{RecordUsageStore as T}from"@typo3/backend/record-usage/record-usage-store.js";import w from"@typo3/backend/storage/client.js";import{topWindow as U}from"@typo3/core/utility/top-window.js";import k from"@typo3/backend/storage/persistent.js";import u from"~labels/core.misc";var d=function(c,e,t,r){var a=arguments.length,i=a<3?e:r===null?r=Object.getOwnPropertyDescriptor(e,t):r,n;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")i=Reflect.decorate(c,e,t,r);else for(var m=c.length-1;m>=0;m--)(n=c[m])&&(i=(a<3?n(i):a>3?n(e,t,i):n(e,t))||i);return a>3&&i&&Object.defineProperty(e,t,i),i};const C={fromAttribute:c=>c===null?!0:c.toLowerCase()==="true",toAttribute:c=>c?"true":"false"};class b{constructor(e,t,r,a,i,n,m,z,x,$){this.identifier=e,this.label=t,this.description=r,this.icon=a,this.iconOverlay=i,this.url=n,this.requestType=m,this.defaultValues=z,this.saveAndClose=x,this.event=$,this.visible=!0}static fromData(e){return new b(e.identifier,e.label,e.description,e.icon,e.iconOverlay,e.url??null,e.requestType??"location",e.defaultValues??[],e.saveAndClose??!1,e.event??null)}reset(){this.visible=!0}}class h{constructor(e,t,r,a,i){this.identifier=e,this.label=t,this.items=r,this.icon=a,this.featured=i,this.disabled=!1}static fromData(e){return new h(e.identifier,e.label,e.items.map(t=>b.fromData(t)))}reset(){this.disabled=!1,this.items.forEach(e=>{e.reset()})}activeItems(){return this.items.filter(e=>e.visible)??[]}}class y{constructor(e){this.items=e}static fromData(e){return new y(Object.values(e).map(t=>h.fromData(t)))}reset(){this.items.forEach(e=>{e.reset()})}categoriesWithItems(){return this.items.filter(e=>e.activeItems().length>0)??[]}}const I="wizard-last-category/",A=`
typo3-backend-new-record-wizard {
  display: block;
  container-type: inline-size;
  height: 100%;
}

typo3-backend-new-record-wizard .element {
  display: flex;
  flex-direction: column;
  gap: var(--typo3-spacing);
  font-size: var(--typo3-component-font-size);
  line-height: var(--typo3-component-line-height);
  height: 100%;
}

typo3-backend-new-record-wizard .main {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: calc(var(--typo3-spacing) * 2);
}

@container (min-width: 500px) {
  typo3-backend-new-record-wizard .main {
    flex-direction: row;
    overflow: hidden;
  }
}

typo3-backend-new-record-wizard .main > * {
  flex-grow: 1;
  padding-inline-end: calc(var(--typo3-spacing) / 4);
}

typo3-backend-new-record-wizard .navigation {
  position: relative;
  flex-shrink: 0;
}

@container (min-width: 500px) {
  typo3-backend-new-record-wizard .navigation {
    flex-grow: 0;
    width: 200px;
    overflow-block: auto;
  }
}

@container (min-width: 500px) {
  typo3-backend-new-record-wizard .navigation-toggle {
    display: none !important;
  }
}

typo3-backend-new-record-wizard .navigation-list {
  display: none;
  flex-direction: column;
  gap: 2px;
  list-style: none;
  padding: 0;
  margin: 0;
}

typo3-backend-new-record-wizard .navigation-list.show {
  display: flex;
}

@container (max-width: 499px) {
  typo3-backend-new-record-wizard .navigation-list {
    z-index: 1;
    position: absolute;
    top: calc(100% + 2px);
    padding: 2px;
    background: var(--typo3-component-bg);
    border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
    border-radius: var(--typo3-component-border-radius);
    box-shadow: var(--typo3-component-box-shadow);
  }
}

@container (min-width: 500px) {
  typo3-backend-new-record-wizard .navigation-list {
    display: flex;
  }
}

typo3-backend-new-record-wizard .navigation-item {
  cursor: pointer;
  align-items: center;
  display: flex;
  width: 100%;
  gap: calc(var(--typo3-spacing) / 2);
  text-align: start;
  color: inherit;
  background: transparent;
  border: var(--typo3-component-border-width) solid var(--typo3-component-border-color);
  border-radius: var(--typo3-component-border-radius);
  padding: var(--typo3-list-item-padding-y) var(--typo3-list-item-padding-x);
}

typo3-backend-new-record-wizard .navigation-item-featured:has(+ .navigation-item:not(.navigation-item-featured)) {
  margin-bottom: calc(var(--typo3-spacing) / 2);
}

@container (max-width: 499px) {
  typo3-backend-new-record-wizard .navigation-item {
    --typo3-component-border-color: transparent;
    margin-bottom: 0 !important;
    border-radius: calc(var(--typo3-component-border-radius) - var(--typo3-component-border-width));
  }
}

typo3-backend-new-record-wizard .navigation-item:hover {
  color: var(--typo3-component-hover-color);
  background: var(--typo3-component-hover-bg);
  border-color: var(--typo3-component-hover-border-color);
}

typo3-backend-new-record-wizard .navigation-item:focus {
  outline: none;
  color: var(--typo3-component-focus-color);
  background: var(--typo3-component-focus-bg);
  border-color: var(--typo3-component-focus-border-color);
}

typo3-backend-new-record-wizard .navigation-item.active {
  color: var(--typo3-component-active-color);
  background: var(--typo3-component-active-bg);
  border-color: var(--typo3-component-active-border-color);
}

typo3-backend-new-record-wizard .navigation-item.active:focus-visible {
  outline: var(--typo3-outline-width) var(--typo3-outline-style) color-mix(in srgb, var(--typo3-component-active-border-color), transparent 25%);
}

typo3-backend-new-record-wizard .navigation-item[disabled] {
  cursor: not-allowed;
  color: var(--typo3-component-disabled-color);
  background: var(--typo3-component-disabled-bg);
  border-color: var(--typo3-component-disabled-border-color);
}

typo3-backend-new-record-wizard .navigation-item-label {
  flex-grow: 1;
}

typo3-backend-new-record-wizard .navigation-item-count {
  opacity: .75;
  flex-shrink: 0;
}

typo3-backend-new-record-wizard .content {
  container-type: inline-size;
  overflow-block: auto;
}

typo3-backend-new-record-wizard .elementwizard-categories {
  display: grid;
  gap: var(--typo3-spacing);
}

typo3-backend-new-record-wizard .elementwizard-category-headline {
  font-weight: bold;
  color: var(--typo3-text-color-variant);
  margin-bottom: calc(var(--typo3-spacing) / 2);
}

typo3-backend-new-record-wizard .elementwizard-category-items {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: var(--typo3-spacing);
}

@container (min-width: 500px) {
  typo3-backend-new-record-wizard .elementwizard-category-items {
    grid-template-columns: repeat(2, 1fr);
  }
}

@container (min-width: 750px) {
  typo3-backend-new-record-wizard .elementwizard-category-items {
    grid-template-columns: repeat(3, 1fr);
  }
}

typo3-backend-new-record-wizard .item {
  cursor: pointer;
  display: flex;
  gap: calc(var(--typo3-spacing) / 2);
  text-align: start;
  border: var(--typo3-component-border-width) solid transparent;
  border-radius: var(--typo3-component-border-radius);
  padding: var(--typo3-list-item-padding-y) var(--typo3-list-item-padding-x);
  background: transparent;
  color: inherit;
}

typo3-backend-new-record-wizard .item:hover {
  color: var(--typo3-component-hover-color);
  background: var(--typo3-component-hover-bg);
  border-color: var(--typo3-component-hover-border-color);
}

typo3-backend-new-record-wizard .item:focus {
  outline: none;
  color: var(--typo3-component-focus-color);
  background: var(--typo3-component-focus-bg);
  border-color: var(--typo3-component-focus-border-color);
}

typo3-backend-new-record-wizard .item-body-label {
  text-wrap: balance;
  font-weight: bold;
  margin-bottom: .25rem;
}

typo3-backend-new-record-wizard .item-body-description {
  opacity: .75;
  text-wrap: pretty;
}
`;let s=class extends R{constructor(){super(...arguments),this.categories=new y([]),this.searchPlaceholder=u.get("newRecordWizard.filter.placeholder"),this.searchNothingFoundLabel=u.get("newRecordWizard.filter.noResults"),this.userNotAllowedLabel=u.get("newContentElement.filter.userNotAllowed"),this.displayMenu=!0,this.displayFilter=!0,this.selectedCategory=null,this.searchTerm="",this.messages=[],this.toggleMenu=!1,this.storeName=null,this.hasNavigation=!1}createRenderRoot(){return this}firstUpdated(){if(!document.getElementById("typo3-new-record-wizard-styles")){const t=document.createElement("style");t.id="typo3-new-record-wizard-styles",t.textContent=A,document.head.appendChild(t)}this.displayFilter===!0&&this.renderRoot.querySelector('input[name="search"]').focus();const e=k.isset("displayRecentlyUsed")?!!JSON.parse(k.get("displayRecentlyUsed")):!0;this.storeName&&(this.recordUsageStore=new T(this.storeName),e&&this.addRecentlyUsedCategory()),this.selectAvailableCategory()}addRecentlyUsedCategory(){const e=this.recordUsageStore.getUsage();if(Object.keys(e).length===0)return;const t=this.categories.items.flatMap(r=>r.items.filter(a=>a.identifier in e)).sort((r,a)=>{const i=e[r.identifier],n=e[a.identifier];return n.count!==i.count?n.count-i.count:n.lastUsed-i.lastUsed}).slice(0,10);if(t.length>0){const r=new h("recently-used",u.get("newRecordWizard.recentlyUsed"),t,"actions-history",!0);this.categories.items.unshift(r)}}selectAvailableCategory(){let e=null;this.storeName&&(e=w.get(this.getCategoryLocalStorageKey())),this.categories.categoriesWithItems().filter(r=>r===this.selectedCategory).length===0&&(e?this.selectedCategory=this.categories.categoriesWithItems().find(r=>r.identifier===e)??this.categories.categoriesWithItems()[0]??null:this.selectedCategory=this.categories.categoriesWithItems()[0]??null),this.messages=[],this.categories.items.length===0?this.messages=[{message:this.userNotAllowedLabel,severity:"info"}]:this.selectedCategory===null&&(this.messages=[{message:this.searchNothingFoundLabel,severity:"info"}])}filter(e){this.searchTerm=e,this.categories.reset(),this.categories.items.forEach(t=>{const r=t.label.trim().replace(/\s+/g," ");!(this.searchTerm!==""&&!RegExp(this.searchTerm,"i").test(r))||t.items.forEach(i=>{const n=i.label.trim().replace(/\s+/g," ")+i.description?.trim().replace(/\s+/g," ");i.visible=!(this.searchTerm!==""&&!RegExp(this.searchTerm,"i").test(n))}),t.disabled=t.items.filter(i=>i.visible).length===0}),this.selectAvailableCategory()}willUpdate(){const e=this.selectedCategory!==null&&this.displayMenu===!0&&this.categories.items.length>1;this.hasNavigation!==e&&(this.hasNavigation=e)}render(){return o`<div class=element>${this.displayFilter===!0?this.renderFilter():p} ${this.renderMessages()} ${this.selectedCategory===null?p:o`<div class=main>${this.categories.items.length>1&&this.displayMenu===!0?o`<div class=navigation>${this.renderNavigationToggle()} ${this.renderNavigationList()}</div>`:p}<div class=content>${this.renderCategories()}</div></div>`}</div>`}renderFilter(){return o`<form class=filter @submit=${e=>e.preventDefault()}><input name=search type=search autocomplete=off class=form-control .value=${this.searchTerm} @input=${e=>{this.filter(e.target.value)}} @keydown=${e=>{e.key===v.ESCAPE&&(e.stopImmediatePropagation(),this.filter(""))}} placeholder=${this.searchPlaceholder}></form>`}renderMessages(){return o`${this.messages.length>0?o`<div class=messages>${this.messages.map(e=>o`<div class="alert alert-${e.severity}" role=alert>${e.message}</div>`)}</div>`:p}`}renderNavigationToggle(){return o`<button class="navigation-toggle btn btn-default" @click=${()=>{this.toggleMenu=!this.toggleMenu}}>${this.selectedCategory.icon?o`<typo3-backend-icon identifier=${this.selectedCategory.icon} size=small></typo3-backend-icon>`:p} ${this.selectedCategory.label}<typo3-backend-icon identifier=actions-chevron-${this.toggleMenu===!0?"up":"down"} size=small></typo3-backend-icon></button>`}renderNavigationList(){return o`<div class=navigation-list${this.toggleMenu===!0?" show":""} role=tablist aria-orientation=vertical>${this.categories.items.map(e=>o`<button data-identifier=${e.identifier} class=navigation-item${e.featured?" navigation-item-featured":""}${this.selectedCategory===e?" active":""} ?disabled=${e.disabled} @click=${()=>{this.handleNavigationClick(e)}} @keydown=${t=>{this.handleNavigationKeydown(t,e)}} role=tab aria-selected=${this.selectedCategory===e?"true":"false"} tabindex=${this.selectedCategory===e?"0":"-1"}>${e.icon?o`<span class=navigation-item-icon><typo3-backend-icon identifier=${e.icon} size=small></typo3-backend-icon></span>`:p} <span class=navigation-item-label>${e.label}</span> <span class=navigation-item-count>${e.activeItems().length}</span></button>`)}</div>`}handleNavigationClick(e){this.selectedCategory=e,this.toggleMenu=!1,this.storeName&&w.set(this.getCategoryLocalStorageKey(),e.identifier)}handleNavigationKeydown(e,t){const r=this.categories.categoriesWithItems(),a=r.findIndex(n=>n.identifier===t.identifier);let i;if(e.key===v.UP)i=r[a-1]??void 0;else if(e.key===v.DOWN)i=r[a+1]??void 0;else return;i&&(this.renderRoot.querySelector(`button[data-identifier="${i.identifier}"]`).focus(),this.handleNavigationClick(i))}renderCategories(){return o`<div class=elementwizard-categories>${this.categories.items.map(e=>this.renderCategory(e))}</div>`}renderCategory(e){return o`${(this.selectedCategory===e||this.displayMenu===!1)&&!e.disabled?o`<div class=elementwizard-category role=${this.displayMenu?"tabpanel":p}>${this.displayMenu===!1?o`<div class=elementwizard-category-headline>${e.icon?o`<typo3-backend-icon identifier=${e.icon} size=small></typo3-backend-icon>`:p} ${e.label}</div>`:p}<div class=elementwizard-category-items>${e.items.map(t=>this.renderCategoryItem(t))}</div></div>`:p}`}renderCategoryItem(e){return o`${e.visible?o`<button type=button class=item data-identifier=${e.identifier} @click=${t=>{t.preventDefault(),this.handleItemClick(e)}}><div class=item-icon><typo3-backend-icon identifier=${e.icon||"empty-empty"} overlay=${e.iconOverlay} size=medium></typo3-backend-icon></div><div class=item-body><div class=item-body-label>${e.label}</div><div class=item-body-description>${e.description}</div></div></button>`:p}`}handleItemClick(e){if(this.storeName&&this.recordUsageStore.track(e.identifier),e.requestType==="event"){const t=new CustomEvent(e.event,{detail:{item:e},cancelable:!0,bubbles:!0,composed:!0});this.dispatchEvent(t),t.defaultPrevented||g.dismiss();return}if(e.url.trim()!==""){if(e.requestType==="location"){f.ContentContainer.setUrl(e.url.replace(/_CURRENT_MODULE_/g,U.TYPO3.ModuleMenu.App.getCurrentModule()??"")),g.dismiss();return}e.requestType==="ajax"&&new M(e.url).post({defVals:e.defaultValues,saveAndClose:e.saveAndClose?"1":"0"}).then(async t=>{const r=document.createRange().createContextualFragment(await t.resolve());g.currentModal.addEventListener("modal-updated",()=>{new S("click",(a,i)=>{a.preventDefault();const n=i.dataset.target;n&&(f.ContentContainer.setUrl(n),g.dismiss())}).delegateTo(g.currentModal,"button[data-target]")}),g.currentModal.setContent(r)}).catch(()=>{E.error("Could not load module data")})}}getCategoryLocalStorageKey(){return I+this.storeName}};d([l({type:Object,converter:{fromAttribute:c=>{const e=JSON.parse(c);return y.fromData(e)}}})],s.prototype,"categories",void 0),d([l({type:String})],s.prototype,"searchPlaceholder",void 0),d([l({type:String})],s.prototype,"searchNothingFoundLabel",void 0),d([l({type:String})],s.prototype,"userNotAllowedLabel",void 0),d([l({type:Boolean,converter:C})],s.prototype,"displayMenu",void 0),d([l({type:Boolean,converter:C})],s.prototype,"displayFilter",void 0),d([l({type:String,attribute:!1})],s.prototype,"selectedCategory",void 0),d([l({type:String,attribute:!1})],s.prototype,"searchTerm",void 0),d([l({type:Array,attribute:!1})],s.prototype,"messages",void 0),d([l({type:Boolean,attribute:!1})],s.prototype,"toggleMenu",void 0),d([l({type:String})],s.prototype,"storeName",void 0),d([l({type:Boolean,reflect:!0,attribute:"has-navigation"})],s.prototype,"hasNavigation",void 0),s=d([N("typo3-backend-new-record-wizard")],s);export{y as Categories,h as Category,s as NewRecordWizard};
