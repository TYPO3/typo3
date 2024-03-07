<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

enum Method: string
{
    case Create = 'POST';
    case Check = 'HEAD';
    case Read = 'GET';
    case Replace = 'PUT';
    case Update = 'PATCH';
    case Delete = 'DELETE';
    case CanIHaz = 'OPTIONS';
    case WAT = 'TRACE';
}
