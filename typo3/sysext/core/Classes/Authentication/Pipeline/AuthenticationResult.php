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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

/**
 * Union type of the possible outcomes of an authentication attempt:
 *
 * - Authenticated: a Principal was established
 * - Challenge: an interactive step is required, carrying the PSR-7 response
 *   that initiates it (redirect to an identity provider, MFA view, 401)
 * - Failure: authentication was attempted and explicitly failed
 * - NotApplicable: the authenticator is not responsible for this request,
 *   including a request that carries no credentials for it. The caller of
 *   the realm decides how to answer such a request, e.g. with a 401 that
 *   names the expected authentication scheme
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
interface AuthenticationResult {}
