The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/master/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## Changes in the Zendesk package versions

- [5.1.0](#510-2023-03-31)
- [5.0.0](#500-2022-01-26)
- [4.2.0](#420-2020-01-29)
- [4.1.0](#410-2020-01-31)
- [4.0.0](#400-2019-07-31)
- [3.0.0](#300-2018-07-27)
- [2.4.0](#240-2017-09-29)
- [2.3.0](#230-2017-07-28)
- [2.1.0](#210-2017-03-30)


## 5.1.0 (2023-03-31)
[Show detailed list of changes](incompatibilities-5-1.md)

## 5.0.0 (2022-01-26)
[Show detailed list of changes](incompatibilities-5-0.md)

### Removed
* The service `oro_zendesk.listener.doctrine.sync_abstract` was removed.


## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)

### Removed
* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).

* All `*.class` parameters for service definitions were removed from the dependency injection container.

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

## 2.4.0 (2017-09-29)
[Show detailed list of changes](incompatibilities-2-4.md)

## 2.3.0 (2017-07-28)
[Show detailed list of changes](incompatibilities-2-3.md)

## 2.1.0 (2017-03-30)
[Show detailed list of changes](incompatibilities-2-1.md)

### Changed
- Service `oro_zendesk.twig.extension` was marked as `private`
### Removed
- Parameter `oro_zendesk.twig.extension.class` was removed from the DI container
- Class `ZendeskExtension`<sup>[[?]](https://github.com/oroinc/OroCRMZendeskBundle/tree/2.1.0/Twig/ZendeskExtension.php "Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension")</sup>
    - removed property `protected $zendeskProvider`
