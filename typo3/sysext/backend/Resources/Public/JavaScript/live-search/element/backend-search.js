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
import{customElement as p}from"lit/decorators.js";import{LitElement as a}from"lit";var m=function(n,t,r,c){var o=arguments.length,e=o<3?t:c===null?c=Object.getOwnPropertyDescriptor(t,r):c,f;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,r,c);else for(var l=n.length-1;l>=0;l--)(f=n[l])&&(e=(o<3?f(e):o>3?f(t,r,e):f(t,r))||e);return o>3&&e&&Object.defineProperty(t,r,e),e};let i=class extends a{createRenderRoot(){return this}};i=m([p("typo3-backend-live-search")],i);export{i as BackendSearch};
