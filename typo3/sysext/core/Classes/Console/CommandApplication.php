<?php
namespace TYPO3\CMS\Core\Console;

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
use Symfony\Component\Console\Input\ArgvInput;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\ApplicationInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Entry point for the TYPO3 Command Line for Commands
 * Does not run the RequestHandler as this already runs an Application inside an Application which
 * is just way too much logic around simple CLI calls
 */
class CommandApplication implements ApplicationInterface
{
    /**
     */
    public function __construct()
    {
        $this->checkEnvironmentOrDie();
    }

    /**
     * Run the Symfony Console application in this TYPO3 application
     *
     * @param callable $execute
     */
    public function run(callable $execute = null)
    {
        $this->initializeContext();
        $handler = GeneralUtility::makeInstance(CommandRequestHandler::class);
        $handler->handleRequest(new ArgvInput());

        if ($execute !== null) {
            call_user_func($execute);
        }
    }

    /**
     * Check the script is called from a cli environment.
     */
    protected function checkEnvironmentOrDie()
    {
        if (PHP_SAPI !== 'cli') {
            die('Not called from a command line interface (e.g. a shell or scheduler).' . LF);
        }
    }

    /**
     * Initializes the Context used for accessing data and finding out the current state of the application
     * Will be moved to a DI-like concept once introduced, for now, this is a singleton
     */
    protected function initializeContext()
    {
        GeneralUtility::makeInstance(Context::class, [
            'date' => new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['EXEC_TIME'])),
            'visibility' => new VisibilityAspect(true, true),
            'workspace' => new WorkspaceAspect(0),
            'backend.user' => new UserAspect(null),
        ]);
    }
}
