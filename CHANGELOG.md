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
