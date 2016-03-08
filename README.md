[![Build Status](https://travis-ci.org/vufind-org/vufind.svg?branch=master)](https://travis-ci.org/vufind-org/vufind)
VuFind
======

Introduction
------------
VuFind is an open source discovery environment for searching a collection of
records.  To learn more, visit https://vufind.org.

Installation
------------

See online documentation at https://vufind.org/wiki/installation

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
