<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Driver\PDOSqlsrv;

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

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 * All private methods have to be checked on every release of doctrine/dbal.
 */
class Driver extends \Doctrine\DBAL\Driver\PDOSqlsrv\Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        [$driverOptions, $connectionOptions] = $this->splitOptions($driverOptions);

        return new Connection(
            $this->_constructPdoDsn($params, $connectionOptions),
            $username,
            $password,
            $driverOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    private function _constructPdoDsn(array $params, array $connectionOptions)
    {
        $dsn = 'sqlsrv:server=';

        if (isset($params['host'])) {
            $dsn .= $params['host'];
        }

        if (isset($params['port']) && ! empty($params['port'])) {
            $dsn .= ',' . $params['port'];
        }

        if (isset($params['dbname'])) {
            $connectionOptions['Database'] = $params['dbname'];
        }

        if (isset($params['MultipleActiveResultSets'])) {
            $connectionOptions['MultipleActiveResultSets'] = $params['MultipleActiveResultSets'] ? 'true' : 'false';
        }

        return $dsn . $this->getConnectionOptionsDsn($connectionOptions);
    }

    /**
     * {@inheritdoc}
     */
    private function splitOptions(array $options): array
    {
        $driverOptions     = [];
        $connectionOptions = [];

        foreach ($options as $optionKey => $optionValue) {
            if (is_int($optionKey)) {
                $driverOptions[$optionKey] = $optionValue;
            } else {
                $connectionOptions[$optionKey] = $optionValue;
            }
        }

        return [$driverOptions, $connectionOptions];
    }

    /**
     * {@inheritdoc}
     */
    private function getConnectionOptionsDsn(array $connectionOptions): string
    {
        $connectionOptionsDsn = '';

        foreach ($connectionOptions as $paramName => $paramValue) {
            $connectionOptionsDsn .= sprintf(';%s=%s', $paramName, $paramValue);
        }

        return $connectionOptionsDsn;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return parent::getName();
    }
}
