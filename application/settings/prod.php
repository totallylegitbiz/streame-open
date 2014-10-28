<?

##########################################################
#
# HOST
#
##########################################################

define('BASE_DOMAIN',     'streame.butts');
define('BASE_APP_DOMAIN',  (APP_NAME != 'www'?APP_NAME . '.':'') . BASE_DOMAIN);
define('SESSION_COOKIE',  'pocky');
define('IS_DEV', false);

##########################################################
#
# Services
#
##########################################################

define('REDIS_SERVER',    'service-redis:6379');
define('MONGO_SERVER',    'service-mongo:27017');
define('QUEUE_SERVERS',   'service-queue');
define('ES_SERVER' ,      'service-es:9200');

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
define('STATIC_DOMAIN',       'cdn.'. BASE_DOMAIN);

##########################################################
#
# Reddit
#
##########################################################

define('REDDIT_API_USER',    '');
define('REDDIT_API_PASS',    '');


