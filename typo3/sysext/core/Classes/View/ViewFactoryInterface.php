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

/**
 * Generic TYPO3 view factory - the V in MVC.
 *
 * This interface is used in TYPO3 and should be used via dependency
 * injection to create a view instance in custom TYPO3 extensions
 * whenever a view should be rendered.
 */
interface ViewFactoryInterface
{
    public function create(ViewFactoryData $data): ViewInterface;
}
