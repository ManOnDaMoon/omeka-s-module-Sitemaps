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
            $siteSettings->delete('sitemaps_enableindex');
            $siteSettings->delete('sitemaps_maxentries');
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
        $options = $form->getOptions();
        $options['element_groups']['sitemaps'] = 'Sitemaps';
        $form->setOption('element_groups', $options['element_groups']);
        
        $form->add(
            array(
                'name' => 'sitemaps_enablesitemap',
                'type' => 'Checkbox',
                'options' => [
                    'element_group' => 'sitemaps',
                    'label' => 'Enable dynamic sitemap for this site', // @translate
                    'info' => 'Dynamically generates a sitemap.xml file at the root of the site, e.g.: https://myomekasite.com/s/slug/sitemap.xml', // @translate
                ],
                'attributes' => [
                    'value' => (bool) $siteSettings->get(
                        'sitemaps_enablesitemap',
                        false
                        )
                ]
            )
            );
        
        $form->add(
            array(
            'name' => 'sitemaps_enableindex',
            'type' => 'Checkbox',
            'options' => [
                'element_group' => 'sitemaps',
                'label' => 'Enable sitemap index', // @translate
                'info' => 'Use this setting if you have a large number of items and pages (i.e. more than 500). A https://myomekasite.com/s/slug/sitemapindex.xml file will be generated with a list of paginated sitemaps.', // @translate
            ],
            'attributes' => [
                'value' => (bool) $siteSettings->get(
                    'sitemaps_enableindex',
                    false
                    )
            ]
        )
        );

        $form->add([
            'type' => 'Number',
            'name' => 'sitemaps_maxentries',
            'options' => [
                'label' => 'Sitemap max entry count', // @translate
                'info' => 'Use this setting to control how many entry lines will contain a single sitemap file when using indexing', // @translate
                'element_group' => 'sitemaps'
            ],
            'attributes' => [
                'value' => $siteSettings->get('sitemaps_maxentries', 500),
                'min' => 1,
                'max' => 50000,
            ],
        ]);
        return;
    }
}