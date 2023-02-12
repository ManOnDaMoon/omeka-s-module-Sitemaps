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
     
        $site = $this->currentSite();
        $sitemaps = [];
        
        $siteSettings = $this->siteSettings();
        $siteSettings->setTargetId($site->id());
        
        $hasIndex = $siteSettings->get('sitemaps_enableindex', null);
        if (!$hasIndex) {
            $this->response->setStatusCode(404);
            return;
        }
        
        // Sitemap #1 is for pages and item sets. Check if there are some
        $pages = array_merge($site->linkedPages(), $site->notlinkedPages());
        
        $query = array();
        $query['site_id'] = $site->id();
        $response = $this->api()->search('item_sets', $query);
        $response->getTotalResults();
        $itemsets = $response->getContent();
        
        if (count($pages) > 0 || count($itemsets) > 0) {
            $sitemaps[] = ['url' => $site->siteUrl($site->slug(), true) . '/sitemap-1.xml',
                'lastmod' => '' // TODO : Get max lastmod of pages and itemsets
            ];
        }
        
        // Sitemap #2 and next are for items
        $query = array();
        $query['site_id'] = $site->id();
        $query['limit'] = 0;
        $response = $this->api()->search('items', $query);
        $itemsCount = $response->getTotalResults();
        
        $sitemapsCount = intval($itemsCount / 200) + (($itemsCount % 200) > 0 ? 1 : 0); // TODO fetch limit from setting
        
        for ($i  = 2; $i <= $sitemapsCount + 1; $i++) {
            $sitemaps[] = ['url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . $i . '.xml',
                'lastmod' => '' // TODO : Get max lastmod of pages and itemsets
            ];
        }
        
        /** @var \Laminas\View\Model\ViewModel $view */
        $view = new ViewModel();
        $view->setTemplate('site/sitemap-index');
        
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
            // Paginated sitemap file
            if ($sitemapPage == "1") {
                
                // Fetch site pages
                $pages = array_merge($site->linkedPages(), $site->notlinkedPages());
                
                // Fetch site item sets
                $query = array();
                $query['site_id'] = $site->id();
                $response = $this->api()->search('item_sets', $query);
                $itemsets = $response->getContent();
                
                // For now, assume pages and item sets are less than 500
                // TODO fetch limit from setting
                $entries = array_merge($pages, $itemsets);
                
            } else {
                $query = array();
                $query['site_id'] = $site->id();
                $query['page'] = $sitemapPage - 1;
                $query['per_page'] = 200; // TODO fetch limit from setting

                $response = $this->api()->search('items', $query);
                $entries = $response->getContent();
                
                if (count($entries) == 0) {
                    $this->response->setStatusCode(404);
                    return;
                }
            }
                
        }
        $view->setVariable('site', $site);
        $view->setVariable('entries', $entries);
        $view->setTerminal(true);

        return $view;
    }
}
