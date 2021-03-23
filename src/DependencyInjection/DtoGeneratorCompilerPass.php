<?php

namespace SVB\DataTransfer\DependencyInjection;

use SVB\DataTransfer\DtoGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DtoGeneratorCompilerPass implements CompilerPassInterface
{
    public const HANDLER_SERVICE_TAG = 'svb.data_transfer.handler';

    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(DtoGenerator::class);

        foreach (array_keys($container->findTaggedServiceIds(self::HANDLER_SERVICE_TAG)) as $serviceId) {
            $definition->addMethodCall('addHandler', [new Reference($serviceId)]);
        }
    }
}
