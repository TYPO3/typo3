<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures;

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
 * Backend fixture for CacheManager test getCacheCreatesBackendWithGivenConfiguration()
 */
class BackendConfigurationOptionFixture extends BackendFixture
{
    /**
     * Test if constructor receives backend options
     *
     * @param string $context FLOW3's application context
     * @param array $options Configuration options - depends on the actual backend
     */
    public function __construct($context, array $options = array())
    {
        $testOptions = [
            'anOption' => 'anOptionValue',
        ];
        if ($options === $testOptions) {
            // expected exception thrown
            throw new \RuntimeException('', 1464555007);
        }
    }
}
