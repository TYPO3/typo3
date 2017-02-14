<?php
namespace TYPO3\CMS\Install\Controller\Action;

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
 * General action interface
 */
interface ActionInterface
{
    /**
     * Handle this action
     *
     * @return string Rendered content
     */
    public function handle();

    /**
     * Set form protection token
     *
     * @param string $token Form protection token
     */
    public function setToken($token);

    /**
     * Set controller, Either string 'step', 'tool' or 'common'
     *
     * @param string $controller Controller name
     */
    public function setController($controller);

    /**
     * Set action name. This is usually similar to the class name,
     * only for loginForm, the action is login
     *
     * @param string $action Name of target action for forms
     */
    public function setAction($action);

    /**
     * Set POST values
     *
     * @param array $postValues List of values submitted via POST
     */
    public function setPostValues(array $postValues);

    /**
     * Set the last error array as returned by error_get_last()
     *
     * @param array $lastError
     */
    public function setLastError(array $lastError);

    /**
     * Status messages from controller
     *
     * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $messages
     */
    public function setMessages(array $messages = []);
}
