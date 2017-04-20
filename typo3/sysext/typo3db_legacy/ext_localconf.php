<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Typo3DbLegacy\Updates\DbalAndAdodbExtractionUpdate::class]
        = \TYPO3\CMS\Typo3DbLegacy\Updates\DbalAndAdodbExtractionUpdate::class;

    // Initialize database connection in $GLOBALS and connect
    $databaseConnection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection::class);
    $databaseConnection->setDatabaseName(
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] ?? ''
    );
    $databaseConnection->setDatabaseUsername(
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] ?? ''
    );
    $databaseConnection->setDatabasePassword(
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] ?? ''
    );

    $databaseHost = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] ?? '';
    if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'])) {
        $databaseConnection->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port']);
    } elseif (strpos($databaseHost, ':') > 0) {
        // @TODO: Find a way to handle this case in the install tool and drop this
        list($databaseHost, $databasePort) = explode(':', $databaseHost);
        $databaseConnection->setDatabasePort($databasePort);
    }
    if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket'])) {
        $databaseConnection->setDatabaseSocket(
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['unix_socket']
        );
    }
    $databaseConnection->setDatabaseHost($databaseHost);

    $databaseConnection->debugOutput = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'];

    if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['persistentConnection'])
        && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['persistentConnection']
    ) {
        $databaseConnection->setPersistentDatabaseConnection(true);
    }

    $isDatabaseHostLocalHost = in_array($databaseHost, ['localhost', '127.0.0.1', '::1'], true);
    if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverOptions'])
        && $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverOptions'] & MYSQLI_CLIENT_COMPRESS
        && !$isDatabaseHostLocalHost
    ) {
        $databaseConnection->setConnectionCompression(true);
    }

    if (!empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'])) {
        $commandsAfterConnect = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
            LF,
            str_replace(
                '\' . LF . \'',
                LF,
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands']
            ),
            true
        );
        $databaseConnection->setInitializeCommandsAfterConnect($commandsAfterConnect);
    }

    $GLOBALS['TYPO3_DB'] = $databaseConnection;
    $GLOBALS['TYPO3_DB']->initialize();
});
