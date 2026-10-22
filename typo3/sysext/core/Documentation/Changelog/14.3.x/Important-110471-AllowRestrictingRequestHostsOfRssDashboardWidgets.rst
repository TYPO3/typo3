..  include:: /Includes.rst.txt

..  _important-110471-1792652849:

=============================================================================
Important: #110471 - Allow restricting request hosts of RSS dashboard widgets
=============================================================================

See :issue:`110471`

Description
===========

Feeds of RSS dashboard widgets are fetched by http(s) only, other sources, such
as local files, are not read anymore. This applies to feed urls configured in
the settings of a widget as well as to the :yaml:`feedUrl` option of the widget
registration. The :yaml:`feedUrl` option cannot be overridden by the settings
of a widget anymore.

By default, RSS dashboard widgets may still request any host. Backend users
who are allowed to configure an RSS widget can therefore let the server send
requests to arbitrary hosts, including hosts of internal networks (Server-Side
Request Forgery). This is only prevented once the hosts RSS dashboard widgets
connect to are restricted in
:php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss']`.

It is strongly recommended to configure this option and to only allow the
hosts of the feeds actually used, for example in
:file:`config/system/additional.php`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = [
        // Hosts of the TYPO3 news feed
        'typo3.org',
        'news.typo3.com',
    ];

The restriction applies to all feed requests, including redirect targets and
feed urls of the widget registration. Feeds of hosts that are not allowed are
not loaded, and the widget shows a corresponding notice.

Parts of the domain can be substituted with a wildcard character :php:`'*'`
(matches one or multiple characters, no regex syntax supported). For example,
:php:`'*.example.com'` accepts all domains ending in `.example.com`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'][] = '*.example.com';

As long as the option is unset or :php:`null`, all hosts are allowed. An empty
array blocks all feed requests.

..  index:: LocalConfiguration, ext:dashboard
