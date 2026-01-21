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

/**
 * Module: @typo3/backend/Contrib/bootstrap
 *
 * This module bundles the `bootstrap` package and allows to add compatibility
 * layers based on `@typo3/*` packages (which will not be bundled).
 *
 * The bundle configuration is located next to `filesToBundle`
 * in `process-javascript` Grunt task.
 */

//export { default as Alert } from 'bootstrap/js/src/alert.js';
//export { default as Button } from 'bootstrap/js/src/button.js';
export { default as Carousel } from 'bootstrap/js/src/carousel.js';
export { default as Collapse } from 'bootstrap/js/src/collapse.js';
export { default as Dropdown } from 'bootstrap/js/src/dropdown.js';
//export { default as Modal } from 'bootstrap/js/src/modal.js';
//export { default as Offcanvas } from 'bootstrap/js/src/offcanvas.js';
export { default as Popover } from 'bootstrap/js/src/popover.js';
//export { default as ScrollSpy } from 'bootstrap/js/src/scrollspy.js';
export { default as Tab } from 'bootstrap/js/src/tab.js';
//export { default as Toast } from 'bootstrap/js/src/toast.js';
//export { default as Tooltip } from 'bootstrap/js/src/tooltip.js';
