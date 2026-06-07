<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'serbisyo_bot_db');
define('DB_PORT', 3306);


// ================================================================================================== reminder: aws_access_key and aws_secret_key
define('AWS_REGION',           getenv('AWS_REGION')           ?: 'ap-southeast-1');
define('AWS_ACCESS_KEY_ID',    getenv('AWS_ACCESS_KEY_ID')    ?: 'YOUR_AWS_ACCESS_KEY_ID');
define('AWS_SECRET_ACCESS_KEY',getenv('AWS_SECRET_ACCESS_KEY')?: 'YOUR_AWS_SECRET_ACCESS_KEY');

define('BEDROCK_MODEL_ID', 'anthropic.claude-3-sonnet-20240229-v1:0');

define('APP_NAME',    'SerbisyoBot');
define('APP_TAGLINE', 'Tanong Mo, Sasagutin Ko!');
define('LGU_NAME',    'Marawi City Government');
define('LGU_REGION',  'BARMM, Philippines');

define('DEFAULT_TENANT_SLUG', 'marawi-city');

define('SUPPORTED_LANGUAGES', ['en' => 'English', 'fil' => 'Filipino', 'mrw' => 'Meranao']);
define('DEFAULT_LANGUAGE',    'fil');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = DEFAULT_LANGUAGE;
}
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['lang'] = $_GET['lang'];
}
define('CURRENT_LANG', $_SESSION['lang']);
