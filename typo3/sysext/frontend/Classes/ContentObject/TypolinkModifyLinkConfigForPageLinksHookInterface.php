<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
 * interface for classes which hook into \TYPO3\CMS\Frontend\ContentObjectRenderer and wish to modify the typolink
 * configuration of the page link.
 */
interface TypolinkModifyLinkConfigForPageLinksHookInterface
{
    /**
     * Modifies the typolink page link configuration array.
     *
     * @param array $linkConfiguration The link configuration (for options see TSRef -> typolink)
     * @param array $linkDetails Additional information for the link
     * @param array $pageRow The complete page row for the page to link to
     *
     * @return array The modified $linkConfiguration
     */
    public function modifyPageLinkConfiguration(array $linkConfiguration, array $linkDetails, array $pageRow): array;
}
