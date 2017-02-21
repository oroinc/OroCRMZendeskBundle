UPGRADE FROM 2.0 to 2.1
========================

ZendeskBundle
-------------
- Parameter `oro_zendesk.twig.extension.class` was removed from the DI container
- Service `oro_zendesk.twig.extension` was marked as `private`
- Class `Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension`
    - construction signature was changed, now it takes only `ContainerInterface $container`
    - removed property `protected $oroProvider`
    - removed property `protected $zendeskProvider`
