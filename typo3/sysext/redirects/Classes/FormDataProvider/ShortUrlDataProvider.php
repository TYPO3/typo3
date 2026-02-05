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

namespace TYPO3\CMS\Redirects\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Redirects\Repository\Demand;

/**
 * Set field config for sys_redirect of type "short_url"
 * @todo Replace this by a generic column config, like "readonlyOnPersist"!
 * @internal
 */
class ShortUrlDataProvider implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $record = $result['databaseRow'];

        // Set short_url to readyOnly when editing a Short URL
        if (($record['redirect_type'] ?? '') === Demand::SHORT_URL_REDIRECT_TYPE && $result['command'] === 'edit') {
            $result['processedTca']['columns']['short_url']['config']['readOnly'] = true;
        }

        return $result;
    }
}
