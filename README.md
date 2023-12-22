# PrestaShop module for TransparentEdge CDN

[![GitHub license](https://img.shields.io/badge/license-MIT-blue)](https://github.com/NUTechnolgyInc/Transparent_Prestashop/LICENSE.md)

## About

The Prestashop **transparentedge** module allows you to invalidate TrasparentEdge CDN cache internally when updating categories, products and clear PrestaShop cache.

## Installation and configuration

1. Create local packages folder (you need to be in Prestashop root folder):

```bash
mkdir -p ./modules/transparentedge
```

2. Clone repository to created folder

```bash
git clone git@github.com:TransparentEdge/prestashop_plugin ./modules/transparentedge
```

3. Install Prestashop module

```bash
php bin/console prestashop:module install transparentedge
```

4. Run composer dumpautoload in module root folder

```bash
composer dumpautoload -d ./modules/transparentedge
```

5. Flush Prestashop cache

```bash
php bin/console cache:clear
```

Also, you can install it manually by downloading the latest release on https://github.com/TransparentEdge/prestashop_plugin/releases and uploading it through the Module manager page of your Backoffice.

## Requirements

This module requires PrestaShop 8 or higher, and it won't install on lower versions like PrestaShop 1.7 or 1.6.

## Reporting issues

You can report issues in the module's repository. [Click here to report an issue][report-issue].
