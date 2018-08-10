<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Http;

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

use Psr\Http\Message\ResponseInterface;

/**
 * Exception that has to be handled immediately in order to have
 * stop current execution and provide the current response. This
 * exception is used as alternative to previous die() or exit().
 *
 * @internal
 */
class ImmediateResponseException extends \Exception
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param ResponseInterface $response
     * @param int $code
     */
    public function __construct(ResponseInterface $response, int $code = 0)
    {
        $this->response = $response;
        $this->code = $code;
    }

    /**
     * @return Response
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
