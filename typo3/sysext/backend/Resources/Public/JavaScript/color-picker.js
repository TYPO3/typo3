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
import Alwan from"alwan";import RegularEvent from"@typo3/core/event/regular-event.js";class ColorPicker{initialize(e,t={}){if(e.classList.contains("t3js-colorpicker-initialized"))return;const o=new Alwan(e,{position:"bottom-start",format:"hex",opacity:t.opacity,preset:!1,color:e.value,swatches:t.swatches});e.classList.add("t3js-colorpicker-initialized"),o.on("color",(t=>{e.value=t.hex,e.dispatchEvent(new Event("blur"))})),["input","change"].forEach((t=>{new RegularEvent(t,(e=>{o.setColor(e.target.value)})).bindTo(e)}))}}export default new ColorPicker;