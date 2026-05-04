<?php

namespace Sitemaps\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Handles the install-root global sitemap routes:
 *   GET /sitemap.xml       → redirects to /sitemapindex.xml (when enabled)
 *   GET /sitemapindex.xml  → sitemap index listing one entry per enabled site
 *
 * Each entry points to that site's own sitemapindex.xml (if the site has
 * indexing enabled) or its sitemap.xml (if not). This is a nested
 * index-of-indexes pattern which Google tolerates in practice, though it is
 * technically outside the Sitemap Protocol spec.
 *
 * This controller has no site context — it must NOT call currentSite().
 */
class GlobalSitemapsController extends AbstractActionController
{
    /**
     * GET /sitemap.xml
     *
     * When the global sitemap is enabled, redirect to /sitemapindex.xml.
     * Otherwise return 404.
     */
    public function globalSitemapAction()
    {
        // [CHANGE] Read the global toggle from module-level settings, not site settings.
        $settings = $this->settings();
        $globalEnabled = (bool) $settings->get('sitemaps_enable_global', false);

        if (!$globalEnabled) {
            $this->response->setStatusCode(404);
            return;
        }

        // [CHANGE] Redirect /sitemap.xml → /sitemapindex.xml, mirroring the
        // per-site behaviour where sitemap.xml redirects to sitemapindex.xml
        // when indexing is active.
        // [CHANGE] Route name uses hyphens — Laminas treats slashes as a
        // parent/child separator so 'sitemaps/global-sitemapindex' would cause
        // a "Route with name sitemaps not found" RuntimeException.
        return $this->redirect()->toRoute('sitemaps-global-sitemapindex');
    }

    /**
     * GET /sitemapindex.xml
     *
     * Builds a sitemap index whose entries are the per-site sitemap roots for
     * every site that has sitemaps_enablesitemap = true.
     */
    public function globalSitemapIndexAction()
    {
        // [CHANGE] Read the global toggle from module-level settings.
        $settings = $this->settings();
        $globalEnabled = (bool) $settings->get('sitemaps_enable_global', false);

        if (!$globalEnabled) {
            $this->response->setStatusCode(404);
            return;
        }

        // [CHANGE] Fetch all sites through the API — no site context available here.
        $api = $this->api();
        $sites = $api->search('sites', [])->getContent();

        // [CHANGE] Retrieve site-level settings service so we can check each
        // site's individual sitemaps_enablesitemap flag.
        $siteSettings = $this->siteSettings();

        $sitemaps = [];

        foreach ($sites as $site) {
            // [CHANGE] Switch the site settings context to this site before reading.
            $siteSettings->setTargetId($site->id());

            $hasSitemap = (bool) $siteSettings->get('sitemaps_enablesitemap', false);

            // Only include sites that have explicitly enabled their sitemap.
            if (!$hasSitemap) {
                continue;
            }

            $hasIndex = (bool) $siteSettings->get('sitemaps_enableindex', false);

            // [CHANGE] Point to the site's sitemapindex.xml when it has indexing
            // on, otherwise point directly to its sitemap.xml.
            if ($hasIndex) {
                $sitemapUrl = $site->siteUrl($site->slug(), true) . '/sitemapindex.xml';
            } else {
                $sitemapUrl = $site->siteUrl($site->slug(), true) . '/sitemap.xml';
            }

            // [CHANGE] Use the site's most-recently-modified item/page as the
            // lastmod date. We fall back to today if nothing is found.
            $lastmod = $this->getLastModForSite($site->id());

            $sitemaps[] = [
                'url'     => $sitemapUrl,
                'lastmod' => $lastmod,
            ];
        }

        /** @var ViewModel $view */
        $view = new ViewModel();
        // [CHANGE] Reuse the existing sitemap-index template — it only needs a
        // $sitemaps array of ['url' => ..., 'lastmod' => ...] entries.
        $view->setTemplate('site/sitemap-index');
        $view->setVariable('sitemaps', $sitemaps);
        $view->setTerminal(true);

        return $view;
    }

    /**
     * Returns the most recent modified date (Y-m-d) across items, item sets,
     * and pages for the given site, falling back to today's date.
     *
     * @param int $siteId
     * @return string  e.g. "2024-03-15"
     */
    private function getLastModForSite(int $siteId): string
    {
        $api = $this->api();
        $baseQuery = [
            'site_id'    => $siteId,
            'sort_by'    => 'modified',
            'sort_order' => 'desc',
            'limit'      => 1,
        ];

        $latest = null;

        // Check items, item_sets and site_pages — take whichever modified date
        // is most recent across all three resource types.
        foreach (['items', 'item_sets', 'site_pages'] as $resourceType) {
            $content = $api->search($resourceType, $baseQuery)->getContent();
            if (!empty($content)) {
                $modified = $content[0]->modified();
                if ($modified !== null) {
                    $ts = $modified->getTimestamp();
                    if ($latest === null || $ts > $latest) {
                        $latest = $ts;
                    }
                }
            }
        }

        if ($latest === null) {
            return (new \DateTime())->format('Y-m-d');
        }

        return (new \DateTime('@' . $latest))->format('Y-m-d');
    }
}
