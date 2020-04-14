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

namespace TYPO3\CMS\Core\Tests\Functional\Fixtures\Frontend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Test case for frontend requests without having site handling configured
 */
class PhpError implements PageErrorHandlerInterface
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param int $statusCode
     * @param array $configuration
     */
    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        $this->configuration = $configuration;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     */
    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        $data = [
            'uri' => (string)$request->getUri(),
            'message' => $message,
            'reasons' => $reasons,
        ];
        return new JsonResponse($data, $this->statusCode);
    }
}
