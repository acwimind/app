<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport"
	content="width=device-width,minimum-scale=1.0, maximum-scale=1.0" />
<title>Site Name</title>
<style>
@media screen and (max-device-width:480px) {
	body {
		-webkit-text-size-adjust: none
	}
}
</style>

<!-- implement javascript on web page that first tries to open the deeplink
        1. if user has app installed, then they would be redirected to open the app to specified screen
        2. if user doesn't have app installed, then their mobile browser wouldn't recognize the URL scheme
        and app wouldn't open since it's not installed. In 1 second (1000 milliseconds) user is redirected
        to download app from app store.
     -->
<script>
    window.onload = function() {
    
<?php

// Include and instantiate the class.
require_once 'Mobile_Detect.php';
$detect = new Mobile_Detect ();

$ifinstalled='http://xxxxxxxxxxansa.it';
$ifnotinstalled="http://ansa.it";

// Se è IOS
if ($detect->isiOS ()) {
	
//	header ( "location: https://itunes.apple.com/it/app/haamble-conosci-posti-nuovi/id848231757?mt=8" );
$ifinstalled="haamble://";
$ifnotinstalled="https://itunes.apple.com/it/app/haamble-conosci-posti-nuovi/id848231757?mt=8";
}
// Se è Android
if ($detect->isAndroidOS ()) {
	
$ifinstalled="haamble://invite";
$ifnotinstalled="https://play.google.com/store/apps/details?id=com.haamble.haamblesocial";
}

?>


        window.location = '<?php
								echo ($iftinstalled);
								?>';
        setTimeout("window.location = '<?php
								echo ($ifnotinstalled);
								?>';", 1000);
    }
    </script>
</head>
<body>

</body>
</html>

