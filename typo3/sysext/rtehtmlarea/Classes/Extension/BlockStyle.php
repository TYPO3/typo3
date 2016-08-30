<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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

use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Block Style extension for htmlArea RTE
 */
class BlockStyle extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'BlockStyle';

    /**
     * The comma-separated list of button names that the extension id adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'blockstyle';

    /**
     * The comma-separated list of label names that the extension id adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginLabels = 'blockstylelabel';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'blockstylelabel' => 'I[Block style label]',
        'blockstyle' => 'BlockStyle'
    ];
}
