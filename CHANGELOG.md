## 2.4.0 (Unreleased)
[Show detailed list of changes](file-incompatibilities-2-4-0.md)

## 2.3.0 (2017-07-28)
[Show detailed list of changes](file-incompatibilities-2-3-0.md)

## 2.1.0 (2017-03-30)
[Show detailed list of changes](file-incompatibilities-2-1-0.md)

### Changed
- Service `oro_zendesk.twig.extension` was marked as `private`
### Removed
- Parameter `oro_zendesk.twig.extension.class` was removed from the DI container
- Class `Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension`
    - removed property `protected $zendeskProvider`
