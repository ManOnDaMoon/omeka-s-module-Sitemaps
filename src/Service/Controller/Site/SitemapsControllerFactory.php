<?php
namespace Sitemaps\Service\Controller\Site;

use Sitemaps\Controller\Site\SitemapsController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SitemapsControllerFactory implements FactoryInterface
{

    /**
     * Instantiate sitelogin controller class with access to Authentication
     * Service
     *
     * {@inheritDoc}
     *
     * @see \Laminas\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new SitemapsController($container->get('ViewRenderer'));
    }
}
