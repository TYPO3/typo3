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
import Alwan from"alwan";class ColorPicker{initialize(e,t={}){if(e.classList.contains("t3js-colorpicker-initialized"))return;const i=new Alwan(e,{position:"bottom-start",format:"hex",opacity:!1,preset:!1,color:e.value,swatches:t.swatches});e.classList.add("t3js-colorpicker-initialized");const o=e.closest(".t3js-formengine-field-item")?.querySelector('input[type="hidden"]');o&&(o.addEventListener("change",(e=>{i.setColor(e.target.value)})),i.on("color",(t=>{e.value=t.hex,o.value=t.hex,e.dispatchEvent(new Event("blur"))})))}}export default new ColorPicker;