.. include:: /Includes.rst.txt

.. _important-102402-1700410900:

============================================================
Important: #102402 - Extended Doctrine DBAL Platform classes
============================================================

See :issue:`102402`

Description
===========

The Core needs to adapt some internal classes to prepare towards `doctrine/dbal`
major version 4.x. The Doctrine team deprecated especially the Doctrine
event manager, the Core used to populate custom adaptions.

The proposed way to mitigate the old events is to extend classes and integrate
custom handling code directly. TYPO3 thus extends a couple of classes and replaces
them using a factory.

Affected code is marked :php:`@internal`. Extension author must not rely on the
TYPO3 class names for :php:`instanceof` checks and should check using the original
Doctrine classes instead.

For example, `doctrine/dbal` has the following inheritance chain:

..  code-block:: php

    <?php

    class MySQL80Platform extends MySQL57Platform {}
    class MySQL57Platform extends MySQLPlatform {}
    class MySQLPlatform extends AbstractMySQLPlatform {}
    class AbstractMySQLPlatform extends AbstractPlatform {}

TYPO3 now extends the concrete platform classes:

*   :php:`\TYPO3\CMS\Core\Database\Platform\MySQL80Platform` extends :php:`\Doctrine\DBAL\Platforms\MySQL80Platform`
*   :php:`\TYPO3\CMS\Core\Database\Platform\MySQL57Platform` extends :php:`\Doctrine\DBAL\Platforms\MySQL57Platform`
*   :php:`\TYPO3\CMS\Core\Database\Platform\MySQLPlatform` extends :php:`\Doctrine\DBAL\Platforms\MySQLPlatform`

The TYPO3 Core classes are only used as top layer, for example:

#.  :php:`\TYPO3\CMS\Core\Database\Platform\MySQL80Platform` extends :php:`\Doctrine\DBAL\Platforms\MySQL80Platform`
#.  :php:`\Doctrine\DBAL\Platforms\MySQL80Platform` extends :php:`\Doctrine\DBAL\Platforms\MySQL57Platform`
#.  :php:`\Doctrine\DBAL\Platforms\MySQL57Platform` extends :php:`\Doctrine\DBAL\Platforms\MySQLPlatform`
#.  :php:`\Doctrine\DBAL\Platforms\MySQLPlatform` extends :php:`\Doctrine\DBAL\Platforms\AbstractMySQLPlatform`
#.  :php:`\Doctrine\DBAL\Platforms\AbstractMySQLPlatform` extends :php:`\Doctrine\DBAL\Platforms\AbstractPlatform`

Custom extension code that needs to implement :php:`instanceof` checks for specific platforms
should use the Doctrine classes and not the TYPO3 Core classes, for example:

..  code-block:: php

    <?php

    use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
    use TYPO3\CMS\Core\Database\Platform\MySQL80Platform as Typo3MySQL80Platform;

    // Usually incoming from elsewhere, eg. DI.
    $platform = new Typo3MySQL80Platform();

    $check = $platform instanceof DoctrineMySQLPlatform();


.. index:: Database, PHP-API, ext:core
