<?php
/*		
    This is the bootstap for the entire application

    Bantingan Framework v0.3
    Copyright (C) 2020 by Susilo Nurcahyo
    susilonurcahyo@gmail.com
    
    Some library are copyright to their respective owners.

	Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.	

	This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.

	Want to thank me?
	- send me email, let me know how you using it,
	- want to thank more? consider buying me a coffe,
	- still not enough? let's discuss
	Thank you for your support!
*/
// set error reporting level, all for development phase
error_reporting(E_ALL);
// only show error
//error_reporting(E_ERROR);
// turn off reporting
//error_reporting(0);
// check for minimum PHP version
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    exit('Sorry, this application require PHP7 !');
} else {
    require_once('bantingan/Starter.php');   
}
?>