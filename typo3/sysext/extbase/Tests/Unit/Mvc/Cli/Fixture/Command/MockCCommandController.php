<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Cli\Fixture\Command;

/**
 * Another mock CLI Command
 */
class MockCCommandController extends \TYPO3\CMS\Extbase\Mvc\Cli\Command
{
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
     * @cli
     */
    public function cliOnlyCommand()
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
