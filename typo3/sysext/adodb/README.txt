This folder contains the ADOdb library used by DBAL to connect to the
various DBMS we are supporting with TYPO3.

Latest version may be downloaded: on http://adodb.sourceforge.net/

BEWARE: At least in version 5.19 and below some methods are not properly
extending their parent's method signature and cause PHP warnings.
Please apply patch from http://forge.typo3.org/issues/48034 if needed.

The charset is not set properly for every driver, so another patch
is required: https://forge.typo3.org/issues/61738
There is also a pull request for this issue so this might be fixed in
the next release: https://github.com/ADOdb/ADOdb/pull/39