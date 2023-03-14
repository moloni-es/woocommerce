# Moloni España

![WordPress Plugin Required PHP Version](https://img.shields.io/badge/php-%3E%3D5.6-blue)
![WordPress Plugin: Tested PHP Version](https://img.shields.io/badge/php-8.1%20tested-blue)
![WordPress Plugin: Required WP Version](https://img.shields.io/badge/WordPress-%3E%3D%205.0-orange)
![WordPress Plugin: Tested WP Version](https://img.shields.io/badge/WordPress-6.1.1%20tested-orange)
![WooCommerce: Required Version](https://img.shields.io/badge/WooCommerce-%3E%3D%203.0.0-orange)
![WooCommerce: Tested Version](https://img.shields.io/badge/WooCommerce-7.3.0%20tested-orange)

![GitHub](https://img.shields.io/github/license/moloni-pt/woocommerce)

**Contributors:**      [moloni-es](https://github.com/moloni-es)  
**Homepage:**          [https://woocommerce.moloni.es/](https://woocommerce.moloni.es/)  
**Tags:**              Invoicing, Orders  
**Requires PHP:**      7.2  
**Tested up to:**      6.1.1  
**WC tested up to**    7.3.0  
**Stable tag:**        1.0.41  
**License:**           GPLv2 or later  
**License URI:**       [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

Innovative billing software that fits your business.! Intended for professionals, micro, small and medium enterprises. No initial investment, complete and intuitive.

## Description
Moloni is an innovative online billing and POS software that includes access to numerous useful and functional tools that allow each company to manage their billing, control stocks, automate processes and issue documents quickly, simply and intuitively.

Moloni is always updated with the latest features and tax changes according to the law in Spain!

### Through the plugin it is possible to:
* Synchronize products and stocks between the two platforms
* Automatic or manual document issuance
* Select the status of issued documents
* Select from a wide variety of document types
* Select the outbound item warehouse
* Automatic sending of the document to the customer
* Automatic creation of customers and articles
* Customize your billing details
* Access issued documents without leaving WordPress

All technical and commercial support given to users of the plugin is provided by the Moloni Customer Support team.

## Frequently Asked Questions

### Is there a paid version of the plugin?
No. The plugin was developed and is made available completely free.

### How much will I have to pay for support? 
Like the Moloni software, all support is completely free and provided entirely by our Moloni Customer Support team.

### I have questions or suggestions, who can I contact? 
For any doubts or suggestions you can contact us via email soporte@moloni.es.

### Documents are being issued without a taxpayer number
By default, WooCommerce does not have a taxpayer field, so what you usually do is add a plugin to add the taxpayer to the customer.

These plugins create a custom_field associated with the customer's billing address, such as  `_billing_nif`.

Once you have a plugin for the contributor installed, just select in the settings of the Moloni plugin which  `custom_field` corresponds to the client's contributor.


## Installation
This plugin can be installed via FTP or using the WordPress plugin installer.

#### Via FTP
1. Upload the plugin files to the `/wp-content/plugins/moloni_es` directory
2. Activate the plugin through the `Plugins` option visible in WordPress

## Upgrade Notice
### 1.0.0
Released plugin version 1.0.0.

## Changelog

### 1.0.41
* FIX: Fix PHP 8 erros

### 1.0.40
* FEATURE: Validate Spanish VAT numbers
* FEATURE: New way to set new customers numbers

### 1.0.39
* FIX: Fixed error on product edit in older versions of WooCommerce
* Tested up to version 7.3.0 of WooCommerce

### 1.0.38
* FIX: Small fixes and improvements
* FEATURE: New logos and banners
* FEATURE: New empty state in company select
* FEATURE: Added support for WooCommerce HPOS orders system
* Tested up to version 6.1.1 of WordPress
* Tested up to version 7.2.2 of WooCommerce

### 1.0.37
* FIX: Replaced stock quantity setter method
* Tested up to version 6.5.1 of WooCommerce

### 1.0.36
* FIX: Fix plugin menu position
* Tested up to version 5.9.3 of WordPress
* Tested up to version 6.4.0 of WooCommerce

### 1.0.35
* FIX: Fix categories association

### 1.0.34
* FIX: Removed some PHP warnings
* Tested up to version 6.3.1 of WooCommerce

### 1.0.33
* FIX: Prevent API inconsistency
* DEPRECATION: Removed synchronization with crons

### 1.0.32
* FIX: Fix customers country fetch
* Tested up to version 5.9.2 of WordPress
* Tested up to version 6.2.2 of WooCommerce

### 1.0.31
* FEATURE: New setting to set shipping load address
* FEATURE: Order shipping name is now used to set delivery method name
* Tested up to version 5.9.1 of WordPress
* Tested up to version 6.2.1 of WooCommerce

### 1.0.30
* Prevent random logouts while refreshing tokens
* Improved logs in automatic actions
* Tested up to version 5.9.0 of WordPress
* Tested up to version 6.2.0 of WooCommerce

### 1.0.29
* Fetch invisible products when searching for products
* Updated translations
* Updated logs when syncing products
* Tested up to version 5.9.0 of WooCommerce

### 1.0.28
* Fix fiscal zone verification

### 1.0.27
* Updated fiscal zone behavior

### 1.0.26
* Added option to choose where to base fiscal zone
* Fix tax fetch
* Fix documents with refunds
* Tested up to version 5.8.1 of WordPress
* Tested up to version 5.8.0 of WooCommerce

### 1.0.25
* Sync more variation fields

### 1.0.24
* Minor fix

### 1.0.23
* Changed variation getter

### 1.0.22
* Fix deprecated variants behaviour
* Tested up to version 5.8 of WordPress
* Tested up to version 5.6.0 of WooCommerce

### 1.0.21
* Fix products prices with tax value included
* Fix error with images permissions

### 1.0.20
* Fix properties name escape

### 1.0.19
* Escape invalid characters on properties names and values

### 1.0.18
* Fixed error settings variants price when syncing product with hooks

### 1.0.17
* Escaped error on discount when products were free

### 1.0.16
* Escaped error when order products were deleted

### 1.0.15
* New tool to reinstall Moloni Webhooks
* Updated jquery enqueue order

### 1.0.14
* Added support for image synchronization in both ways
* Minor stability improvements

### 1.0.13
* Fixed synchronization issue with product variants
* Fixed behaviour when saving settings
* Support for EAN synchronization

### 1.0.12
* Added missing translations
* Fixed labels in login overlay
* Enqueue jquery if missing
* Fix behaviour with bulk document creation

### 1.0.11
* Stability improvements
* Changed the way an item stocked is handled during stock synchronization
* Fixed behaviour in hooks when using WooCommerce Rest API that injected login form if plugin did not have valid account
* Fixed error when costumer email was empty
* Tested up to version 5.7 of WordPress
* Tested up to version 5.2.0 of WooCommerce
* Tested up to version 8.0 of PHP

### 1.0.10
* Improved customer language

### 1.0.9
* Improved customer number

### 1.0.7
* Added support for WebHooks
* Sync products with variants (Moloni -> WooCommerce)

### 1.0.6
* Fix buttons placement in pending orders page.
* Plugin name update
* Minor fixes

### 1.0.0
* Initial release
