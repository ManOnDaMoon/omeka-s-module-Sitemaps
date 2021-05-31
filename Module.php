<?php
namespace Sitemaps;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\EventManager\EventInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Permissions\Acl;

class Module extends AbstractModule
{
    /**
     * Include the configuration array containing the sitelogin controller, the
     * sitelogin controller factory and the sitelogin route
     *
     * {@inheritDoc}
     *
     * @see \Omeka\Module\AbstractModule::getConfig()
     */
    public function getConfig ()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    /**
     * Called on module application bootstrap, this adds the required ACL level
     * authorization for anybody to use the sitelogin controller
     *
     * {@inheritDoc}
     *
     * @see \Omeka\Module\AbstractModule::onBootstrap()
     */
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        
        /** @var Acl $acl */
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            [
                'Sitemaps\Controller\Site\SitemapsController'
            ],
            null
            );
    }
    
    /**
     * Attach to Laminas and Omeka specific listeners
     */
    public function attachListeners(
        SharedEventManagerInterface $sharedEventManager
        ) {
            // Attach to site settings form to add the module settings
            $sharedEventManager->attach(
                'Omeka\Form\SiteSettingsForm',
                'form.add_elements',
                array(
                    $this,
                    'addSitemapsSiteSetting'
                )
                );
    }
    
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $api = $serviceLocator->get('Omeka\ApiManager');
        $sites = $api->search('sites', [])->getContent();
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
        
        foreach ($sites as $site) {
            $siteSettings->setTargetId($site->id());
            $siteSettings->delete('sitemaps_enablesitemap');
        }
    }
    
    /**
     * Adds a Checkbox element to the site settings form
     * This element is automatically handled by Omeka in the site_settings table
     *
     * @param EventInterface $event
     */
    public function addSitemapsSiteSetting(EventInterface $event)
    {
        /** @var \Omeka\Form\UserForm $form */
        $form = $event->getTarget();
        
        $siteSettings = $form->getSiteSettings();
        
        $form->add([
            'type' => 'fieldset',
            'name' => 'sitemaps',
            'options' => [
                'label' => 'Sitemap file', // @translate
            ],
        ]);
        
        $rsFieldset = $form->get('sitemaps');
        
        $rsFieldset->add(
            array(
                'name' => 'sitemaps_enablesitemap',
                'type' => 'Checkbox',
                'options' => array(
                    'label' => 'Enable dynamic sitemap for this site', // @translate
                    'info' => 'Dynamically generates a sitemap.xml file at the root of the site, e.g.: https://myomekasite.com/s/slug/sitemap.xml', // @translate
                ),
                'attributes' => array(
                    'value' => (bool) $siteSettings->get(
                        'sitemaps_enablesitemap',
                        false
                        )
                )
            )
            );
        return;
    }
}