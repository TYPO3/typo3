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

namespace TYPO3\CMS\Install;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal This enum is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
enum WebserverType: string
{
    case Apache = 'apache';
    case MicrosoftInternetInformationServer = 'iis';
    case Other = 'other';

    public static function fromRequest(ServerRequestInterface $request): self
    {
        return self::fromType((string)($request->getServerParams()['SERVER_SOFTWARE'] ?? ''));
    }

    public static function fromType(string $type): self
    {
        if ($type === 'apache' || str_starts_with($type, 'Apache')) {
            return self::Apache;
        }
        if ($type === 'iis' || str_starts_with($type, 'Microsoft-IIS')) {
            return self::MicrosoftInternetInformationServer;
        }

        return self::Other;
    }

    /**
     * @return array<string, non-empty-string>
     */
    public static function getDescriptions(): array
    {
        return [
            self::Apache->value => 'Apache',
            self::MicrosoftInternetInformationServer->value => 'Microsoft IIS',
            self::Other->value => 'Other (use for anything else)',
        ];
    }

    public function isApacheServer(): bool
    {
        return $this === self::Apache;
    }

    public function isMicrosoftInternetInformationServer(): bool
    {
        return $this === self::MicrosoftInternetInformationServer;
    }
}
