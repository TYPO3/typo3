
.. include:: ../../Includes.txt

=======================================================
Breaking: #52705 - Default log configuration is changed
=======================================================

See :issue:`52705`

Description
===========

FileWriter behavior has changed
-------------------------------

The FileWriter of the logging Framework now appends a hash to its default log file which is used when no log file name
is provided in the configuration.

The new default log file might now look like this (the hash depends on the current encryption key):

::

	typo3temp/logs/typo3_7ac500bce5.log


Default configuration has changed
---------------------------------

For security reasons we want the default log file of TYPO3 to contain a random hash to make guessing the file name harder.

Therefore the :code:`logFile` configuration is removed for the default :code:`FileWriter` configuration.

Additionally the "deprecated" :code:`FileWriter` configuration is removed because it is not used by the core.



Impact
======

If the log file configuration is **not** overwritten the TYPO3 default log file will change from
:code:`typo3temp/logs/typo3.log` to :code:`typo3temp/logs/typo3_<hash>.log`.

Installations with Extensions making use of the changed / removed log configurations might break.


Affected Installations
======================

All instances that expect the default log file to be :code:`typo3temp/logs/typo3.log` for some reason.

All instances that expect the :code:`logFile` configuration to be present in the :code:`writerConfiguration` for some reason.

All instances that use the "deprecated" log configuration in their Extensions.

All instances that use Extensions which extend the FileWriter and access the :code:`$defaultLogFile` class property
which is replaced by :code:`$defaultLogFileTemplate` and the :code:`getDefaultLogFileName()` method.


Migration
=========

Adjust the log configuration according to your needs in your :code:`LocalConfiguration.php`.

Adjust any Extension code affected by the changes if needed.


.. index:: PHP-API, LocalConfiguration
