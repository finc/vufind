[![Build Status](https://travis-ci.org/vufind-org/vufind.svg?branch=master)](https://travis-ci.org/vufind-org/vufind)
VuFind
======

Introduction
------------
VuFind is an open source discovery environment for searching a collection of
records.  To learn more, visit https://vufind.org.

Installation
------------
See our [online installation documentation](https://vufind.org/wiki/installation) for step-by-step instructions for installing from packaged releases to popular platforms.

VuFind's [packaged releases](http://vufind-org.github.io/vufind/downloads.html) have all dependencies included. If you are installing directly from a Git checkout, you will need to load these dependencies manually using the [Composer](https://getcomposer.org) tool by running `composer install` from the VuFind home directory.


Documentation and Support
-------------------------
The VuFind community maintains a detailed [wiki](http://vufind.org/wiki) containing information on using and customizing the software. The VuFind website also lists [sources of community and commercial support](http://vufind-org.github.io/vufind/support.html).


Contributing
------------
See our [developers handbook](https://vufind.org/wiki/development) for more information.

Testing
-------

For performing all ci-tasks you have to add all dev-dependencies. Do so like this

    php composer.phar update

this adds all necessary tools like phing, phpunit, etc.

After that you can perform the task with the following command:

    phing

Be aware that you will need several programs like java and mysql to setup a solr-index and a database-store in order to
test the components of vufind that communicate with them.

You might need to set up the mysql-user credentials, in case they do not agree with your mysql root-users credentials.
You can do that by providing optional parameters like that

    phing -Dmysqlrootpass=''

Do so if you have no rootpassword set. The default password is 'password'.

Modules
-------

* all non-global non-community modules go to

        module/finc/
