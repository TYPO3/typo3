<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Typolink;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Used to generate a link to a page or file, an external URL or any other protocol
 * in the frontend or backend.
 * The actual resolving of the Link happens in LinkFactory
 */
interface TypolinkBuilderInterface
{
    /**
     * @param array $linkDetails parsed link details by the LinkService
     * @param array $configuration the TypoLink configuration array
     * @param string $linkText the link text
     * @throws UnableToLinkException
     */
    public function buildLink(array $linkDetails, array $configuration, ServerRequestInterface $request, string $linkText = ''): LinkResultInterface;

}
