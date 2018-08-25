<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Cli\Fixture\Command;

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
 * Another mock CLI Command
 */
class MockCCommandController extends \TYPO3\CMS\Extbase\Mvc\Cli\Command
{
    /**
     * @cli
     */
    public function cliOnlyCommand()
    {
    }

    public function emptyCommand()
    {
    }

    /**
     * @internal
     */
    public function internalCommand()
    {
    }

    /**
     * @flushesCaches
     */
    public function flushingCachesCommand()
    {
    }

    /**
     * @param string $foo FooParamDescription
     * @param string $bar BarParamDescription
     */
    public function withArgumentsCommand($foo, $bar = 'baz')
    {
    }

    /**
     * Short Description
     *
     * Longer Description
     * Multine
     *
     * Much Multiline
     */
    public function withDescriptionCommand()
    {
    }

    /**
     * @see Foo:Bar:Baz
     */
    public function relatedCommandIdentifiersCommand()
    {
    }
}
