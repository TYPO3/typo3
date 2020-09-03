..  include:: /Includes.rst.txt

..  _important-92187-1742812030:

========================================================================
Important: #92187 - Evaluation of incoming HTTP Header X-Forwarded-Proto
========================================================================

See :issue:`92187`

Description
===========

When running TYPO3 behind a reverse proxy, the site owner needs to set two
TYPO3 settings.

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'] = 'first';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = '{ip-of-the-reverse-proxy}';

At this point it is not known if the request between the client (the actual
web browser for example) and the reverse proxy was made via HTTP or HTTPS,
mainly because TYPO3 only evaluated the information from the reverse proxy
to TYPO3 - which was typically faked on the TYPO3's webserver by setting
"HTTPS=on" (e.g. via .htaccess file). In a typical setup, the communication
between the reverse proxy and TYPO3's webserver is done via HTTP and irrelevant
for TYPO3.

When the site owner knows that the reverse proxy acts as a SSL termination point
and only communicates via https to the client, the
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxySSL']` option
can be set, to identify all reverse proxy IPs that ensure a secure connection
between client and reverse proxy.

In case, it is not known, and `reverseProxySSL` is not in use, but
`reverseProxyIP` is in use, the incoming HTTP header `X-Forwarded-Proto` is
now evaluated to determine if the request was made, if the header is sent.

If it is NOT sent, TYPO3 will assume to detect a secure connection between
SSL information as before via various other HTTP Headers or server configuration
settintgs.

..  index:: LocalConfiguration, ext:core
