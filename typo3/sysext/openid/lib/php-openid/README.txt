This directory contains a modified version of the PHP OpenID library
(http://www.openidenabled.com/). We use only "Auth" directory from the library
and include also a copy of COPYING file to conform to the license requirements.

Current version of the library is 2.1.2.

The following modifications are made:
- added cURL proxy settings from TYPO3 to the Auth/Yadis/ParanoidHTTPFetcher.php

See also the patch to the library. If the library is updated, the patch should
be applied to a new version.