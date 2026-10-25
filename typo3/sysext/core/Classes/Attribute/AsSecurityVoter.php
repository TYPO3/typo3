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

namespace TYPO3\CMS\Core\Attribute;

/**
 * Service tag to register a voter of the authorization checker. The class
 * must implement Symfony's VoterInterface, usually by extending its Voter.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsSecurityVoter
{
    public const string TAG_NAME = 'security.voter';

    public function __construct(
        /**
         * Voters with a higher priority are asked first. The voters shipped
         * with TYPO3 Core use the default priority of 0.
         */
        public int $priority = 0,
    ) {}
}
