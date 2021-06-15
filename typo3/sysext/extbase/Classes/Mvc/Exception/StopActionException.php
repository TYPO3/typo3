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

namespace TYPO3\CMS\Extbase\Mvc\Exception;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Extbase\Mvc\Exception;

/**
 * This exception is thrown by a controller to stop the execution of the current
 * action and return the control to the dispatcher. The dispatcher catches this
 * exception and - depending on the "dispatched" status of the request - either
 * continues dispatching the request or returns control to the request handler.
 *
 * See the Action Controller's forward() and redirectToUri() methods for more information.
 *
 * @deprecated since v11, will be removed in v12. This action shouldn't be thrown anymore:
 * Actions that extbase-internally forward to another action should RETURN Extbase\Http\ForwardResponse
 * instead. Actions that initiate a client redirect should RETURN a Core\Http\RedirectResponse instead.
 */
class StopActionException extends Exception
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct($message = '', $code = 0, \Throwable $previous = null, ResponseInterface $response = null)
    {
        // @deprecated since v11, will be removed in v12. Can not trigger_error() here since
        // extbase ActionController still has to use this exception for b/w compatibility.
        // See the usages of this exception when dropping in v12.
        $this->response = $response ?? new Response();
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
