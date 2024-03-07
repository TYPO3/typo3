<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Domain;

use TYPO3\CMS\Core\Http\Uri;

class ResourceUri extends Uri
{
    protected array $supportedSchemes = [
        't3' => 0
    ];

}
