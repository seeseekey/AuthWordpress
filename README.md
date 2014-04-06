AuthWordpress
=============

Wordpress Authentification module for MediaWiki

==Installation==

Just drop this file in extensions and add these few lines in your LocalSettings.php

require_once( 'extensions/AuthWordpress/AuthWordpress.php' );
$wgAuth = new AuthWordpress();
$wgAuth->setAuthWordpressTablePrefix('wp_');
$wgAuth->setAuthWordpressDBServer ('CHANGEME');    // wordpress host (eg. localhost)
$wgAuth->setAuthWordpressDBName('DBNAME');         // wordpress database
$wgAuth->setAuthWordpressUser('DBUSER');           // wordpress db username
$wgAuth->setAuthWordpressPassword('DBPASS');       // wordpress db password
