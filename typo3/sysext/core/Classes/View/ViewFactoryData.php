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

namespace TYPO3\CMS\Core\View;

use Psr\Http\Message\ServerRequestInterface;

/**
 * A data object hand over to ViewFactoryInterface to create,
 * configure and return a view based on this data.
 *
 * Best practices:
 * * Hand over request if possible.
 * * Use the tuple $templateRootPaths, $partialRootPaths and $layoutRootPaths if possible, using
 *   an array of "base" paths like 'EXT:Resources/Private/(Templates|Partials|Layouts)'
 * * Avoid $templatePathAndFilename
 * * Call render('path/within/templateRootPath') without file-ending on the returned ViewInterface instance.
 */
final readonly class ViewFactoryData
{
    public function __construct(
        public ?array $templateRootPaths = null,
        public ?array $partialRootPaths = null,
        public ?array $layoutRootPaths = null,
        public ?string $templatePathAndFilename = null,
        public ?ServerRequestInterface $request = null,
        public ?string $format = null,
    ) {}
}
