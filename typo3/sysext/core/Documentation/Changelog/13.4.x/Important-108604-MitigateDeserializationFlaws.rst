..  include:: /Includes.rst.txt

..  _important-108604-1780297491:

===================================================
Important: #108604 - Mitigate deserialization flaws
===================================================

See :issue:`108604`

Description
===========

TYPO3 introduces new serialization infrastructure to protect against PHP object
injection attacks. Two complementary strategies are applied depending on how
long the serialized data lives:

**Cache frontend (**:php:`VariableFrontend`**) — HMAC-authenticated serialization**

:php:`\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend` (the default cache
frontend) now uses
:php:`\TYPO3\CMS\Core\Serializer\AuthenticatedMessageDeserializer` for both
writing and reading cache entries. On every :php:`set()` call the payload is
serialized and an HMAC is appended; on every :php:`get()` call the HMAC is
validated before deserialization proceeds.

Because caches are temporary and written exclusively by the server that reads
them, this approach provides a strong integrity guarantee: an attacker cannot
craft a malicious serialized payload that the server would accept, since they
cannot forge the HMAC without knowing the encryption key.

Cache entries written by an older TYPO3 version (without an HMAC) are handled
gracefully: if the payload contains no PHP class tokens it is deserialized
safely with :php:`allowed_classes: false`; if it does contain class tokens it
is discarded and treated as a cache miss, causing the entry to be regenerated
transparently.

**Registry (**:php:`\TYPO3\CMS\Core\Registry`**) — gadget denylist**

:php:`\TYPO3\CMS\Core\Registry` (the persistent key-value store backed by the
:sql:`sys_registry` table) deserializes stored payloads through
:php:`\TYPO3\CMS\Core\Serializer\DenyListDeserializer`. Before deserialization
the class names embedded in the payload are checked against a gadget deny list.
The deny/allow decision for each class is resolved lazily via
:php:`\ReflectionClass` on first encounter and then cached in :php:`cache:core`
(HMAC-signed for integrity) so that reflection is not repeated for the same
class within a cache lifetime. A class is considered a gadget when it carries a
user-defined :php:`__destruct()` or an exploitable :php:`__wakeup()` — that is,
a :php:`__wakeup()` not provided solely by
:php:`\TYPO3\CMS\Core\Security\BlockSerializationTrait`. If any gadget class is
referenced in a payload, a
:php:`\TYPO3\CMS\Core\Serializer\Exception\DeserializerException` is thrown and
deserialization is aborted.

A denylist strategy (block known-bad, allow unknown) is used intentionally for
the registry to avoid breaking changes to long-lived persisted data.

Impact
------

**Cache (**:php:`VariableFrontend`**)**

Existing cache entries that contain serialized PHP objects will be treated as
cache misses on the next read. No exception is thrown; the entry is simply
discarded and the cache is repopulated on the next request. This is transparent
to callers.

**Registry**

Extensions or third-party code that stores serialized PHP objects in
:php:`Registry` may encounter a :php:`DeserializerException` at read time if
the serialized object graph contains a class with a user-defined
:php:`__destruct()` or :php:`__wakeup()` method.

Migration & Insights
--------------------

In most cases no migration is required. The sections below aim to provide
some insights into internal details and general suggestions.

**Cache (**:php:`VariableFrontend`**)**

No migration is required. Stale or legacy cache entries are automatically
treated as misses and regenerated. If code stores object graphs in a
:php:`VariableFrontend`-backed cache it will continue to work as long as the
server reads the entry it wrote (same encryption key).

**Registry — preferred approach: avoid object serialization**

Review what is stored in the registry. Plain PHP arrays and scalar values are
not affected by this protection and should be preferred over serialized object
graphs wherever possible.

**Registry — alternative: restructure the stored value**

If an object must be stored, ensure that neither the object itself nor any
object reachable from it through public or serialized properties carries a
user-defined :php:`__destruct()` or :php:`__wakeup()` method.

**Registry — last resort: explicit class allowlist**

If neither of the above is feasible in the short term, the affected class can be
added to the site-level allowlist in :file:`config/system/additional.php` (or
the legacy :file:`typo3conf/AdditionalConfiguration.php`):

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['deserialization']['allowedClassNames'][] =
        \Vendor\MyExtension\Domain\Model\MyObject::class;

..  warning::

    The allowlist setting bypasses the deserialization gadget protection for
    the listed classes. It should only be used as a last resort after carefully
    reviewing the class and confirming that its :php:`__destruct()` or
    :php:`__wakeup()` implementation cannot be abused in a PHP object injection
    attack chain. Remove the entry as soon as the underlying serialization is
    refactored.

..  index:: PHP-API, LocalConfiguration, ext:core
