============================
Animate Slider block
============================

Description
============
Animate Slider block module integrates the Animate Slider jquery Plugin with
Drupal 8 block. Basic animation has attached to the all elements for all slide
items. Multiple instance of slider can be created for different pages which is
a big credit to Drupal 8 block system(A single slider block can be used 
multiple times with different configurations in same/different regions).


Installation
============
1. Copy the module in sites/all/module or modules directory and install it. 
2. Create different term page in Slider taxonomy. 
3. Create the slide items in the add content section(node/add/slider). 
4. Place the animate slider block in your require regions.

Requirements
============
Download the Animate slider and Modernizr , rename the folder as 
'animate-slider' and 'modernizr' respectively and place it under your libraries
folder. So your file structure should look like this: 

[drupal_root]/libraries/animate-slider/js/jquery.animateSlider.js,
URL:https://github.com/Vchouliaras/jquery.animateSlider.js/tree/master/src

[drupal_root]/libraries/animate-slider/css/jquery.animateSlider.css,
URL:https://github.com/vchouliaras/jquery.animateSlider.js

[drupal_root]/libraries/modernizr/js/modernizr.js
URL:https://cdnjs.com/libraries/modernizr

Demo:
============
Home page URL: http://drup8demovdjm2cyo7u.devcloud.acquia-sites.com/
Service page : http://drup8demovdjm2cyo7u.devcloud.acquia-sites.com/services

Uninstallation
===============
1. Disable the module from 'administer >> modules'.
2. Uninstall the module


MAINTAINERS
============
https://www.drupal.org/u/bapi_22
