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
import{Tooltip as BootstrapTooltip}from"bootstrap";import DocumentService from"@typo3/core/document-service.js";class Tooltip{static applyAttributes(t,o){for(const[e,i]of Object.entries(t))o.setAttribute(e,i)}constructor(){DocumentService.ready().then(()=>{this.initialize('[data-bs-toggle="tooltip"]')})}initialize(t,o={}){0===Object.entries(o).length&&(o={container:"body",trigger:"hover",delay:{show:500,hide:100}});const e=document.querySelectorAll(t);for(const t of e)BootstrapTooltip.getOrCreateInstance(t,o)}show(t,o){const e={"data-bs-placement":"auto",title:o};if(t instanceof NodeList)for(const o of t)Tooltip.applyAttributes(e,o),BootstrapTooltip.getInstance(o).show();else if(t instanceof HTMLElement)return Tooltip.applyAttributes(e,t),void BootstrapTooltip.getInstance(t).show()}hide(t){if(t instanceof NodeList)for(const o of t){const t=BootstrapTooltip.getInstance(o);null!==t&&t.hide()}else t instanceof HTMLElement&&BootstrapTooltip.getInstance(t).hide()}}const tooltipObject=new Tooltip;TYPO3.Tooltip=tooltipObject;export default tooltipObject;