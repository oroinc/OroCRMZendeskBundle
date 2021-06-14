The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

## 4.2.0-rc (2020-11-30)
[Show detailed list of changes](incompatibilities-4-2-rc.md)

## 4.2.0-alpha.3 (2020-07-30)
[Show detailed list of changes](incompatibilities-4-2-alpha-3.md)

## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)

### Removed
* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).

## 4.1.0-beta (2019-09-30)

### Removed
* All `*.class` parameters for service definitions were removed from the dependency injection container.

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

## 3.0.0-beta (2018-03-30)
[Show detailed list of changes](incompatibilities-3-0-beta.md)

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
