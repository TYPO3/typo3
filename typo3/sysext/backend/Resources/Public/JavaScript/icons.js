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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import ClientStorage from"@typo3/backend/storage/client.js";import{Sizes,States,MarkupIdentifiers}from"@typo3/backend/enum/icon-types.js";import{css}from"lit";import{DedupeAsyncTask}from"@typo3/core/cache/dedupe-async-task.js";export class IconStyles{static getStyles(){return[css`
        :host {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          height: var(--icon-size, 1em);
          width: var(--icon-size, 1em)
          line-height: var(--icon-size, 1em);
          vertical-align: -22%
        }

        :host([size=default]),
        :host([raw]) .icon-size-default {
          --icon-size: 1em;
        }

        :host([size=small]),
        :host([raw]) .icon-size-small {
          --icon-size: var(--icon-size-small, 16px)
        }

        :host([size=medium]),
        :host([raw]) .icon-size-medium {
          --icon-size: var(--icon-size-medium, 32px)
        }

        :host([size=large]),
        :host([raw]) .icon-size-large {
          --icon-size: var(--icon-size-large, 48px)
        }

        :host([size=mega]),
        :host([raw]) .icon-size-mega {
          --icon-size: var(--icon-size-mega, 64px)
        }

        .icon {
          position: relative;
          display: flex;
          overflow: hidden;
          white-space: nowrap;
          color: var(--icon-color-primary, currentColor);
          height: var(--icon-size, 1em);
          width: var(--icon-size, 1em);
          line-height: var(--icon-size, 1em);
          flex-shrink: 0;
        }

        .icon img, .icon svg {
          display: block;
          height: 100%;
          width: 100%
        }

        .icon * {
          display: block;
          line-height: inherit
        }

        .icon-markup {
          position: absolute;
          display: block;
          text-align: center;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0
        }

        .icon-overlay {
          position: absolute;
          bottom: 0;
          right: 0;
          height: 68.75%;
          width: 68.75%;
          text-align: center
        }

        .icon-spin .icon-markup {
          -webkit-animation: icon-spin 2s infinite linear;
          animation: icon-spin 2s infinite linear
        }

        @keyframes icon-spin {
          0% {
            transform: rotate(0)
          }
          100% {
            transform: rotate(360deg)
          }
        }

        .icon-state-disabled .icon-markup {
          opacity: var(--icon-opacity-disabled, 0.5)
        }

        .icon-unify {
          line-height: var(--icon-size, 1em);
          font-size: calc(var(--icon-size, 1em) * var(--icon-unify-modifier, .86))
        }

        .icon-overlay .icon-unify {
          line-height: calc(var(--icon-size, 1em) / 1.6);
          font-size: calc((var(--icon-size, 1em) / 1.6) * var(--icon-unify-modifier, .86))
        }
      `]}}class Icons{constructor(){this.sizes=Sizes,this.states=States,this.markupIdentifiers=MarkupIdentifiers,this.promiseCache=new DedupeAsyncTask}getIcon(e,i,t,s,n,o){const r=[e,i=i||Sizes.default,t,s=s||States.default,n=n||MarkupIdentifiers.default],a=r.join("_");return this.getIconRegistryCache().then((e=>(ClientStorage.isset("icon_registry_cache_identifier")&&ClientStorage.get("icon_registry_cache_identifier")===e||(ClientStorage.unsetByPrefix("icon_"),ClientStorage.set("icon_registry_cache_identifier",e)),this.fetchFromLocal(a).then(null,(()=>this.fetchFromRemote(r,a,o))))))}getIconRegistryCache(){return this.promiseCache.get("icon_registry_cache_identifier",(async e=>{const i=await new AjaxRequest(TYPO3.settings.ajaxUrls.icons_cache).get({signal:e});return await i.resolve()}))}fetchFromRemote(e,i,t){return this.promiseCache.get(i,(async t=>{const s=await new AjaxRequest(TYPO3.settings.ajaxUrls.icons).withQueryArguments({icon:JSON.stringify(e)}).get({signal:t}),n=await s.resolve();return!s.response.redirected&&n.startsWith("<span")&&n.includes("t3js-icon")&&n.includes('<span class="icon-markup">')&&ClientStorage.set("icon_"+i,n),n}),t)}fetchFromLocal(e){return ClientStorage.isset("icon_"+e)?Promise.resolve(ClientStorage.get("icon_"+e)):Promise.reject()}}let iconsObject;iconsObject||(iconsObject=new Icons,"undefined"!=typeof TYPO3&&(TYPO3.Icons=iconsObject));export default iconsObject;