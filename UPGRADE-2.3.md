UPGRADE FROM 2.2 to 2.3
=======================

- Class `Oro\Bundle\ZendeskBundle\EventListener\Doctrine\AbstractSyncSchedulerListener`
    - changed the constructor signature: parameter `ServiceLink $securityFacadeLink` was replaced with `TokenAccessorInterface $tokenAccessor`
    - property `securityFacade` was replaced with `tokenAccessor`
    - removed method `getSecurityFacade`
