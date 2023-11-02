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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Use "default" value if set in config
 *
 * @internal
 */
final class ConfigDefault extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * Match if ['config']['default'] is set.
     *
     * @param array $data
     * @return bool
     */
    public function match(array $data): bool
    {
        return (isset($data['fieldConfig']['config']['default'])) ? true : false;
    }

    /**
     * Returns the value of ['config']['default']
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        return (string)$data['fieldConfig']['config']['default'];
    }
}
