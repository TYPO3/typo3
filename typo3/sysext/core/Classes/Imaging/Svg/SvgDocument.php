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

namespace TYPO3\CMS\Core\Imaging\Svg;

/**
 * A parsed SVG document.
 *
 * This is intentionally an otherwise empty subclass of {@see \DOMDocument}.
 * It exists only to give SVG handling its own dedicated, type-hintable type
 * while inheriting the full DOM API for traversal and serialization.
 *
 * Obtain instances via {@see SvgDocumentFactory}. Resolve dimensions,
 * serialize and crop them via {@see SvgDocumentService}.
 *
 * @internal not part of TYPO3 Core API.
 */
final class SvgDocument extends \DOMDocument {}
