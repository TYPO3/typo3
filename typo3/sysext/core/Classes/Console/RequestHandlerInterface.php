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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The interface for a request handler for a console-based application
 *
 * @api
 */
interface RequestHandlerInterface
{
    /**
     * Handles a CLI request
     *
     * @param InputInterface $input
     * @return NULL|OutputInterface
     * @api
     */
    public function handleRequest(InputInterface $input);

    /**
     * Checks if the request handler can handle the given request.
     *
     * @param InputInterface $input
     * @return bool TRUE if it can handle the request, otherwise FALSE
     * @api
     */
    public function canHandleRequest(InputInterface $input);

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request. An integer > 0 means "I want to handle this request" where
     * "100" is default. "0" means "I am a fallback solution".
     *
     * @return int The priority of the request handler
     * @api
     */
    public function getPriority();
}
