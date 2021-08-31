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

namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/**
 * @internal May change / vanish any time. This interface *may* be used to override implementation via
 *           ServiceProvider.php in an own extension. However, ServiceProvider.php itself is *not* API,
 *           so this interface isn't as well. Core *may* break this later again, but will still try not to,
 *           but this is not guaranteed.
 */
interface ViewHelperResolverFactoryInterface
{
    public function create(): ViewHelperResolver;
}
