..  include:: /Includes.rst.txt

..  _feature-91724-1737200000:

================================================================================
Feature: #91724 - Introduce TemplatedEmailFactory for centralized email creation
================================================================================

See :issue:`91724`

Description
===========

A new :php-short:`\TYPO3\CMS\Core\Mail\TemplatedEmailFactory` class has been
introduced to provide a centralized creation of
:php-short:`\TYPO3\CMS\Core\Mail\FluidEmail` instances.

The factory provides three methods for different use cases:

:php:`create()`
    For backend and CLI contexts, such as login notifications, Scheduler
    tasks, and the Install Tool, where only the global configuration
    :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL'][...]` is used.

:php:`createFromRequest()`
    For frontend contexts, such as form submissions and `EXT:felogin`, where
    site-specific email templates should be applied. It merges site settings
    from :yaml:`typo3/email` with the global configuration
    :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL'][...]`.

:php:`createWithOverrides()`
    For extensions that need to provide custom template paths merged on top of
    the base configuration, optionally taking a request context into account.
    Two cases of template resolution are possible, ordered by priority:

    *   Request without site attribute: 1. Provided override arguments ->
        2. global configuration
    *   Request with site attribute: 1. Provided override arguments ->
        2. site settings -> 3. global configuration

    Note that you can also use the numerical priority of template paths so
    that site settings with a higher priority number can override paths in the
    provided arguments with a lower priority number.

Site settings
=============

A new site set, :yaml:`typo3/email`, is available in EXT:core and defines the
settings below. These are applied automatically when a request with a site
attribute is passed to :php:`createFromRequest()` or
:php:`createWithOverrides()`. This means extensions running in a frontend
context, such as EXT:form email finishers, benefit from site-specific email
configuration:

:yaml:`email.format`
    The email format to use (`html`, `plain`, `both`). If empty, the global
    configuration is used.

:yaml:`email.templateRootPaths`
    An array of paths to email templates. These are merged with the global
    mail template paths.

:yaml:`email.layoutRootPaths`
    An array of paths to email layouts. These are merged with the global mail
    layout paths.

:yaml:`email.partialRootPaths`
    An array of paths to email partials. These are merged with the global mail
    partial paths.

..  hint::

    Please note that entering these paths via the site settings GUI adds
    entries as a sequential array, numbered 0, 1, 2, and so on. In this case,
    all paths are appended to the array, giving all entries the highest
    priority.
    When editing `settings.yaml` manually, specific numerical array keys can
    be assigned.

Usage
=====

Frontend usage (site-aware)
---------------------------

For frontend contexts where site-specific templates are desired, use
:php:`createFromRequest()`. Include the :yaml:`typo3/email` site set in your
site configuration:

..  code-block:: yaml
    :caption: config/sites/my-site/config.yaml

    dependencies:
      - typo3/email

    settings:
      email:
        templateRootPaths:
          100: 'EXT:my_sitepackage/Resources/Private/Templates/Email/'
        layoutRootPaths:
          100: 'EXT:my_sitepackage/Resources/Private/Layouts/Email/'
        format: 'html'

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/MyFrontendEmailService.php

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Core\Mail\MailerInterface;
    use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;

    final class MyFrontendEmailService
    {
        public function __construct(
            private readonly TemplatedEmailFactory $templatedEmailFactory,
            private readonly MailerInterface $mailer,
        ) {}

        public function sendEmail(ServerRequestInterface $request): void
        {
            // Uses site-specific template paths if configured
            $email = $this->templatedEmailFactory->createFromRequest($request);
            $email
                ->setTemplate('MyTemplate')
                ->to('recipient@example.com')
                ->from('sender@example.com')
                ->subject('My Subject')
                ->assign('name', 'World');

            $this->mailer->send($email);
        }
    }

Backend and CLI usage
---------------------

