This directory contains a modified version of the PHP OpenID library
(http://www.openidenabled.com/). We use only "Auth" directory from the library
and include also a copy of COPYING file to conform to the license requirements.

Current version of the library is 2.2.2
(git-checkout 2014-10-20; commit fff9217fb1acda132702730b66b10981ea9d4cac)
Source: https://github.com/openid/php-openid

The following modifications are made:
- added cURL proxy settings from TYPO3 to the Auth/Yadis/ParanoidHTTPFetcher.php

See also the patch to the library. If the library is updated, the patch should
be applied to a new version.