..  include:: /Includes.rst.txt

..  _feature-91724-1737200000:

================================================================================
Feature: #91724 - Introduce TemplatedEmailFactory for centralized email creation
================================================================================

See :issue:`91724`

Description
===========

A new :php-short:`\TYPO3\CMS\Core\Mail\TemplatedEmailFactory` class has been
introduced that provides centralized creation of :php-short:`\TYPO3\CMS\Core\Mail\FluidEmail` instances.

The factory provides three methods for different use cases:

:php:`create()`
    For backend/CLI contexts (login notifications, scheduler tasks, install
    tool) where only global configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL'][...]`
    is used.

:php:`createFromRequest()`
    For frontend contexts (form submissions, felogin) where site-specific
    email templates should be applied. Merges site settings :yaml:`typo3/email`
    with global configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL'][...]`.

:php:`createWithOverrides()`
    For extensions that need to provide custom template paths merged on top
    of the base configuration, taking an optional request context into
    evaluation. Two cases of template resolution are then possible
    (ordered by priority):

    *   Request without site attribute: 1. Provided override arguments -> 2. global config
    *   Request **with** site attribute: 1. Provided override arguments -> 2. Site settings -> 3. global config

    Note you can also utilize the numerical priority of template paths so that site settings
    with a higher priority number could "win" the order battle of a lower-number in the
    override argument paths.

Site settings
=============

A new site set :yaml:`typo3/email` is available in EXT:core that defines the
following settings. These are applied automatically when a request with a site
attribute is passed to :php:`createFromRequest()` or :php:`createWithOverrides()`.
This means extensions running in a frontend context (such as EXT:form email
finishers) benefit from site-specific email configuration:

:yaml:`email.format`
    The email format to use (html, plain, both). If empty, the global
    configuration is used.

:yaml:`email.templateRootPaths`
    Array of paths to email templates. These are merged with the global mail
    template paths.

:yaml:`email.layoutRootPaths`
    Array of paths to email layouts. These are merged with the global mail
    layout paths.

:yaml:`email.partialRootPaths`
    Array of paths to email partials. These are merged with the global mail
    partial paths.

..  hint::

    Please note that entering these paths via the Site settings GUI will
    add entries as sequential array (numbered 0, 1, 2, ...). In this case,
    all paths will just be appended to the array, giving all entries here
    the highest priority.
    When editing `settings.yaml` manually, specific numerical array keys
    can be assigned, if needed.

sage
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
    use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
    use TYPO3\CMS\Core\Mail\MailerInterface;

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

Backend/CLI usage
-----------------

For backend contexts where no site-specific templates are needed, use
:php:`create()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/MyBackendEmailService.php

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
    use TYPO3\CMS\Core\Mail\MailerInterface;

    final class MyBackendEmailService
    {
        public function __construct(
            private readonly TemplatedEmailFactory $templatedEmailFactory,
            private readonly MailerInterface $mailer,
        ) {}

        public function sendNotification(?ServerRequestInterface $request = null): void
        {
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

For extensions that need their own email templates merged with global
configuration, use :php:`createWithOverrides()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Task/MySchedulerTask.php

    use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
    use TYPO3\CMS\Core\Mail\MailerInterface;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Scheduler\Task\AbstractTask;

    final class MySchedulerTask extends AbstractTask
    {
        public function sendReport(): void
        {
            // This example showcases how to use this when
            // constructor-based Dependency Injection is blocked, like in the case of
            // AbstractTask (EXT:scheduler). Always use DI, where possible.
            $mailer = GeneralUtility::makeInstance(MailerInterface::class);
            $templatedEmailFactory = GeneralUtility::makeInstance(TemplatedEmailFactory::class);

            // Merge extension-specific paths with global configuration.
            // Note, if you do NOT pass a `$request` argument here, no site context will be evaluated.
            // You may want to check for `$GLOBALS['TYPO3_REQUEST']` in case you want this fallback,
            // or a custom created request object.
            $email = $templatedEmailFactory->createWithOverrides(
                templateRootPaths: [20 => 'EXT:my_extension/Resources/Private/Templates/Email/'],
                layoutRootPaths: [20 => 'EXT:my_extension/Resources/Private/Layouts/'],
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

    Remember, you can use the :php-short:`\TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent`
    to do adjustments to a `FluidEmail` object, before it is sent. There you can also still assign
    fluid variables or adjust parts of the email (like setting `From` or the mail's subject
    prefix, depending on your site):

    ..  code-block:: php
        :caption: EXT:my_extension/Classes/Listener/MyMailerListener.php

        <?php
        declare(strict_types=1);

        use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
        use TYPO3\CMS\Core\Attribute\AsEventListener;

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

All core extensions that send emails have been migrated to use the
:php:`TemplatedEmailFactory`. This includes:

* **EXT:form** - Email finishers now use :php:`createWithOverrides()` with the
  request from the form runtime, so site-specific email settings are applied
  automatically.
* **EXT:felogin** - Password recovery emails now use
  :php:`createWithOverrides()`, making them site-aware. The method
  :php:`RecoveryConfiguration::getMailTemplatePaths()` has been removed as
  template path resolution is now handled by the factory.
* **EXT:backend** - Login notifications, failed login/MFA attempt
  notifications, and password reset emails use :php:`create()`.
* **EXT:install** - Test email sending uses :php:`create()`.
* **EXT:workspaces** - Stage change notifications use
  :php:`createWithOverrides()`.
* **EXT:linkvalidator** - Broken link report emails use
  :php:`createWithOverrides()`.
* **EXT:reports** - System status emails use :php:`create()`.


Impact
======

Extensions that send emails are encouraged to use the
:php:`TemplatedEmailFactory` to create :php:`FluidEmail` instances instead of
directly instantiating them. When a request with a site attribute is passed,
template paths and format from the :yaml:`typo3/email` site set are applied
automatically. The merge priority is (highest priority wins):

1.  Global :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']` paths (base)
2.  Site settings from :yaml:`typo3/email`, when a site-based request is available,
    and site settings are applied.
3.  Caller-provided override paths, when using :php:`createWithOverrides()`.

..  index:: PHP-API, YAML, ext:core
