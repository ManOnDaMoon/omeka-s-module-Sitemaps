<?php

namespace Sitemaps\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

class SitemapsController extends AbstractActionController
{
    protected $viewRenderer;

    public function __construct(PhpRenderer $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer;
    }

    public function indexAction()
    {
        $site = $this->currentSite();
        $sitemaps = [];

        $siteSettings = $this->siteSettings();
        $siteSettings->setTargetId($site->id());

        $hasSitemap = $siteSettings->get('sitemaps_enablesitemap', null);
        if (!$hasSitemap) { // Sitemap disabled for this site
            $this->response->setStatusCode(404);
            return;
        }

        $hasIndex = $siteSettings->get('sitemaps_enableindex', null);
        if (!$hasIndex) {
            // Redirect to sitemap action
            return $this->redirect()->toRoute('site/sitemap', ['site-slug' => $site->slug()]);
        }

        $maxEntries = (int) $siteSettings->get('sitemaps_maxentries', 500);
        $sitemapsCount = 0;

        // Sitemaps for pages
        // Fetch pages count and last mod date
        $query = [
            'site_id' => $site->id(),
            'sort_by' => 'modified',
            'sort_order' => 'desc',
            'limit' => 1,
        ];
        $response = $this->api()->search('site_pages', $query);
        $pagesCount = $response->getTotalResults();
        if ($content = $response->getContent()) { // Sites can exist without pages
            $lastModPage = $content[0];
        }

        if ($pagesCount > 0) { // Generate pages sitemap urls
            $pagesSitemapsCount = intdiv($pagesCount, $maxEntries) + (($pagesCount % $maxEntries) > 0 ? 1 : 0);
            if ($pagesSitemapsCount > 1) {
                for ($i = 1; $i <= $pagesSitemapsCount ; $i++) {
                    $query = [
                        'site_id' => $site->id(),
                        'sort_by' => 'modified',
                        'sort_order' => 'desc',
                        'limit' => 1, // Only fetch the last modified entry for each page
                        'offset' => (($i - 1) * $maxEntries),
                    ];
                    $response = $this->api()->search('site_pages', $query);
                    $content = $response->getContent();
                    $itemsCount = $response->getTotalResults();

                    $sitemaps[] = [
                        'url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . $i . '.xml',
                        'lastmod' => $content[0]->modified()->format('Y-m-d'),
                    ];
                }
                $sitemapsCount += $pagesSitemapsCount;
            } else {
                // Simple case with one single sitemap for Pages
                $sitemaps[] = [
                    'url' => $site->siteUrl($site->slug(), true) . '/sitemap-1.xml',
                    'lastmod' => $lastModPage->modified()->format('Y-m-d'),
                ];
                $sitemapsCount++;
            }
        }

        // Sitemaps for Item Sets
        // Fetch item sets count and last mod date
        $query = [
            'site_id' => $site->id(),
            'sort_by' => 'modified',
            'sort_order' => 'desc',
            'limit' => 1,
        ];
        $response = $this->api()->search('item_sets', $query);
        $itemsetsCount = $response->getTotalResults();
        if ($content = $response->getContent()) { // Sites can exist without attached item sets.
            $lastModItemSet = $content[0];
        }

        if ($itemsetsCount > 0) {
            $itemsetsSitemapsCount = intdiv($itemsetsCount, $maxEntries) + (($itemsetsCount % $maxEntries) > 0 ? 1 : 0);
            if ($itemsetsSitemapsCount > 1) {
                for ($i = $sitemapsCount + 1; $i <= $itemsetsSitemapsCount + $sitemapsCount ; $i++) {
                    $query = [
                        'site_id' => $site->id(),
                        'sort_by' => 'modified',
                        'sort_order' => 'desc',
                        'limit' => 1, // Only fetch the last modified entry for each page
                        'offset' => (($i - $sitemapsCount - 1) * $maxEntries),
                    ];
                    $response = $this->api()->search('item_sets', $query);
                    $content = $response->getContent();
                    $itemsCount = $response->getTotalResults();

                    $sitemaps[] = [
                        'url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . ($i + $sitemapsCount) . '.xml',
                        'lastmod' => $content[0]->modified()->format('Y-m-d'),
                    ];
                }
                $sitemapsCount += $itemsetsSitemapsCount;
            } else {
                // Simple case with one single sitemap for Item Sets
                $sitemaps[] = [
                    'url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . (1 + $sitemapsCount) . '.xml',
                    'lastmod' => $lastModItemSet->modified()->format('Y-m-d'),
                ];
                $sitemapsCount++;
            }
        }

        // Sitemap #2 and next are for items
        // Just get the total count
        $query = [
            'site_id' => $site->id(),
            'limit' => 1,
            'sort_by' => 'modified',
            'sort_order' => 'desc',
        ];
        $response = $this->api()->search('items', $query);
        $itemsCount = $response->getTotalResults();
        if ($content = $response->getContent()) { // Sites can exist without attached item sets.
            $lastModItem = $content[0];
        }

        if ($itemsCount > 0) {
            $itemsSitemapsCount = intdiv($itemsCount, $maxEntries) + (($itemsCount % $maxEntries) > 0 ? 1 : 0);
            if ($itemsSitemapsCount > 1) {
                for ($i = $sitemapsCount + 1; $i <= $itemsSitemapsCount + $sitemapsCount ; $i++) {
                    $query = [
                        'site_id' => $site->id(),
                        'sort_by' => 'modified',
                        'sort_order' => 'desc',
                        'limit' => 1, // Only fetch the last modified entry for each page
                        'offset' => (($i - $sitemapsCount - 1) * $maxEntries),
                    ];
                    $response = $this->api()->search('items', $query);
                    $content = $response->getContent();
                    $itemsCount = $response->getTotalResults();

                    $sitemaps[] = [
                        'url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . $i . '.xml',
                        'lastmod' => $content[0]->modified()->format('Y-m-d'),
                    ];
                }
                $sitemapsCount += $itemsSitemapsCount;
            } else {
                // Simple case with one single sitemap for Items
                $sitemaps[] = [
                    'url' => $site->siteUrl($site->slug(), true) . '/sitemap-' . (1 + $sitemapsCount) . '.xml',
                    'lastmod' => $lastModItem->modified()->format('Y-m-d'),
                ];
                $sitemapsCount++;
            }
        }

        /** @var \Laminas\View\Model\ViewModel $view */
        $view = new ViewModel();
        $view->setTemplate('site/sitemap-index');
        $view->setVariable('sitemaps', $sitemaps);
        $view->setTerminal(true);

        return $view;
    }

