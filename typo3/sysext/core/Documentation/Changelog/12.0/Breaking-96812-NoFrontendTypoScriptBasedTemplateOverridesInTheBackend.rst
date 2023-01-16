.. include:: /Includes.rst.txt

.. _breaking-96812:

=================================================================================
Breaking: #96812 - No Frontend TypoScript based template overrides in the backend
=================================================================================

See :issue:`96812`

Description
===========

A couple of Core extensions with backend module controllers allowed overriding Fluid
templates using frontend TypoScript. The two documented extensions are EXT:dashboard
and the backend page module. The Extbase based backend extensions EXT:belog, EXT:beuser
and EXT:extensionmanager allowed this implicitly too, but this detail has never
been directly documented.

This functionality has been removed: All Core extensions, and in general all extensions
that switch to the :doc:`simplified backend templating <Feature-96730-SimplifiedExtbackendModuleTemplateAPI>`
no longer use the frontend TypoScript based override approach. This has been superseded
by a general override strategy based on TSconfig, as described in :doc:`this changelog
entry <Feature-96812-OverrideBackendTemplatesWithTSconfig>`.

This change became necessary since configuring backend modules via frontend TypoScript
is flawed by design: It on one hand forces backend modules to parse the full frontend
TypoScript, which is a general performance penalty in the backend - the backend then
scales with the amount of frontend TypoScript. Also, the implementation is based on
the Extbase ConfigurationManager, which leads to the situation that casual non-Extbase
backend modules have an indirect dependency to lots of Extbase code. But most importantly,
frontend TypoScript is always bound to a page record. There is no concept in the frontend
for the "root" page zero, since that page can not be rendered in the frontend. In the backend
however, we have many modules that are not within page context: In general all modules that
do not have a page tree. This gives the "Use frontend TypoScript to configure backend modules"
approach some hard headaches: It forces the ConfigurationManager to still select "some" page as
frontend TypoScript entry point. In practice, the first non-hidden tree-level-one page
that has a sys_template record is selected. This strategy is both ugly and troublesome,
and leads to the situation that backend module configuration had to be bound to this first page,
which could easily explode when for instance pages are resorted - apart from the fact that
this scenario is hard to understand and to debug.

Impact
======

The combination of performance drawbacks, the tight Extbase coupling, and the
"which frontend TypoScript should be parsed for page zero?" problematic leads to the decision
to phase out the "frontend TypoScript for backend module configuration" approach that
Extbase brought in.

One part of this process is a generic backend approach to :doc:`override backend templates
using TSconfig <Feature-96812-OverrideBackendTemplatesWithTSconfig>`. This has impact
on EXT:dashboard widgets and page module template overrides.

Affected Installations
======================

Instances with extensions that configure own EXT:dashboard widgets or override templates
of existing dashboard widgets using Frontend TypoScript are affected, as well as instances
that override page module templates as described in :doc:`this changelog entry <../10.3/Feature-90348-NewFluid-basedReplacementForPageLayoutView>`.

Migration
=========

Page module template overrides
------------------------------

An instance sets frontend TypoScript like this:

..  code-block:: typoscript

    module.tx_backend.view.templateRootPaths.1644483508 = EXT:myext/Resources/Private/Templates/
    module.tx_backend.view.partialRootPaths.1644483508 = EXT:myext/Resources/Private/Partials/

If extension "myext" now delivered a template file such as :file:`Resources/Private/Templates/PageLayout/PageLayout.html`,
that template file was used for rendering the page module instead of the default template.

As described in this :doc:`changelog <Feature-96812-OverrideBackendTemplatesWithTSconfig>`,
the new definition is now done using TSconfig. The extension "myext" with Composer name "myvendor/myext" can
deliver a :file:`Configuration/page.tsconfig` file (see :doc:`changelog <Feature-96614-AutomaticInclusionOfPageTsConfigOfExtensions>`)
with the below content to substitute the old definition and keep overriding template files at the current position:

..  code-block:: typoscript

    # Pattern: templates."composer-name"."something-unique" = "overriding-extension-composer-name":"entry-path"
    templates.typo3/cms-backend.1644483508 = myvendor/myext:Resources/Private

EXT:dashboard
-------------

Required changes regarding existing template overrides of the dashboard extension and the
dashboard widget registration itself are a bit broader. Let's look at this in detail:

Templating
..........

An extension delivers this TypoScript:

..  code-block:: typoscript

    module.tx_dashboard {
        view {
            templateRootPaths {
                1644485473 = EXT:myext/Resources/Private/Templates/Dashboard/Widgets/
            }
        }
    }

This instructed the dashboard widget renderer to look up widget templates in this
path, too. The new registration for extension "myext" with Composer name "myvendor/myext"
using file :file:`Configuration/page.tsconfig`
(see :doc:`changelog <Feature-96614-AutomaticInclusionOfPageTsConfigOfExtensions>`)
could look like this:

..  code-block:: typoscript

    # Pattern: templates.typo3/cms-dashboard."something-unique" = "overriding-extension-composer-name":"entry-path"
    templates.typo3/cms-dashboard.1644485473 = myvendor/myext:Resources/Private

