.. include:: /Includes.rst.txt

.. _feature-99053-1668163567:

======================================================
Feature: #99053 - Route aspect fallback value handling
======================================================

See :issue:`99053`

Description
===========

Imagine a route like `/news/{news_title}` that has been filled with an
"invalid" value for the `news_title` part. Often these are outdated, deleted
or hidden records. Usually TYPO3 reacts to these "invalid" URL sections at a
very early stage with an HTTP status code `404` (resource not found).

The new property `fallbackValue = [string|null]` can prevent the above scenario
in several ways. By specifying an alternative value, a different record,
language or other detail can be represented. Specifying `null` removes the
corresponding parameter from the route result. In this way, it is up to the
developer to react accordingly.

In the case of Extbase extensions, the developer can define the parameters in
their calling controller action as nullable and deliver corresponding
flash messages that explain the current scenario better than a 404 HTTP
status code.

Examples
--------

..  code-block:: yaml

    routeEnhancers:
      NewsPlugin:
        type: Extbase
        extension: News
        plugin: Pi1
        routes:
          - routePath: '/detail/{news_title}'
            _controller: 'News::detail'
            _arguments:
              news_title: 'news'
        aspects:
          news_title:
            type: PersistedAliasMapper
            tableName: tx_news_domain_model_news
            routeFieldName: path_segment

            # string values lead to parameter `&tx_news_pi1[news]=0`
            fallbackValue: '0'

            # null values lead to parameter `&tx_news_pi1[news]` being removed
            fallbackValue: null

Custom mapper implementations can incorporate this behavior by implementing
the new :php:`\TYPO3\CMS\Core\Routing\Aspect\UnresolvedValueInterface` which
is provided by :php:`\TYPO3\CMS\Core\Routing\Aspect\UnresolvedValueTrait`.

..  code-block:: php

    use TYPO3\CMS\Core\Routing\Aspect\MappableAspectInterface;
    use TYPO3\CMS\Core\Routing\Aspect\UnresolvedValueInterface;
    use TYPO3\CMS\Core\Routing\Aspect\UnresolvedValueTrait;

    class MyCustomEnhancer implements MappableAspectInterface, UnresolvedValueInterface
    {
        use UnresolvedValueTrait;
        // ...
    }


.. index:: Frontend, YAML, ext:core
