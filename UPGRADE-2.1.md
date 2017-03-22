UPGRADE FROM 2.0 to 2.1
========================

####General
- Changed minimum required php version to 7.0
- Updated dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin to version 1.3.
- Composer updated to version 1.4.

```
    composer self-update
    composer global require "fxp/composer-asset-plugin"
```

ZendeskBundle
-------------
- Parameter `oro_zendesk.twig.extension.class` was removed from the DI container
- Service `oro_zendesk.twig.extension` was marked as `private`
- Class `Oro\Bundle\ZendeskBundle\Twig\ZendeskExtension`
    - construction signature was changed, now it takes only `ContainerInterface $container`
    - removed property `protected $oroProvider`
    - removed property `protected $zendeskProvider`
