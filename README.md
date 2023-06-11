# omeka-s-module-Sitemaps
Module for Omeka S

This module generates dynamic individual sitemap XML files for your Omeka S sites.
Indexed URLs include items show pages, item-sets browse pages, and site pages.

The module is in early development stage and could do with more advanced usage and testing. Use at your own risk.

## Installing / Getting started

This module requires Omeka S v4

* Download and unzip in your `omeka-s/modules` directory.
* Rename the uncompressed folder to `Sitemaps`.
* Log into your Omeka-S admin backend and navigate to the `Modules` menu.
* Click `Install` next to the Sitemaps module.

## Features

This module includes the following features:

* Individual site setting to enable Sitemap generation
* Dynamically generate a sitemap.xml file for each site
* Support Sitemap indexes and paginated files, useful for large collections.

### Enable Sitemap for a site

To enable the sitemap on a site of your choice:

* Navigate to your Omeka-S admin panel.
* Click on the `Sites` menu.
* Click the pencil icon next to the site you wish to configure.
* Navigate to your site `Settings` menu.
* Check the `Enable dynamic sitemap for this site` option and save.

The `sitemap.xml` file is dynamically generated: there is no file physically created on the server.
The URL for site `site-slug` is the following: `http://myomekasite.com/s/site-slug/sitemap.xml`

### Enable Sitemap indexes

* Navigate to your Omeka-S admin panel.
* Click on the `Sites` menu.
* Click the pencil icon next to the site you wish to configure.
* Navigate to your site `Settings` menu.
* Check the `Enable sitemap index` option and save.
* Select a max number of entries for each sitemap index file (default is set to 500) and save

The `sitemap-index.xml` file and the sub-indexes are dynamically generated: there is no file physically created on the server.
The main URL for site `site-slug` is the following: `http://myomekasite.com/s/site-slug/sitemap-index.xml` with separated indexes created for pages, collections and items.


## Module configuration

There is no module specific configuration.

## Known issues

See the Issues page.

## Contributing

The module is in early development stage and could do with more advanced usage and testing. Contributions are welcome. Please use Issues and Pull Requests workflows to contribute.

## Links

Also check out my other Omeka S modules:

* RestrictedSites: https://github.com/ManOnDaMoon/omeka-s-module-RestrictedSites
* UserNames: https://github.com/ManOnDaMoon/omeka-s-module-UserNames
* RoleBasedNavigation: https://github.com/ManOnDaMoon/omeka-s-module-RoleBasedNavigation
* Sitemaps: https://github.com/ManOnDaMoon/omeka-s-module-Sitemaps
* Siteswitcher: https://github.com/ManOnDaMoon/omeka-s-module-SiteSwitcher

[![ko-fi](https://www.ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/H2H0IPOUY)

## Licensing

The code in this project is licensed under GNU GPLv3.
