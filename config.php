<?php

define('ROOT_PATH', realpath(dirname(__FILE__)).'/');
define('BASEURL', '/internship_mgt/');

define('DB_HOST', 'localhost');
// Supply deployment credentials through environment variables. Never commit production secrets.
define('DB_USER', getenv('INTERNSHIP_DB_USER') ?: 'root');
define('DB_PASS', getenv('INTERNSHIP_DB_PASS') ?: '');
define('DB_NAME', 'internship');

// Optional live external opportunity search. Keep provider credentials in the
// server environment; never expose them to browser JavaScript.
define('JOOBLE_API_KEY', getenv('JOOBLE_API_KEY') ?: '');

// Self-hosted Matrix endpoint. Leave empty until Synapse is deployed with HTTPS.
define('MATRIX_HOMESERVER_URL', rtrim(getenv('MATRIX_HOMESERVER_URL') ?: '', '/'));
define('MATRIX_SERVER_NAME', getenv('MATRIX_SERVER_NAME') ?: '');

define('CART_COOKIE', 'Sum123Am1902intern');
define('CART_COOKIE_EXPIRE', time() + (86400 * 30));
define('TAXRATE', 0.18);
