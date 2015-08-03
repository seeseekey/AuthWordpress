AuthWordpress
=============

Wordpress Authentification module for MediaWiki

**Installation**

Just drop this file in extensions and add these few lines in your LocalSettings.php

require_once( 'extensions/AuthWordpress/AuthWordpress.php' );<br/>
$wgAuth = new AuthWordpress();<br/>
$wgAuth->setAuthWordpressTablePrefix('wp_');<br/>
$wgAuth->setAuthWordpressDBServer ('CHANGEME');    // wordpress host (eg. localhost)<br/>
$wgAuth->setAuthWordpressDBName('DBNAME');         // wordpress database<br/>
$wgAuth->setAuthWordpressUser('DBUSER');           // wordpress db username<br/>
$wgAuth->setAuthWordpressPassword('DBPASS');       // wordpress db password<br/>
