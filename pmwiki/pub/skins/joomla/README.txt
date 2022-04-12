Read Me ssofb.co.uk_joomla_rhuk PmWiki Skin
-------------------------------------------

Version: 1.1
Svn Version Info: $Revision: 1304 $
Date: 14 October 2008


General Notes
-------------

Adapted by AndyG from Software Systems Open For Business (ssofb.co.uk).
Please email any bugs or sugestions to ag@ssofb.co.uk

This Skin is an adaption of a Joomla Template called rhuk_milkyway (v1.0.2) created by Andy Miller aka rhuk (rhuk@rhuk.net) so all thanks go to him for creating a great starting point.  Some changes were made to the template, mainly removing aspects of the layout, simplifying the design greatly.  Then it was ported to PmWiki skin format.  The re-purposing of open source code from one project to another is good for cross pollination of ideas and leveraging exising code.

It is completly CSS based and has a choice of six colours.

The theme is intended to be used for CMS like websites, where the majority of user are not editors, so the edit menu is in s side menu that is conditional on the user being authenticated. This is easy to remove.

This is the first skin that I've posted, so sorry if there are any issues.


Colour Selection
---------------
Set the colour in the ssofb.co.uk_joomla_rhuk.php, there is a choice of blue, black, red, green, orange and white.


Skin config
-----------
Set $Skin to ssofb.co.uk_joomla_rhuk in config.php
$Skin = 'ssofb.co.uk_joomla_rhuk';


Set the logo
------------
the variable $PageLogoUrl is the URL for a logo image, you should change this to your own logo.  The logo should not really be any bigger than 600 pixels wide or 65 pixels high.
$PageLogoUrl = "$FarmPubDirUrl/skins/ssofb.co.uk_joomla_rhuk/site_logo.gif";


Wiki Menu
---------
The theme is designed with CMS like websites in mind, where the majority of user are not editors, so the edit menu is in a side menu that is conditional on the user being authenticated. This is easy to remove. Just change the code in ssofb.co.uk_joomla_rhuk.tmpl from:
 <!--markup:
 (:if [ auth edit ] :)
 (:div1 class="module_menu":)
 (:div2:)
 (:div3:)
 (:div4:)
 !!!Wiki Menu
 (:include {$Group}.PageActions {$SiteGroup}.PageActions :)
 (:div4end:)
 (:div3end:)
 (:div2end:)
 (:div1end:)
 (:ifend:)
 -->
to:
 <div class="module_menu">
 <div>
 <div>
 <div>
 <h3>Wiki Menu</h3>
 <!--wiki: {$Group}.PageActions {$SiteGroup}.PageActions                     
 </div>
 </div>
 </div>
 </div>


Online
------
Skin Page
http://www.pmwiki.org/wiki/Cookbook/SsofbJoomlaRhuk
AndyG's page
http://www.pmwiki.org/wiki/Profiles/AndyG


History
-------
1.0.827 is the first release on 14 October 2008. Download: ssofb.co.uk_joomla_rhuk_v1.0.827.zip
	* First Release 
1.1.969 is the second release on 4 November 2008. Download: ssofb.co.uk_joomla_rhuk_v1.1.969.zip
	* Fixed some CSS issues with heading sizes, (thanks Frank)
	* Tidied up a couple of other bits. 
1.2.1304 is the third release on 1 January 2009. Download: ssofb.co.uk_joomla_rhuk_v1.2.1304.zip
	* Changed the css path from using the base tag to using $SkinDirUrl on CSS paths. This fixed a couple of issues.
	* Tidied up a couple of other bits. 