    public function sitemapAction()
    {
        $site = $this->currentSite();

        $sitemapPage = (int) $this->params('sitemap-page');

        $siteSettings = $this->siteSettings();
        $siteSettings->setTargetId($site->id());
        $hasSitemap = $siteSettings->get('sitemaps_enablesitemap', null);
        if (!$hasSitemap) { // Sitemap disabled for this site
            $this->response->setStatusCode(404);
            return;
        }

        $hasIndex = $siteSettings->get('sitemaps_enableindex', null);

        if ($hasIndex && !$sitemapPage) {
            // Requesting sitemap.xml in a context of indexed sitemap without page parameter - Redirect to sitemapindex.xml
            return $this->redirect()->toRoute('site/sitemapindex', ['site-slug' => $site->slug()]);
        }
        if (!$hasIndex && $sitemapPage) {
            // Requesting a page in a context of unindexed sitemap
            $this->response->setStatusCode(404);
            return;
        }

        /** @var \Laminas\View\Model\ViewModel $view */
        $view = new ViewModel();
        $view->setTemplate('site/sitemap');
        $view->setVariable('site', $site);
        $view->setTerminal(true);
        $entries = [];

        if (!$hasIndex) {
            // Simple Sitemap file with all content - Faster to run

            $pages = $site->pages();

            $query = ['site_id' => $site->id()];
            $response = $this->api()->search('items', $query);
            $items = $response->getContent();

            $query = ['site_id' => $site->id()];
            $response = $this->api()->search('item_sets', $query);
            $response->getTotalResults();
            $itemsets = $response->getContent();

            $entries = array_merge($pages, $items, $itemsets);
            
            $view->setVariable('entries', $entries);
            return $view; 
            
        } else {
            $maxEntries = (int) $siteSettings->get('sitemaps_maxentries', 500);

            // Fetch pages count and last mod date
            $query = [
                'site_id' => $site->id(),
                'sort_by' => 'modified',
                'sort_order' => 'desc',
                'limit' => 0,
            ];
            $response = $this->api()->search('site_pages', $query);
            $pagesCount = $response->getTotalResults();
            $pagesSitemapsCount = intdiv($pagesCount, $maxEntries) + (($pagesCount % $maxEntries) > 0 ? 1 : 0);

            // If $sitemapPage <= this count, return the page! Else continue
            if ($sitemapPage <= $pagesSitemapsCount) {
                $query = [
                    'site_id' => $site->id(),
                    'sort_by' => 'modified',
                    'sort_order' => 'desc',
                    'limit' => $maxEntries,
                    'offset' => (($sitemapPage - 1) * $maxEntries),
                ];
                $response = $this->api()->search('site_pages', $query);
                $entries = $response->getContent();
                $view->setVariable('entries', $entries);
                return $view;
            }

            $query = [
                'site_id' => $site->id(),
                'sort_by' => 'modified',
                'sort_order' => 'desc',
                'limit' => 0,
            ];
            $response = $this->api()->search('item_sets', $query);
            $itemsetsCount = $response->getTotalResults();
            $itemsetsSitemapsCount = intdiv($itemsetsCount, $maxEntries) + (($itemsetsCount % $maxEntries) > 0 ? 1 : 0);

            // If $sitemapPage <= this count, return the page! Else continue
            if ($sitemapPage <= $pagesSitemapsCount + $itemsetsSitemapsCount) {
                $query = [
                    'site_id' => $site->id(),
                    'sort_by' => 'modified',
                    'sort_order' => 'desc',
                    'limit' => $maxEntries,
                    'offset' => (($sitemapPage - $pagesSitemapsCount - 1) * $maxEntries),
                ];
                $response = $this->api()->search('item_sets', $query);
                $entries = $response->getContent();
                $view->setVariable('entries', $entries);
                return $view;
            }

            $query = [
                'site_id' => $site->id(),
                'limit' => 1,
                'sort_by' => 'modified',
                'sort_order' => 'desc',
            ];
            $response = $this->api()->search('items', $query);
            $itemsCount = $response->getTotalResults();
            $itemsSitemapsCount = intdiv($itemsCount, $maxEntries) + (($itemsCount % $maxEntries) > 0 ? 1 : 0);

            // If $sitemapPage <= this count, return the page! Else continue
            if ($sitemapPage <= $pagesSitemapsCount + $itemsetsSitemapsCount + $itemsSitemapsCount) {
                $query = [
                    'site_id' => $site->id(),
                    'sort_by' => 'modified',
                    'sort_order' => 'desc',
                    'limit' => $maxEntries,
                    'offset' => (($sitemapPage - $pagesSitemapsCount - $itemsetsSitemapsCount - 1) * $maxEntries),
                ];
                $response = $this->api()->search('items', $query);
                $entries = $response->getContent();
                $view->setVariable('entries', $entries);
                return $view;
            }

            if (count($entries) == 0) {
                // Requesting a non existing page
                $this->response->setStatusCode(404);
                return;
            }
        }
    }
}
