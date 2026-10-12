<?php

define('ROOT_PATH', realpath(dirname(__FILE__)).'/');
define('BASEURL', '/internship_mgt/');

define('DB_HOST', 'localhost');
// Supply deployment credentials through environment variables. Never commit production secrets.
define('DB_USER', getenv('INTERNSHIP_DB_USER') ?: 'root');
define('DB_PASS', getenv('INTERNSHIP_DB_PASS') ?: '');
define('DB_NAME', 'internship');

define('CART_COOKIE', 'Sum123Am1902intern');
define('CART_COOKIE_EXPIRE', time() + (86400 * 30));
define('TAXRATE', 0.18);
