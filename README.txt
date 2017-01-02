SOCIAL AUTH FACEBOOK MODULE

INTRODUCTION
------------

Social Auth Facebook Module is a Facebook Authentication integration for Drupal.
It is based on the Social Auth and Social API projects, as well as in the
Simple FB Connect module.

It adds to the site:
* A new url: /user/login/facebook
* A settings form on /admin/config/social-api/social-auth/facebook page
* A Facebook Logo in the Social Auth Login block.

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
user to Facebook for authentication. Google then returns the user to
Drupal site. If we have an existing Drupal user with the same email address
provided by Facebook, that user is logged in. Otherwise a new Drupal user is
created.

SETUP
-----

Installation instructions for Drupal 8 can be found at
https://www.drupal.org/node/2642974


SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Before posting a support request, check Recent log entries at
admin/reports/dblog

When posting a support request, please inform if you were able to see any errors
in Recent log entries.
