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

'use strict';

/**
 * This is a TYPO3 plugin for rte_ckeditor which provides extra
 * CSS for styling broken links.
 *
 * Broken links can be detected by checking if the data-rte-error
 * attribute is set for the <a> element. This attribute is typically
 * set in RteHtmlParser.
 *
 * The default styling used here can be modified:
 * 1. Do not include this plugin
 * 2. And copy CSS from styles/showbrokenlinks.css to your CSS file
 *   for rte_ckeditor and modify it.
 */
CKEDITOR.plugins.add('showbrokenlinks', {
    init: function (editor) {
        var pluginDirectory = this.path;
        editor.addContentsCss( pluginDirectory + 'styles/showbrokenlinks.css' );
    }
});