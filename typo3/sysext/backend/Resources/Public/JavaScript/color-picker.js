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
import $ from"jquery";import"jquery/minicolors.js";class ColorPicker{initialize(e){if(void 0===e)return console.warn("Initializing all color pickers globally has been marked as deprecated. Please pass a specific element to ColorPicker.initialize()."),void document.querySelectorAll(".t3js-color-picker").forEach((e=>{this.initialize(e)}));if(!(e instanceof HTMLInputElement)||e.parentElement?.classList.contains("minicolors"))return;$(e).minicolors({format:"hex",position:"bottom left",theme:"bootstrap"});const t=e.closest(".t3js-formengine-field-item")?.querySelector('input[type="hidden"]');t&&(t.addEventListener("change",(()=>$(e).trigger("paste"))),e.addEventListener("blur",(e=>{e.stopImmediatePropagation();const i=e.target;t.value=i.value,""===i.value&&$(i).trigger("paste"),i.dispatchEvent(new Event("formengine.cp.change"))})))}}export default new ColorPicker;