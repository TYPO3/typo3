<?php
namespace TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures;

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

/**
 * Fixture configured data provider
 */
class TreeDataProviderWithConfigurationFixture
{
    /**
     * @param array $configuration
     * @throws \Exception
     */
    public function __construct($configuration)
    {
        if (!is_array($configuration)) {
            throw new \Exception('Failed asserting that the constructor arguments are an array', 1438875247);
        }
        $tcaConfiguration = [
            'treeConfig' => [
                'dataProvider' => self::class,
            ],
            'internal_type' => 'foo',
        ];
        if ($configuration !== $tcaConfiguration) {
            throw new \Exception('Failed asserting that the constructor arguments are correctly passed', 1438875248);
        }
        throw new  \RuntimeException('This must be thrown', 1438875249);
    }
}
