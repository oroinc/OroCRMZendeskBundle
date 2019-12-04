<?php

namespace Oro\Bundle\ZendeskBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_zendesk');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
