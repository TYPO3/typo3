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

namespace TYPO3\CMS\Core\Exception\Page;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * This exception is thrown in {@see PageRepository::resolveReferencedPageRecord()}
 * in case that circular page reference chain has been detected and would end up in
 * endless loop resolving chain. This is impossible to resolve and communicated with
 * this exception.
 */
class CircularPageReferenceChainException extends PageNotFoundException {}
