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

namespace TYPO3\CMS\Frontend\Page;

use Psr\Http\Message\ResponseInterface;

/**
 * Exception created in PageInformationFactory that contains an "early" Response
 * when further calculation is stopped and the final PageInformation object can
 * not be created. It is typically caught and handled in the Frontend
 * middleware that triggers PageInformation creation.
 *
 * @internal
 */
final class PageInformationCreationFailedException extends \Exception
{
    public function __construct(
        private readonly ResponseInterface $response,
        int $code
    ) {
        $this->code = $code;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
