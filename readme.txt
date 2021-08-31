=== Plugin Name ===
Moloni España
Contributors: Moloni
Tags: Invoicing, Orders
Stable tag: 1.0.21
Tested up to: 5.7
Requires PHP: 5.6
Requires at least: 5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Innovative billing software that fits your business.! Intended for professionals, micro, small and medium enterprises. No initial investment, complete and intuitive.

== Description ==
Moloni is an innovative online billing and POS software that includes access to numerous useful and functional tools that allow each company to manage their billing, control stocks, automate processes and issue documents quickly, simply and intuitively.

Moloni is always updated with the latest features and tax changes according to the law in Spain!

== Through the plugin it is possible to:  ==
* Synchronize products and stocks between the two platforms
* Automatic or manual document issuance
* Select the status of issued documents
* Select from a wide variety of document types
* Select the outbound item warehouse
* Automatic sending of the document to the customer
* Automatic creation of customers and articles
* Customize your billing details
* Access issued documents without leaving Wordpress

All technical and commercial support given to users of the plugin is provided by the Moloni Customer Support team.

== Frequently Asked Questions ==

= Is there a paid version of the plugin? =
No. The plugin was developed and is made available completely free.

= How much will I have to pay for support? =
Like the Moloni software, all support is completely free and provided entirely by our Moloni Customer Support team.

= I have questions or suggestions, who can i contact? =
For any doubts or suggestions you can contact us via email soporte@moloni.es.

= Documents are being issued without a taxpayer number=
By default, WooCommerce does not have a taxpayer field, so what you usually do is add a plugin to add the taxpayer to the customer.

These plugins create a custom_field associated with the customer's billing address, such as  `_billing_nif`.

Once you have a plugin for the contributor installed, just select in the settings of the Moloni plugin which  `custom_field` corresponds to the client's contributor.


== Installation ==
This plugin can be installed via FTP or using the Wordpress plugin installer.

Via FTP
1. Upload the plugin files to the `/wp-content/plugins/moloni_es` directory
2. Activate the plugin through the `Plugins` option visible in WordPress

== Screenshots ==
1. Main page where you can issue your pending orders documents
2. All our settings available for the plugin
3. Synchronization and query tools

== Upgrade Notice ==
= 1.0.0 =
 Released plugin version 1.0.0.

== Changelog ==
= 1.0.21 =
* Fix products prices with tax value included
* Fix error with images permissions

= 1.0.20 =
* Fix properties name escape

= 1.0.19 =
* Escape invalid characters on properties names and values

= 1.0.18 =
* Fixed error settings variants price when syncing product with hooks

= 1.0.17 =
* Escaped error on discount when products were free

= 1.0.16 =
* Escaped error when order products were deleted

= 1.0.15 =
* New tool to reinstall Moloni Webhooks
* Updated jquery enqueue order

= 1.0.14 =
* Added support for image synchronization in both ways
* Minor stability improvements

= 1.0.13 =
* Fixed synchronization issue with product variants
* Fixed behaviour when saving settings
* Support for EAN synchronization

= 1.0.12 =
* Added missing translations
* Fixed labels in login overlay
* Enqueue jquery if missing
* Fix behaviour with bulk document creation

= 1.0.11 =
* Stability improvements
* Changed the way an item stocked is handled during stock synchronization
* Fixed behaviour in hooks when using WooCommerce Rest API that injected login form if plugin did not have valid account
* Fixed error when costumer email was empty
* Tested up to version 5.7 of Wordpress
* Tested up to version 5.2.0 of WooCommerce
* Tested up to version 8.0 of PHP

= 1.0.10 =
* Improved customer language

= 1.0.9 =
* Improved customer number

= 1.0.7 =
* Added support for WebHooks
* Sync products with variants (Moloni -> WooCommerce)

= 1.0.6 =
* Fix buttons placement in pending orders page.
* Plugin name update
* Minor fixes

= 1.0.0 =
* Initial release
