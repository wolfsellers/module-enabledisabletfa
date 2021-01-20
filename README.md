# Magento 2 Module WolfSellers Enable Disable 2FA

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Tested](#markdown-header-tested)
    

## Main Functionalities

Adds enable disable feature switch for the Two-factor Authorization for Magento 2.4 

It can be configured in `Stores > Configuration > 2FA > General > Enabled` by default is set to no, so the admin can be used.
Change it back in production.

## Installation

### 1. Composer (recommended)

 - Install the module composer by running `composer require wolfsellers/module-enabledisabletfa`
 - Enable the module by running `php bin/magento module:enable WolfSellers_EnableDisableTfa`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`

### 2. Download zip (not recommended)

 - Download the zip file from github
 - Extract the files in `app/code/WolfSellers/EnableDisableTfa/`
 - Enable the module by running `php bin/magento module:enable WolfSellers_EnableDisableTfa`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`

## Tested

Tested in Magento 2.4.0, versions:
 - Community
 - Enterprise
 - Cloud 

## Toggling through CLI

```sh
php bin/magento config:set twofactorauth/general/enabled 1 # or 0
```
