<?php

namespace SkyDiablo\SwiftmailerExtensionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SkyDiabloSwiftmailerExtensionBundle extends Bundle
{

    /**
     * @return null|\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = $this->createContainerExtension();
        }
        return $this->extension;
    }

}