For backend contexts where no site-specific templates are needed, use
:php:`create()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/MyBackendEmailService.php

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Core\Mail\MailerInterface;
    use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;

    final class MyBackendEmailService
    {
        public function __construct(
            private readonly TemplatedEmailFactory $templatedEmailFactory,
            private readonly MailerInterface $mailer,
        ) {}

        public function sendNotification(
            ?ServerRequestInterface $request = null,
        ): void {
            // Uses only global $GLOBALS['TYPO3_CONF_VARS']['MAIL'] configuration
            $email = $this->templatedEmailFactory->create($request);
            $email
                ->setTemplate('SystemNotification')
                ->to('admin@example.com')
                ->from('system@example.com')
                ->subject('System Notification');

            $this->mailer->send($email);
        }
    }

Custom template path overrides
------------------------------

For extensions that need their own email templates merged with the global
configuration, use :php:`createWithOverrides()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Task/MySchedulerTask.php

    use TYPO3\CMS\Core\Mail\MailerInterface;
    use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Scheduler\Task\AbstractTask;

    final class MySchedulerTask extends AbstractTask
    {
        public function sendReport(): void
        {
            // This example shows how to use this when constructor-based
            // dependency injection is not possible, as in AbstractTask
            // (EXT:scheduler). Always use dependency injection where possible.
            $mailer = GeneralUtility::makeInstance(MailerInterface::class);
            $templatedEmailFactory = GeneralUtility::makeInstance(
                TemplatedEmailFactory::class
            );

            // Merge extension-specific paths with the global configuration.
            // Note that if you do not pass a `$request` argument here, no site
            // context is evaluated. You may want to check
            // `$GLOBALS['TYPO3_REQUEST']` if you need this fallback, or use a
            // custom request object.
            $email = $templatedEmailFactory->createWithOverrides(
                templateRootPaths: [
                    20 => 'EXT:my_extension/Resources/Private/Templates/Email/',
                ],
                layoutRootPaths: [
                    20 => 'EXT:my_extension/Resources/Private/Layouts/',
                ],
            );
            $email
                ->setTemplate('Report')
                ->to('admin@example.com')
                ->from('system@example.com')
                ->subject('Scheduled Report');

            $mailer->send($email);
        }
    }

..  hint::

    You can use the
    :php-short:`\TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent`
    to change a `FluidEmail` object before it is sent. You can
    assign Fluid variables or modify parts of the email, such as setting
    `From` or the email subject prefix, depending on your site:

    ..  code-block:: php
        :caption: EXT:my_extension/Classes/Listener/MyMailerListener.php

        <?php
        declare(strict_types=1);

        use TYPO3\CMS\Core\Attribute\AsEventListener;
        use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;

        final class MyMailerListener
        {
            #[AsEventListener('my-extension/mymailerlistener')]
            public function __invoke(BeforeMailerSentMessageEvent $event): void
            {
                $message = $event->getMessage();
                $message->from('customized@example.com');
                $message->assign('mySpecialVariable', 'mySpecialContent');
                $event->setMessage($message);
            }
        }

Core migrations
===============

All core extensions that send emails have been migrated to use
:php:`TemplatedEmailFactory`. This includes:

*   **EXT:form** Email finishers now use :php:`createWithOverrides()` with the
    request from the form runtime, so site-specific email settings are applied
    automatically.
*   **EXT:felogin** Password recovery emails now use
    :php:`createWithOverrides()`, making them site-aware. The method
    :php:`RecoveryConfiguration::getMailTemplatePaths()` has been removed, as
    template path resolution is now handled by the factory.
*   **EXT:backend** Login notifications, failed login and MFA attempt
    notifications, and password reset emails use :php:`create()`.
*   **EXT:install** Test email sending uses :php:`create()`.
*   **EXT:workspaces** Stage change notifications use
    :php:`createWithOverrides()`.
*   **EXT:linkvalidator** Broken link report emails use
    :php:`createWithOverrides()`.
*   **EXT:reports** System status emails use :php:`create()`.

Impact
======

Extensions that send emails are encouraged to use the
:php:`TemplatedEmailFactory` to create :php:`FluidEmail` instances instead of
instantiating them directly. When a request with a site attribute is passed,
template paths and format from the :yaml:`typo3/email` site set are applied.
The merge priority, with the highest priority winning, is:

#.  Global :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']` paths as the base
#.  Site settings from :yaml:`typo3/email`, when a site-based request is
    available and site settings are applied
#.  Caller-provided override paths when using
    :php:`createWithOverrides()`

..  index:: PHP-API, YAML, ext:core
