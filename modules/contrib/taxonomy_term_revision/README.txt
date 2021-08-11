# Taxonomy term revision

## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration


## INTRODUCTION

This module stores revisions for the taxonomy terms.

Whenever users saves or updates taxonomy terms it will create new revision
for term as it works with nodes. This provides textarea field to fill a
revision log message.

Also on the taxonomy term edit page a new menu tab appears after the module
installation which will list all of the taxonomy term's revisions and provides
two operations for each taxonomy term revisions:

* revert: which will revert the revision.
* delete: which will delete the revision.

Also the module is adding some handler class for taxonomy term to
support workflow as it works for content types.


## REQUIREMENTS

Drupal 8.7.x onwards.


## INSTALLATION

* Install as you would normally. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


## CONFIGURATION

Nothing specific.
