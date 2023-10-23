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

namespace TYPO3\CMS\Core\Messenger;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @internal not part of TYPO3 Core API
 */
final class DoctrineTransportFactory
{
    public function __construct(private SerializerInterface $serializer) {}

    public function createTransport(array $options = []): DoctrineTransport
    {
        $options['table_name'] ??= 'sys_messenger_messages';
        if ($options['table_name'] === 'sys_messenger_messages') {
            $options['auto_setup'] = false;
        }

        // use native doctrine dbal connection instead of TYPO3s overwritten one
        // as the overwritten querybuilder is not fully compatible with symfony messenger
        $connection = DriverManager::getConnection($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']);
        $doctrineTransportConnection = new Connection($options, $connection);
        return new DoctrineTransport($doctrineTransportConnection, $this->serializer);
    }
}
