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

/**
 * Exception that has to be propagated back to the middleware stack
 * in order to stop current execution and respond with the given
 * response. This exception is used as alternative to previous
 * die() or exit() calls.
 *
 * Note this exception should be used in cases where a controller wants
 * to create an 'early' response. An example are failed access checks:
 * A controller wants to throw an early 'denied' response without further
 * local processing.
 *
 * When this exception is thrown by a controller, it will be caught by the
 * 'very inner' ResponsePropagation middleware. The response is then returned
 * to other 'outer' middlewares, allowing them to further operate on the
 * response (eg. adding a content-length header) and to do other jobs
 * (eg. releasing created locks).
 *
 * If this exception is thrown within a middleware (as opposed to be thrown
 * from within controllers), it will bypass other middlewares and will be
 * caught just like the parent ImmediateResponseException. This should be
 * used with care, there should be little reason to do so at all.
 *
 * In general, from within controllers, this response exception
 * should be preferred over ImmediateResponseException.
 *
 * @internal
 */
class PropagateResponseException extends ImmediateResponseException
{
}
