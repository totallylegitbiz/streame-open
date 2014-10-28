Strea.me
========

Strea.me is a news aggregator that learns what topics are most important to you. 
As you read articles on Strea.me you can vote articles, sources, topics up and 
down to help train the algorithm. Or you can just click on the articles you 
like and it does it all for you. 

Since it aggregates across hundreds of news sources, you can quickly 
search topics and find what’s trending very quickly.

It even works when you’re not logged in!

What Happened?
==============

We got pretty far but never launched. We were able to pull all the articles, 
tailor them towards the user. Unfortunately, it became a monolithic app that 
was painful to work on and it no longer became fun. We still love the 
idea of auto suggesting news but maybe this wasn’t the best route.

Tech
====

streame is primarially built on Kohana (originally with 3.3.0) with a AngularJS frontend.

The backend also relies on Redis, gearman, MariaDB, Elasticsearch, and 
PredictionIO. Config files are provided for nginx and MariaDB, however this project
should work on similar web servers and databases without problem.

The frontend code was written in CoffeScript and requires node.js to compile back
in to JavaScript.

## Install

There is a well fleshed puppet recipe in /config/init.pp. For a more 
hands on install, this will get you fairly close to running on a
fresh Ubuntu installation.

#### Basic Installs (php/nginx/mariadb)
```
add-apt-repository -y ppa:ondrej/php5
add-apt-repository -y ppa:gearman-developers/ppa
add-apt-repository -y ppa:nginx/stable

apt-get install -y php-apc php-pear php5-cli php5-common php5-curl 
apt-get install -y php5-dev php5-fpm php5-geoip php5-mysql nginx apache2-utils 

apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xcbcb082a1bb943db
add-apt-repository 'deb http://ftp.osuosl.org/pub/mariadb/repo/5.5/ubuntu precise main'
apt-get install mariadb-server
```

#### Node Setup
```
add-apt-repository -y ppa:chris-lea/node.js
apt-get -y install nodejs ruby-dev rubygems npm
npm install coffee-script jslint -g
gem install compass
```

#### Checkout Repo
```
git@github.com:totallylegitbiz/streame.git
```

#### Configs
```
ln -s /path/to/streame/configs/nginx-conf /install/dir/sites-enabled
```

#### Database Setup
```sql
CREATE DATABASE streame;
CREATE USER 'app'@'localhost' IDENTIFIED BY 'shibby';
GRANT ALL PRIVILEGES ON streame.* TO 'app'@'localhost';
```

Also import the sql schemas in /application/sql/tables.
