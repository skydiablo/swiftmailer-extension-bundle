<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\DependencyInjection;

use SkyDiablo\SwiftmailerExtensionBundle\Handler\aws\EmailReturnStatusHandler;
use SkyDiablo\SwiftmailerExtensionBundle\Spool\AWSSQSSpoolManager;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(SkyDiabloSwiftmailerExtensionExtension::ALIAS);

        $rootNode->children()
            ->scalarNode('email_sender_address')->cannotBeEmpty()->end()
            ->scalarNode('email_sender_name')->defaultNull()->end()
            ->arrayNode('spool')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('awssqs')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('queue')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('url')->end()
                                    ->integerNode('max_message_size')->defaultValue(256)->end()
                                    ->integerNode('long_polling_timeout')->defaultValue(AWSSQSSpoolManager::DEFAULT_AWS_SQS_LONG_POLLING_TIMEOUT)->end()
                                ->end()
                            ->end()
                            ->scalarNode('client_id')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('plugin')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('embedded_media')
                        ->addDefaultsIfNotSet()
                        ->canBeDisabled()
                        ->children()
                            ->scalarNode('embed_attribute_name')->defaultValue('embed')->end()
                            ->booleanNode('default')->defaultFalse()->end()
                        ->end()
                    ->end()
                    ->arrayNode('css2inline')
                        ->addDefaultsIfNotSet()
                        ->canBeDisabled()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('handler')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('email_return_status')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('aws_ses')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('queue')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('url')->cannotBeEmpty()->defaultValue('')->end()
                                           ->integerNode('long_polling_timeout')->defaultValue(EmailReturnStatusHandler::DEFAULT_AWS_SQS_LONG_POLLING_TIMEOUT)->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
