..  include:: /Includes.rst.txt

..  _hash-calculation:

================
Hash calculation
================

With every webhook request, the following HTTP headers are sent:

..  code-block:: plaintext
    :caption: HTTP Header of a webhooks

    Content-Type: application/json
    Webhook-Signature-Algo: sha256
    Webhook-Signature: <hash>

The hash is calculated with the secret of the webhook and the JSON encoded data
of the request. The hash is created with the PHP function :php:`hash_hmac()`.

The hash is calculated with the following PHP code:

..  code-block:: php

    $hash = hash_hmac('sha256', sprintf(
        '%s:%s',
        $identifier, // The identifier of the webhook (uuid)
        $body // The JSON encoded body of the request
    ), $secret); // The secret of the webhook

The hash is sent as HTTP header `Webhook-Signature` and should be used to
validate that the request was sent from the TYPO3 instance and has not been
manipulated.
To verify this on the receiving end, build the hash with the same algorithm and
secret and compare it with the hash that was sent with the request.

The hash is not meant to be used as a security mechanism, but as a way to verify
that the request was sent from the TYPO3 instance.

