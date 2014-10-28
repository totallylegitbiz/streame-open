#!/usr/bin/php
<?
define('APP_NAME', 'interval');
define('DEFAULT_ERROR_HANDLER', true);
define('DELAY_LOG', false);
define('INTERVAL_START_TS', floor(time() / 60) * 60);

error_reporting( E_ALL ^ E_NOTICE);

require __DIR__ . '/../bootstrap.php';

include APPPATH . 'intervals/sites.php';

/*
include APPPATH . 'apps/www/intervals/best_of.php';
include APPPATH . 'apps/www/intervals/sites.php';
include APPPATH . 'apps/www/intervals/posts.php';
include APPPATH . 'apps/www/intervals/beta.php';
include APPPATH . 'apps/www/intervals/metrics.php';
include APPPATH . 'apps/www/intervals/cleanup.php';
*/



