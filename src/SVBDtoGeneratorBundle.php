<?php

namespace SVB\DataTransfer;

use SVB\DataTransfer\DependencyInjection\DtoGeneratorCompilerPass;
use SVB\DataTransfer\DependencyInjection\DtoGeneratorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SVBDtoGeneratorBundle extends Bundle
{
    public function getContainerExtension(): DtoGeneratorExtension
    {
        return new DtoGeneratorExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DtoGeneratorCompilerPass());
    }
}
