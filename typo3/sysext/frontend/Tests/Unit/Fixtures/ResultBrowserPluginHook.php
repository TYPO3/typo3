<?php

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

namespace TYPO3\CMS\Frontend\Tests\Unit\Fixtures;

use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * Class using the [\TYPO3\CMS\Frontend\Plugin\AbstractPlugin::class]['pi_list_browseresults'] hook
 */
class ResultBrowserPluginHook
{
    /**
     * Returns a results browser. This method is a stub to test the hook in AbstractPlugin::pi_list_browseresults().
     *
     * @param int $showResultCount Determines how the results of the pagerowser will be shown. See description below
     * @param string $tableParams Attributes for the table tag which is wrapped around the table cells containing the browse links
     * @param array $wrapArr Array with elements to overwrite the default $wrapper-array.
     * @param string $pointerName varname for the pointer.
     * @param bool $hscText Enable htmlspecialchars() for the pi_getLL function (set this to FALSE if you want f.e use images instead of text for links like 'previous' and 'next').
     * @param bool $forceOutput Forces the output of the page browser if you set this option to "TRUE" (otherwise it's only drawn if enough entries are available)
     * @param AbstractPlugin $pObj Instance of the calling object
     * @return string Output HTML-Table, wrapped in <div>-tags with a class attribute (if $wrapArr is not passed,
     */
    public function pi_list_browseresults($showResultCount, $tableParams, array $wrapArr, $pointerName, $hscText, $forceOutput, AbstractPlugin $pObj)
    {
    }
}
