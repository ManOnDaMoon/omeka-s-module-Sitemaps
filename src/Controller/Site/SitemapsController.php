<?php
namespace Sitemaps\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Response;
use Laminas\View\Renderer\PhpRenderer;

class SitemapsController extends AbstractActionController {
    
    protected $viewRenderer;
    
    public function __construct(PhpRenderer $viewRenderer) {
            $this->viewRenderer = $viewRenderer;
    }
    
    public function indexAction() {
        
        //TODO : Pagination for over 500 entries

        $site = $this->currentSite();
        
        $siteSettings = $this->siteSettings();
        $siteSettings->setTargetId($site->id());
        $hasSitemap = $siteSettings->get('sitemaps_enablesitemap', null);
        if (! $hasSitemap) {
            $this->response->setStatusCode(404);
            return;
        }
        
        /** @var \Laminas\View\Model\ViewModel $view */
        $view = new ViewModel();
        $view->setTemplate('site/sitemap');
                
        
        $query = array();
        $query['site_id'] = $site->id();
        $response = $this->api()->search('items', $query);
        $response->getTotalResults();
        $items = $response->getContent();
        
        
        $query = array();
        $query['site_id'] = $site->id();
        $response = $this->api()->search('item_sets', $query);
        $response->getTotalResults();
        $itemsets = $response->getContent();
        
        
        $pages = array_merge($site->linkedPages(), $site->notlinkedPages());
        
        $view->setVariable('site', $site);
        $view->setVariable('items', $items);
        $view->setVariable('itemsets', $itemsets);
        $view->setVariable('pages', $pages);
        $view->setTerminal(true);
        
        return $view;
    }
}
