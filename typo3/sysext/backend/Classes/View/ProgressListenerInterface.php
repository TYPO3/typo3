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

namespace TYPO3\CMS\Backend\View;

use Psr\Log\LogLevel;

/**
 * An interface that tracks the progress of a longer-running progress.
 * This acts as a wrapper for Symfony ProgressBar on CLI, so see the info here.
 *
 * The listener can be used multiple times when calling start() again a new progressbar starts.
 *
 * @internal this interface is still experimental, and not considered part of TYPO3 Public API.
 */
interface ProgressListenerInterface
{

    /**
     * Start a progress by using the maximum items, and an additional header message.
     *
     * @param int $maxSteps set the maximum amount of items to be processed
     * @param string|null $additionalMessage a separate text message
     */
    public function start(int $maxSteps = 0, string $additionalMessage = null): void;

    /**
     * Move the progress one step further
     * @param int $step by default, this is "1" but can be used to skip further.
     * @param string|null $additionalMessage a separate text message
     */
    public function advance(int $step = 1, string $additionalMessage = null): void;

    /**
     * Stop the progress, automatically setting it to 100%.
     *
     * @param string|null $additionalMessage a separate text message
     */
    public function finish(string $additionalMessage = null): void;

    /**
     * Can be used to render custom messages during the progress.
     *
     * @param string $message the message to render
     * @param string $logLevel used as severity
     */
    public function log(string $message, string $logLevel = LogLevel::INFO): void;
}
