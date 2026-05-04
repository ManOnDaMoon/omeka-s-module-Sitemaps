<?php

namespace Sitemaps\Service\Controller;

use Sitemaps\Controller\GlobalSitemapsController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * [CHANGE] New factory for GlobalSitemapsController.
 *
 * Unlike SitemapsControllerFactory (which injects the ViewRenderer), the
 * global controller only needs the container's standard services — all of
 * which it accesses via controller plugin helpers (api(), settings(),
 * siteSettings()). No extra constructor arguments are required, so this
 * factory simply instantiates the class.
 */
class GlobalSitemapsControllerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        return new GlobalSitemapsController();
    }
}
