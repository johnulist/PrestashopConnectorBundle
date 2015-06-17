# PrestashopConnectorBundle for Akeneo

Welcome on the Akeneo PIM Prestashop connector bundle.

This repository is issued to develop the Prestashop Connector for Akeneo PIM.

The project is in an very early stage, not ready for production

[![Build Status](https://travis-ci.org/sdiaz/PrestashopConnectorBundle.png?branch=master)](https://travis-ci.org/sdiaz/PrestashopConnectorBundle)

This is a **PORT** from [MagentoConnectorBundle](https://github.com/akeneo/MagentoConnectorBundle) for Prestashop. .
Based on *MagentoConnectorBundle* contributed by :

- Antoine Guigan <antoine@akeneo.com>
- Damien Carcel (https://github.com/damien-carcel)
- Gildas Quemener <gildas@akeneo.com>
- Julien Sanchez <julien@akeneo.com>
- Olivier Soulet <olivier.soulet@akeneo.com>
- Romain Monceau <romain@akeneo.com>
- Willy Mesnage <willy.mesnage@akeneo.com>

# Summary

 * [Requirements](#requirements)
 * [How to install Prestashop connector in Akeneo ?](#installation-instructions)
   * [On a PIM standard for production](#installing-the-prestashop-connector-in-an-akeneo-pim-standard-installation)
   * [On a PIM dev for development](#installing-the-prestashop-connector-in-an-akeneo-pim-development-environment-master)
   * [Get demonstration data](#demo-fixtures)
 * [How to configure Prestashop to work with connector ?](#Prestashop-side-configuration)
 * [User guide](./Resources/doc/userguide.md)
 * [Advanced connector configuration](./Resources/doc/fields_list.md)
 * [Bugs and issues](#bug-and-issues)
 * [Troubleshooting section](./Resources/doc/troubleshooting.md)
 * [Actions not supported](./Resources/doc/userguide.md#not-supported)
 
# Requirements

 - php5-xml
 - Akeneo PIM CE 1.2.x stable or PIM CE 1.3.x stable
 - Prestashop from 1.4 to 1.6
 - MongoDB (optional)

# Installation instructions

## Installing the Prestashop Connector in an Akeneo PIM standard installation

If not already done, install Akeneo PIM (see [this documentation](https://github.com/akeneo/pim-community-standard)).

The PIM installation directory where you will find `app`, `web`, `src`, ... is called thereafter `/my/pim/installation/dir`.

Get composer:

    $ cd /my/pim/installation/dir
    $ curl -sS https://getcomposer.org/installer | php

Install the PrestashopConnector with composer:

    $ php composer.phar require sdiaz/prestashop-connector-bundle:*

Enable the bundle in the `app/AppKernel.php` file, in the `registerBundles` function just before the `return $bundles` line:

    $bundles[] = new Pim\Bundle\PrestashopConnectorBundle\PimPrestashopConnectorBundle();

You can now update your database:

    php app/console doctrine:schema:update --force

Don't forget to reinstall pim assets, then clear the cache:

    php app/console pim:installer:assets
    php app/console cache:clear --env=prod

Finally you can restart your apache server:

    service apache2 restart

## Installing the Prestashop Connector in an Akeneo PIM development environment (master)

The following installation instructions are meant for development on the Prestashop connector itself, and should not be used in production environments. Start by setting up a working installation as previously explained, but use de dev-master version:

    $ php composer.phar require sdiaz/prestashop-connector-bundle:dev-master

Then clone the git repository of the Prestashop connector bundle anywhere on your file system, and create a symbolic link to the vendor folder of your Akeneo installation's (after renaming/deleting the original one).

You can now update your database and reinstall pim assets as explained previously.

## Demo fixtures

To test the connector with the minimum data requirements, you can load the demo fixtures. Change the `installer_data` line from the `app/config/parameters.yml` file to:

    installer_data: PimPrestashopConnectorBundle:demo_prestashop

Two locales are activated by default, so for the export jobs to work out of the box, you need to add an extra storeview to your Prestashop environment, and map this store view with the Akeneo `fr_FR` locale.


# Prestashop side configuration

In order to export products to Prestashop, a Webservice token has to be created on Prestashop.

For that, in the Prestashop Admin Panel, access `Advanced Parameters > Webservice`, then click on `+` button. Create a key, and add set the resource permissions for the key by selecting `All` in resources permissions.

Finally enable PrestaShop's webservice.

After that you can go to `Spread > Export profiles` on Akeneo PIM and create your first Prestashop export job. For more informations, go take a look to the [user guide](./Resources/doc/userguide.md).

# Bug and issues

This bundle is still under active development. Expect bugs and instabilities. Feel free to report them on this repository's [issue section](https://github.com/akeneo/PrestashopConnectorBundle/issues).

# Troubleshooting

You can find solutions for some common problems in the [troubleshooting section](./Resources/doc/troubleshooting.md).