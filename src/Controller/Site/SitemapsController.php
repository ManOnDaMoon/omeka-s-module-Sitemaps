<?php
namespace Sitemaps\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Laminas\View\Renderer\PhpRenderer;

class SitemapsController extends AbstractActionController {
    
    protected $viewRenderer;
    
    public function __construct(PhpRenderer $viewRenderer) {
            $this->viewRenderer = $viewRenderer;
    }
    
    public function indexAction() {
     
        /** @var \Laminas\View\Model\ViewModel $view */
        $view = new ViewModel();
        $view->setTemplate('site/sitemap-index');
        
        $sitemaps = null;
        
        $view->setVariable('sitemaps', $sitemaps);
        $view->setTerminal(true);
        
        return $view;
    }
    
    public function sitemapAction() {
        
        $site = $this->currentSite();
        $sitemapPage = $this->params('sitemap-page');
        
        $siteSettings = $this->siteSettings();
        $siteSettings->setTargetId($site->id());
        $hasSitemap = $siteSettings->get('sitemaps_enablesitemap', null);
        if (! $hasSitemap) { // Sitemap disabled for this site
            $this->response->setStatusCode(404);
            return;
        }
        
        $hasIndex = $siteSettings->get('sitemaps_enableindex', null);
        // If a page is given as parameter, check if sitemap index is enabled
        if ($hasIndex xor $sitemapPage) {
            $this->response->setStatusCode(404);
            return;
        }
        
        /** @var \Laminas\View\Model\ViewModel $view */
        $view = new ViewModel();
        $view->setTemplate('site/sitemap');
         
        $entries = [];
        
        if (!$hasIndex) {
            // Simple Sitemap file with all content
            $pages = array_merge($site->linkedPages(), $site->notlinkedPages());
            
            $query = array();
            $query['site_id'] = $site->id();
            // According to count, enable multi page indexes.
            // Query options: page, per_page, limit, offset, sort_by, sort_order, return_scalar
            $response = $this->api()->search('items', $query);
            $items = $response->getContent();
            
            $query = array();
            $query['site_id'] = $site->id();
            $response = $this->api()->search('item_sets', $query);
            $response->getTotalResults();
            $itemsets = $response->getContent();
            
            
            $entries = array_merge($pages, $items, $itemsets);
        } else {
            // Paginated sitemap file, with a precise
            //TODO : define structure
            // Sitemap-1 = pages + item sets ?
            // Sitemap-2+ = items
            if ($sitemapPage == "1") {
                
                // Fetch site pages
                $pages = array_merge($site->linkedPages(), $site->notlinkedPages());
                
                // Fetch site item sets
                $query = array();
                $query['site_id'] = $site->id();
                $response = $this->api()->search('item_sets', $query);
                $itemsets = $response->getContent();
                
                // For now, assume pages and item sets are less than 500
                $entries = array_merge($pages, $itemsets);
                
            } else {
                $query = array();
                $query['site_id'] = $site->id();
                $query['page'] = $sitemapPage - 1;
                $query['per_page'] = 200;

                $response = $this->api()->search('items', $query); //TODO setting for 500 ? Could ease testing!
                $entries = $response->getContent();
                
                if (count($entries) == 0) {
                    $this->response->setStatusCode(404);
                    return;
                }

                assert(count($entries) <= 200); // TODO remove assert
            }
                
        }
        $view->setVariable('site', $site);
        $view->setVariable('entries', $entries);
        $view->setTerminal(true);

        return $view;
    }
}
