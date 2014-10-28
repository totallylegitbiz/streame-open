<?

##########################################################
#
# HOST
#
##########################################################

define('BASE_DOMAIN',      gethostname());
define('BASE_APP_DOMAIN',  (APP_NAME != 'www'?APP_NAME . '.':'') . BASE_DOMAIN);
define('SESSION_COOKIE',   'dev_tlc_' . str_replace('.', '_', BASE_DOMAIN));

define('BASE_API_ENDPOINT',  'http://api.' . BASE_DOMAIN);

define('IS_DEV', true);

preg_match('/^([^\.]+)/', BASE_DOMAIN, $matches);
define('DEV_EMAIL', $matches[0] . '+dev@strea.me');

##########################################################
#
# Services
#
##########################################################

define('REDIS_SERVER',    '');
define('MONGO_SERVER',    '');
define('QUEUE_SERVERS',   '');
define('ES_SERVER' ,      '');

#Prediction.io
define('PREDICTIONIO_API_ENDPOINT', '');
define('PREDICTIONIO_API_KEY',      '');

##########################################################
#
# AWS
#
##########################################################


define('AWS_ACCESS_ID',      '');
define('AWS_ACCESS_KEY',     '');
define('AWS_ASSOCIATE_TAG',  '');
define('AWS_S3_BUCKET',      '');

define('AWS_S3_BUCKET_BASE',  BASE_DOMAIN);

switch (BASE_DOMAIN) {
  case 'dev.totallylegit.butts';
    define('STATIC_DOMAIN',       'static.'. BASE_DOMAIN); 
    break;
  default:
    define('STATIC_DOMAIN',       'static.'. BASE_DOMAIN);
}

##########################################################
#
# Reddit
#
##########################################################

define('REDDIT_API_USER',    '');
define('REDDIT_API_PASS',    '');

##########################################################
#
# Google Analytics
#
##########################################################

/* define('GA_ACCOUNT_KEY', 'x'); */


##########################################################
#
# Diffbot
#
##########################################################

/* define('DIFFBOT_API_TOKEN','x'); */


##########################################################
#
# Yahoo
#
##########################################################

define('YAHOO_CONSUMER_KEY',   '');
define('YAHOO_CONSUMER_SECRET','');


