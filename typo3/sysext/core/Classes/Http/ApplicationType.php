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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;

/**
 * Helper class to answer "Is this a frontend or backend request?".
 *
 * It requires a PSR-7 ServerRequestInterface request object. Typical usage:
 *
 *     ApplicationType::fromRequest($request)->isFrontend()
 *
 * Note the final request object is given to your controller by the frontend and
 * backend RequestHandler's. This request should be used in code calling this class.
 * However, various library parts of the TYPO3 core do not receive the request
 * object directly, so extensions may not receive it either. To work around
 * this technical debt for now, the RequestHandler's set the final request object
 * as $GLOBALS['TYPO3_REQUEST'], which can be used in those cases to feed this class.
 * Also note that CLI calls often do NOT create a request object, depending on their task.
 *
 * Classes that may be called from CLI without request object thus use this helper like:
 *
 *     // Do something special if this is a frontend request.
 *     if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
 *         && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
 *     ) {
 *
 * Important: $GLOBALS['TYPO3_REQUEST'] is NOT available before the RequestHandler has been
 * called. This especially means the question "Is this a frontend or backend request?"
 * can NOT be answered in the TYPO3 bootstrap related extension files ext_localconf.php,
 * ext_tables.php and Configuration/TCA/* files.
 */
final class ApplicationType
{
    private int $type = 0;

    /**
     * Create an ApplicationType object from a given PSR-7 request.
     *
     * @param ServerRequestInterface $request
     * @return static
     * @throws RuntimeException
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $type = $request->getAttribute('applicationType');
        if (!is_int($type)) {
            // Request object has no valid type. Type is set by the Frontend / Backend / Install
            // application. If it's missing, we've not been called behind a legit TYPO3 application object.
            // This is bogus, we throw a generic RuntimeException that should not be caught.
            throw new RuntimeException('No valid attribute "applicationType" found in request object.', 1606222812);
        }
        return new self($type);
    }

    private function __construct(int $type)
    {
        $this->type = $type;
    }

    public function isFrontend(): bool
    {
        return (bool)($this->type & SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    public function isBackend(): bool
    {
        return (bool)($this->type & SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }
}
