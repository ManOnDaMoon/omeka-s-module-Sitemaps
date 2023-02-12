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
        
        $maxEntries = (int) $siteSettings->get('sitemaps_maxentries', 500);

        // Sitemap #1 is for pages and item sets. Check if there are some
        // Fetch pages
        $query = array();
        $query['site_id'] = $site->id();
        $query['sort_by'] = 'modified';
        $query['sort_order'] = 'desc';
        $query['limit'] = 1;
        $response = $this->api()->search('site_pages', $query);
        $pagesCount = $response->getTotalResults();
        if ($content = $response->getContent()) {
            $lastModPage = $content[0];
        } else {
            $lastModPage = null;
        }

        // Fetch item sets
        $query = array();
        $query['site_id'] = $site->id();
        $query['sort_by'] = 'modified';
        $query['sort_order'] = 'desc';
        $query['limit'] = 1;
        $response = $this->api()->search('item_sets', $query);
        $itemsetsCount = $response->getTotalResults();
        if ($content = $response->getContent()) { // Sites can exist without attached item sets.
            $lastModItemSet = $content[0];
        } else {
            $lastModItemSet = null;
        }
        
        $sitemapsCount = 0;
        if ($lastModPage && $lastModItemSet) {
                $lastMod = max([$lastModPage->modified(), $lastModItemSet->modified()]);
        } else if ($lastModPage) {
                $lastMod = $lastModPage->modified();
        } else if ($lastModItemSet) {
            $lastMod = $lastModItemSet->modified();
        }
            
        if ($pagesCount + $itemsetsCount > 0) {
                $sitemaps[] = ['url' => $site->siteUrl($site->slug(), true) . '/sitemap-1.xml',
                    'lastmod' => $lastMod->format('Y-m-d')
                ];
                $sitemapsCount++;
        }
        
        // Sitemap #2 and next are for items
        $query = array();
        $query['site_id'] = $site->id();
        $query['limit'] = 0; // Just get the total count
        $response = $this->api()->search('items', $query);
        $itemsCount = $response->getTotalResults();
        
        $itemsSitemapsCount = intval($itemsCount / $maxEntries) + (($itemsCount % $maxEntries) > 0 ? 1 : 0);
        
        for ($i  = $sitemapsCount + 1; $i <= $itemsSitemapsCount + $sitemapsCount ; $i++) {
            $sitemaps[] = ['url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . $i . '.xml',
                'lastmod' => '' // TODO : Get max lastmod of pages and itemsets. To do so, add sort_by modified and sort_order desc to query
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
        
        // TODO : Add sort_by 'modified' and sort_order 'desc' to query
        
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
            // Simple Sitemap file with all content - Faster to run
            
            $pages = $site->pages();
            
            $query = array();
            $query['site_id'] = $site->id();
            $response = $this->api()->search('items', $query);
            $items = $response->getContent();
            
            $query = array();
            $query['site_id'] = $site->id();
            $response = $this->api()->search('item_sets', $query);
            $response->getTotalResults();
            $itemsets = $response->getContent();
            
            
            $entries = array_merge($pages, $items, $itemsets);
        } else {
            // TODO: use API to get pages and item sets
            
            $maxEntries = (int) $siteSettings->get('sitemaps_maxentries', 500);

            // Paginated sitemap file
            if ($sitemapPage == "1") {
                
                // Fetch site pages
                $pages = $site->pages();
                
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
                $query['per_page'] = $maxEntries;
                $query['sort_by'] = 'modified';
                $query['sort_order'] = 'desc';

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
