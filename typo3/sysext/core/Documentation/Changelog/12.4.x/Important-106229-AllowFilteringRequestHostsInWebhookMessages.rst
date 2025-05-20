..  include:: /Includes.rst.txt

..  _important-106229-1747304339:

======================================================================
Important: #106229 - Allow filtering request hosts in webhook messages
======================================================================

See :issue:`106229`

Description
===========

To protect against DNS rebinding, the list of allowed hostnames that webhook
handlers will connect to can be configured as a list in
:php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['webhooks']`.

To add a host to the allowlist, it can be appended to the mentioned array.

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['webhooks'][] = 'example.com';


You can substitute parts of the domain with a wildcard character :php:`'*'`
(matches one or multiple characters, no regex syntax supported).
For example, :php:`'*.example.com'` is valid, and accepts all domains ending in
`.example.com`, also `foo.bar.example.com`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['webhooks'][] = '*.example.com';

By default – when the `webhooks` key in `allowed_hosts` is unset or null – all
hosts are allowed.

An empty array will cause all webhooks requests to be blocked:

..  code-block:: php

    // Block all webhook targets by specifying an empty array.
    // You might better want to remove ext:webhooks if you want to do this.
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['webhooks'] = [];

..  index:: LocalConfiguration, ext:webhooks