A widget template is then put to :file:`Resources/Private/Templates/Dashboard/Widgets/MyExtensionWidget.html`.
Extensions that want to stay compatible with both TYPO3 Core v11 and v12 should simply define both the
old way and the new way.

Widget registration using Services.yaml
.......................................

This part (changing :file:`Services.yaml` and widgets PHP code) is not strictly needed
for extensions that configure and deliver own widgets. Extension that work with TYPO3
v11 just work in v12 as well. However, the registration and PHP code changed a bit,
extensions that want to stay deprecation log free with v12 should adapt. The changes
outlined below will be mandatory with v13.

The registration of widgets using :file:`Services.yaml` should be changed a bit. It
was previously documented that widgets can inject an instance of :php:`StandaloneView`.
This approach was flawed: The :php:`StandaloneView` has an internal dependency to the
current PSR-7 request. The request is not available via dependency injection since it is
a heavily stateful runtime dependency. Injecting a view that depends on request is thus
a violation and only worked with EXT:dashboard because :php:`StandaloneView` hides that
dependency internally and creates a new request on the fly, which is a hack in that
implementation that should be avoided.

The view based on EXT:core :php:`ViewInterface` with its factory for backend views based
on EXT:backend :php:`BackendViewFactory` makes the dependency to the request object explicit.
As such, a "prepared" view can not be injected using DI anymore.

This has impact on both the PHP implementation of widgets, as well as the widget
dependency injection configuration.

Let's say a widget has been registered like this:

..  code-block:: yaml

  # This is defined in EXT:dashboard Services.yaml already, extensions
  # must not define this in their Services.yaml files again.
  dashboard.views.widget:
    class: 'TYPO3\CMS\Fluid\View\StandaloneView'
    public: true
    factory: ['TYPO3\CMS\Dashboard\Views\Factory', 'widgetTemplate']

  # This is your custom widget registration in your extensions Services.yaml
  dashboard.widget.sysLogErrors:
    class: 'TYPO3\CMS\Dashboard\Widgets\BarChartWidget'
    arguments:
      $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogErrorsDataProvider'
      $view: '@dashboard.views.widget'
      $buttonProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogButtonProvider'
    tags:
      ...

The important line is :yaml:`$view: '@dashboard.views.widget'`: This instructs the DI
to inject an instance of :php:`StandaloneView` using the EXT:dashboard :php:`Factory::widgetTemplate()`
method for argument :php:`$view`. The :yaml:`dashboard.views.widget` is deprecated since
TYPO3 Core v12 and should not be used anymore. It logs a deprecation message upon use
during build-time and will be removed in v13 together with the :php:`Factory`.

The new registration should be adapted to this, simply removing the :php:`$view` argument:

..  code-block:: yaml

  # This is your custom widget registration in your extensions Services.yaml
  dashboard.widget.sysLogErrors:
    class: 'TYPO3\CMS\Dashboard\Widgets\BarChartWidget'
    arguments:
      $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogErrorsDataProvider'
      $buttonProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogButtonProvider'
    tags:
      ...

Now the PHP implementation. The above example references the :php:`BarChartWidget` class
to take care of rendering. The class looked like this before (shortened):

..  code-block:: php

    class BarChartWidget implements WidgetInterface
    {
        public function __construct(
            private readonly WidgetConfigurationInterface $configuration,
            private readonly ChartDataProviderInterface $dataProvider,
            private readonly StandaloneView $view,
            private readonly $buttonProvider = null,
            private readonly array $options = []
        ) {
        }

        public function renderWidgetContent(): string
        {
            $this->view->setTemplate('Widget/ChartWidget');
            $this->view->assignMultiple([...]);
            return $this->view->render();
        }
    }

Since :php:`StandaloneView` should not be injected anymore, we now inject the
:php:`BackendViewFactory` instead and create a view using the factory in
:php:`renderWidgetContent()`. The factory :php:`create()` method needs the request
object. To get this, widgets should now implement :php:`RequestAwareWidgetInterface`,
the EXT:dashboard framework will then :php:`setRequest()` the current request to the widget
immediately after widget instantiation. The new code thus looks like this:

..  code-block:: php

    class BarChartWidget implements WidgetInterface, RequestAwareWidgetInterface
    {
        private ServerRequestInterface $request;

        public function __construct(
            private readonly WidgetConfigurationInterface $configuration,
            private readonly ChartDataProviderInterface $dataProvider,
            private readonly BackendViewFactory $backendViewFactory,
            private readonly $buttonProvider = null,
            private readonly array $options = []
        ) {
        }

        public function setRequest(ServerRequestInterface $request): void
        {
            $this->request = $request;
        }

        public function renderWidgetContent(): string
        {
            // The second argument is the Composer 'name' of the extension that adds the widget.
            // It is needed to instruct BackendViewFactory to look up templates in this package
            // next to the default location 'typo3/cms-dashboard', too.
            $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'myVendor/myPackage']);
            $this->view->assignMultiple([...]);
            return $this->view->render('Widget/ChartWidget');
        }
    }

The actual implementation in TYPO3 v12 is still slightly different to keep
compatibility with extensions that re-use Core widgets and need v11 and v12
compatibility at the same time. Those Core classes will be adapted in v13
to the above outline version, though.

.. index:: Backend, TSConfig, TypoScript, NotScanned, ext:backend
