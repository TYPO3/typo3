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
 * This exception is thrown in {@see PageRepository::resolveReferencedPageRecord()} in case
 * the iteration limit to resolve page references has been reached and is aborted to avoid
 * consuming too much time and resources.
 *
 * This differentiates from {@see CircularPageReferenceChainException}, which explicitly
 * states that a page already exists in the resolved pages chain, where this exception
 * prevents this earlier than knowing if it is a circular chain or only a too deep chain.
 */
class PageReferenceResolvingReachedIterationLimitException extends PageNotFoundException {}
