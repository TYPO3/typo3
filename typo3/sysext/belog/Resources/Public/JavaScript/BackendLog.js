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
import DocumentService from"TYPO3/CMS/Core/DocumentService.js";import DateTimePicker from"TYPO3/CMS/Backend/DateTimePicker.js";import"TYPO3/CMS/Backend/Input/Clearable.js";class BackendLog{constructor(){this.clearableElements=null,this.dateTimePickerElements=null,DocumentService.ready().then(()=>{this.clearableElements=document.querySelectorAll(".t3js-clearable"),this.dateTimePickerElements=document.querySelectorAll(".t3js-datetimepicker"),this.initializeClearableElements(),this.initializeDateTimePickerElements()})}initializeClearableElements(){this.clearableElements.forEach(e=>e.clearable())}initializeDateTimePickerElements(){this.dateTimePickerElements.forEach(e=>DateTimePicker.initialize(e))}}export default new BackendLog;