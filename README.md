# Amazon Web Services #
**Contributors:** bradt, deliciousbrains  
**Tags:** amazon, amazon web services  
**Requires at least:** 4.6  
**Tested up to:** 4.9  
**Stable tag:** 1.0.4  
**License:** GPLv3  

Houses the Amazon Web Services (AWS) PHP libraries and manages access keys. Required by other AWS plugins.

## Description ##

This plugin is required by other plugins, which use its libraries and its settings to connect to AWS services. Currently, there are only two plugins that require this plugin:

* [WP Offload S3 Lite](http://wordpress.org/plugins/amazon-s3-and-cloudfront/)
* [WP Offload S3](https://deliciousbrains.com/wp-offload-s3/?utm_campaign=WP%2BOffload%2BS3&utm_source=wordpress.org&utm_medium=free%2Bplugin%2Blisting&utm_content=AWS)

### Requirements ###

* PHP version 5.3.3 or greater
* PHP cURL library 7.16.2 or greater
* cURL compiled with OpenSSL and zlib
* curl_multi_exec enabled

## Installation ##

1. Use WordPress' built-in installer
2. A new AWS menu will appear in the side menu

## Screenshots ##

### 1. Settings screen ###
![Settings screen](https://raw.githubusercontent.com/deliciousbrains/wp-amazon-web-services/assets/screenshot-1.png)


## Changelog ##

### 1.0.4 - 2017-11-20 ###
* Improvement: Compatibility with WordPress 4.9
* Improvement: Compatibility with WP Offload S3 1.5.1
* Bug fix: Reveal access keys form option shown when keys partially defined
* Bug fix: WP_Error being passed to AWS methods
* Bug fix: "More info" links can be broken across two lines

### 1.0.3 - 2017-06-19 ###
* Improvement: Compatibility with WP Offload S3 1.5

### 1.0.2 - 2017-03-13 ###
* New: AWS SDK updated to 2.8.31
* New: London and Montreal regions added

### 1.0.1 - 2016-12-13 ###
* New: Mumbai and Seoul regions added

### 1.0 - 2016-09-29 ###
* Improvement: Compatibility with WP Offload S3 Lite 1.1
* Improvement: Compatibility with WP Offload S3 1.2

### 0.3.7 - 2016-09-01 ###
* Improvement: No longer delete plugin data on uninstall. Manual removal possible, as per this [doc](https://deliciousbrains.com/wp-offload-s3/doc/uninstall/?utm_campaign=changelogs&utm_source=wordpress.org&utm_medium=free%2Bplugin%2Blisting&utm_content=AWS).

### 0.3.6 - 2016-05-30 ###
* Improvement: Now checks that the `curl_multi_exec` function is available.

### 0.3.5 - 2016-03-07 ###
* Improvement: Support for `DBI_` prefixed constants to avoid conflicts with other plugins
* Improvement: Redesign of the Addons page
* Improvement: Compatibility with WP Offload S3 Lite 1.0
* Improvement: Compatibility with WP Offload S3 1.1

### 0.3.4 - 2015-11-02 ###
* Improvement: Compatibility with WP Offload S3 Pro 1.0.3

### 0.3.3 - 2015-10-26 ###
* Improvement: Updated Amazon SDK to version 2.8.18
* Improvement: Fix inconsistent notice widths on _Access Keys_ screen
* New: WP Offload S3 Pro addons (Enable Media Replace, Meta Slider, WPML) added to the _Addons_ screen

### 0.3.2 - 2015-08-26 ###
* New: WP Offload S3 Pro upgrade and addons added to the _Addons_ screen

### 0.3.1 - 2015-07-29 ###
* Bug fix: Style inconsistencies on the _Addons_ screen

### 0.3 - 2015-07-08 ###
* New: Support for [IAM Roles on Amazon EC2](https://deliciousbrains.com/wp-offload-s3/doc/iam-roles/?utm_campaign=changelogs&utm_source=wordpress.org&utm_medium=free%2Bplugin%2Blisting&utm_content=AWS) using the `AWS_USE_EC2_IAM_ROLE` constant
* New: Redesigned _Access Keys_ and _Addons_ screens
* Improvement: _Settings_ menu item renamed to _Access Keys_
* Improvement: _Access Keys_ link added to plugin row on _Plugins_ screen
* Improvement: Activate addons directly from within _Addons_ screen
* Improvement: [Quick Start Guide](https://deliciousbrains.com/wp-offload-s3/doc/quick-start-guide/?utm_campaign=changelogs&utm_source=wordpress.org&utm_medium=free%2Bplugin%2Blisting&utm_content=AWS) documentation

### 0.2.2 - 2015-01-19 ###
* Bug Fix: Reverting AWS client config of region and signature

### 0.2.1 - 2015-01-10 ###
* New: AWS SDK updated to 2.7.13
* New: Translation ready
* Improvement: Code cleanup to WordPress coding standards
* Improvement: Settings notice UI aligned with WordPress style
* Bug: Error if migrating keys over from old Amazon S3 and CloudFront plugin settings

### 0.2 - 2014-12-04 ###
* New: AWS SDK updated to 2.6.16
* New: Set the region for the AWS client by defining `AWS_REGION` in your wp-config.php
* New: Composer file for Packagist support
* Improvement: Base plugin class performance of installed version
* Improvement: Base plugin class accessor for various properties
* Improvement: Addon plugin modal now responsive
* Improvement: Better menu icon
* Improvement: Code formatting to WordPress standards

### 0.1 - 2013-09-20 ###
* First release
