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
define(["require", "exports", 'jquery', "TYPO3/CMS/Core/Contrib/jquery.minicolors"], function (require, exports, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/ColorPicker
     * contains all logic for the color picker used in FormEngine
     * @exports TYPO3/CMS/Backend/ColorPicker
     */
    var ColorPicker = (function () {
        /**
         * The constructor, set the class properties default values
         */
        function ColorPicker() {
            this.selector = '.t3js-color-picker';
        }
        /**
         * Initialize the color picker for the given selector
         */
        ColorPicker.prototype.initialize = function () {
            $(this.selector).minicolors({
                format: 'hex',
                position: 'bottom left',
                theme: 'bootstrap',
            });
        };
        return ColorPicker;
    }());
    return new ColorPicker();
});
