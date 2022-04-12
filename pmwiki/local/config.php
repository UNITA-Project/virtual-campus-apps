<?php if (!defined('PmWiki')) exit();
$WikiTitle = "Brainless";
#$PageLogoUrl = "http://example.com/mylogo.gif";

# Uncomment these if needed
#$ScriptUrl = 'http://example.com/pmwiki/pmwiki.php';
#$PubDirUrl = 'http://example.com/pmwiki/pub';

$DefaultPasswords['admin'] = crypt('12345');

$EnableUpload = 1;
$DefaultPasswords['upload'] = crypt('upload');

XLPage('fr','PmWikiZhTw.XLPage');

$Skin = 'soma';
$UploadMaxSize = '100000000';

include_once("$FarmD/cookbook/sourceblock.php");

putenv("TZ=EST5EDT");
$TimeFmt = '%B %d, %Y, at %I:%M %p EST';
?>
