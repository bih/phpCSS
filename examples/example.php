<?php
/*
	phpCSS {
		@note: This script uses the phpCSS library to decode a CSS file. See how it works.
	}
*/

// Can't work without the library itself, ey?
include "./../php/phpCSS.php";

/* Instead of a URL, this could easily be raw CSS. No extra configuration, it detects itself. Cool, eh? */
$e = new phpCSS('http://www.dotcloud.com/static/CACHE/css/01b072508ff7.css');

// Disables super advanced decoding, to save resources.
// $e->set_rule('disable_advanced_decoding');

// Output :)
header("Content-Type: text/javascript");
print_r($e->decode());