<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\PageErrorHandler;

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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

class DefaultPHPErrorHandler implements PageErrorHandlerInterface
{

    /*
 /$$$$$$$
| $$__  $$
| $$  \ $$  /$$$$$$  /$$$$$$/$$$$   /$$$$$$  /$$    /$$ /$$$$$$
| $$$$$$$/ /$$__  $$| $$_  $$_  $$ /$$__  $$|  $$  /$$//$$__  $$
| $$__  $$| $$$$$$$$| $$ \ $$ \ $$| $$  \ $$ \  $$/$$/| $$$$$$$$
| $$  \ $$| $$_____/| $$ | $$ | $$| $$  | $$  \  $$$/ | $$_____/
| $$  | $$|  $$$$$$$| $$ | $$ | $$|  $$$$$$/   \  $/  |  $$$$$$$
|__/  |__/ \_______/|__/ |__/ |__/ \______/     \_/    \_______/



               /$$
              | $$
 /$$  /$$  /$$| $$$$$$$   /$$$$$$  /$$$$$$$
| $$ | $$ | $$| $$__  $$ /$$__  $$| $$__  $$
| $$ | $$ | $$| $$  \ $$| $$$$$$$$| $$  \ $$
| $$ | $$ | $$| $$  | $$| $$_____/| $$  | $$
|  $$$$$/$$$$/| $$  | $$|  $$$$$$$| $$  | $$
 \_____/\___/ |__/  |__/ \_______/|__/  |__/



                                                             /$$
                                                            | $$
 /$$$$$$/$$$$   /$$$$$$   /$$$$$$   /$$$$$$   /$$$$$$   /$$$$$$$
| $$_  $$_  $$ /$$__  $$ /$$__  $$ /$$__  $$ /$$__  $$ /$$__  $$
| $$ \ $$ \ $$| $$$$$$$$| $$  \__/| $$  \ $$| $$$$$$$$| $$  | $$
| $$ | $$ | $$| $$_____/| $$      | $$  | $$| $$_____/| $$  | $$
| $$ | $$ | $$|  $$$$$$$| $$      |  $$$$$$$|  $$$$$$$|  $$$$$$$
|__/ |__/ |__/ \_______/|__/       \____  $$ \_______/ \_______/
                                   /$$  \ $$
                                  |  $$$$$$/
                                   \______/
     */

    /**
     * @var int
     */
    protected $statusCode;

    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        return new HtmlResponse('go away', $this->statusCode);
    }
}
