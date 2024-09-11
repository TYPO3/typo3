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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;

final class AbstractTypolinkBuilderFixture extends AbstractTypolinkBuilder
{
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        return new LinkResult('type', 'url');
    }

    public function forceAbsoluteUrl(string $url, array $configuration, ?ServerRequestInterface $request = null): string
    {
        return parent::forceAbsoluteUrl($url, $configuration, $request);
    }

    public function resolveTargetAttribute(array $conf, string $name, ?ContentObjectRenderer $contentObjectRenderer = null): string
    {
        return parent::resolveTargetAttribute($conf, $name, $contentObjectRenderer);
    }
}
