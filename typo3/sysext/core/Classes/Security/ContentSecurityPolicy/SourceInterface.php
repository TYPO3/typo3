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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

/**
 * Semantic interface for anything that can be used as "source" in the terms
 * of Content-Security-Policy - it includes `enum` objects, as well as real
 * object instances that would be using `SourceValueInterface` instead.
 *
 * @internal This implementation might still change
 */
interface SourceInterface {}
