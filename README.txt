CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How it works
 * Support requests
 * Maintainers

INTRODUCTION
------------

Social Auth Facebook is a Facebook authentication integration for Drupal. It is
based on the Social Auth and Social API projects, as well as in the Simple FB
Connect module.

It adds to the site:

 * A new url: /user/login/facebook
 * A settings form on /admin/config/social-api/social-auth/facebook page
 * A Facebook logo in the Social Auth Login block.

REQUIREMENTS
------------

This module requires the following modules:

 * Social Auth (https://drupal.org/project/social_auth)
 * Social API (https://drupal.org/project/social_api)

HOW IT WORKS
------------

User can click on the Facebook logo on the Social Auth Login block
You can also add a button or link anywhere on the site that points
to /user/login/facebook, so theming and customizing the button or link
is very flexible.

When the user opens the /user/login/facebook link, it automatically takes
user to Facebook for authentication. Facebook then returns the user to
Drupal site. If we have an existing Drupal user with the same email address
provided by Facebook, that user is logged in. Otherwise a new Drupal user is
created.

INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require "drupal/social_auth_facebook:~2.0"

 * Install the dependencies: Social API and Social Auth.

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

 * Add your Facebook app OAuth2 information in Configuration »
   User Authentication » Facebook.

 * Place a Social Auth Login block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.

SUPPORT REQUESTS
----------------

Before posting a support request, check Recent log entries at
admin/reports/dblog

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/social_auth_facebook

When posting a support request, please inform if you were able to see any errors
in Recent log entries.

MAINTAINERS
-----------

Current Maintainers:

 * Getulio Valentin Sánchez (gvso) - https://www.drupal.org/u/gvso
 * Himanshu Dixit (himanshu-dixit) - https://www.drupal.org/u/himanshu-dixit
