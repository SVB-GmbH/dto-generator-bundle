<?php

namespace SVB\DataTransfer\DependencyInjection;

use SVB\DataTransfer\DtoGenerator;
use SVB\DataTransfer\Handler\DataTransferHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DtoGeneratorExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $container->setParameter('svb.dto_generator.cache_service', $mergedConfig['cache_service']);

        $definition = new Definition(DtoGenerator::class);
        $definition->setPublic(true);

        if (is_string($mergedConfig['cache_service'])) {
            $definition->addMethodCall('setCache', [new Reference($container->getParameter('svb.dto_generator.cache_service'))]);
        }

        $container->addDefinitions([DtoGenerator::class => $definition]);
        $container
            ->registerForAutoconfiguration(DataTransferHandlerInterface::class)
            ->addTag(DtoGeneratorCompilerPass::HANDLER_SERVICE_TAG)
        ;
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new DtoGeneratorConfiguration();
    }
}
