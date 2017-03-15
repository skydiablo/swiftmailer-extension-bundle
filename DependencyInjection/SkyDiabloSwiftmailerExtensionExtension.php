<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\DependencyInjection;

use SkyDiablo\SwiftmailerExtensionBundle\Spool\AWSSQSSpoolManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SkyDiabloSwiftmailerExtensionExtension extends Extension
{

    const ALIAS = 'skydiablo_swiftmailer_extension';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->handleDefault($config, $container);
        $this->handleSpool($config['spool'], $container);
        $this->handlePlugin($config['plugin'], $container);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    protected function handleDefault(array $pluginConfig, ContainerBuilder $container)
    {
        $container->getParameterBag()->set('skydiablo.swiftmailer-extension.email_sender_address', $pluginConfig['email_sender_address']);
        $container->getParameterBag()->set('skydiablo.swiftmailer-extension.email_sender_name', $pluginConfig['email_sender_name']);

    }

    protected function handleSpool(array $spoolConfigs, ContainerBuilder $container)
    {
        foreach ($spoolConfigs AS $key => $spoolConfig) {
            $serviceId = sprintf('skydiablo.swiftmailer-extension.spool.%s.handler', $key);
            switch (strtolower($key)) {
                case 'awssqs':
                    $container->setDefinition($serviceId, new Definition(
                        AWSSQSSpoolManager::class,
                        [
                            new Reference($spoolConfig['client_id']),
                            $spoolConfig['queue'],
                            new Reference('logger')
                        ]
                    ));
                    break;
            }

            //publish spool manager type
            $container->setAlias(sprintf('swiftmailer.spool.%s', $key), $serviceId);
            $container->setAlias(sprintf('swiftmailer.mailer.default.spool.%s', $key), $serviceId);
        }
    }

    protected function handlePlugin(array $pluginConfig, ContainerBuilder $container)
    {
        foreach ($pluginConfig AS $key => $config) {
            switch (strtolower($key)) {
                case 'embedded_media':
                    if ($config['enabled']) {
                        $container->getParameterBag()->set('skydiablo.swiftmailer-extension.plugin.embedded-media.embed-attribute-name', $config['embed_attribute_name']);
                        $container->getParameterBag()->set('skydiablo.swiftmailer-extension.plugin.embedded-media.default', $config['default']);
                    } else {
                        $container->removeDefinition('skydiablo.swiftmailer-extension.plugin.embedded-media');
                    }
                    break;
                case 'css2inline':
                    if (!$config['enabled']) {
                        $container->removeDefinition('skydiablo.swiftmailer-extension.plugin.css2inline');
                    }
                    break;
            }
        }
    }

    public function getAlias()
    {
        return self::ALIAS;
    }
}
