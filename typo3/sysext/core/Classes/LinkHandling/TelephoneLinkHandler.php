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

namespace TYPO3\CMS\Core\LinkHandling;

/**
 * Resolves telephone numbers
 */
class TelephoneLinkHandler implements LinkHandlingInterface
{

    /**
     * Returns the link to a telephone number as a string
     *
     * @param array $parameters
     * @return string
     */
    public function asString(array $parameters): string
    {
        $telephoneNumber = preg_replace('/(?:[^\d\+]+)/', '', $parameters['telephone']);

        return 'tel:' . $telephoneNumber;
    }

    /**
     * Returns the telephone number without the "tel:" prefix
     * in the 'telephone' property of the array.
     *
     * @param array $data
     * @return array
     */
    public function resolveHandlerData(array $data): array
    {
        return ['telephone' => substr($data['telephone'], 4)];
    }
}
