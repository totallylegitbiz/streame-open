class streame-webapp {

  include streame-webapp::php
  include streame-webapp::code
  include streame-webapp::nginx
  include streame-webapp::worker
  
  package {[mysql-client]:
    ensure => installed
  }
  
}

class streame-webapp::devstack {

  include streame-webapp
  include mariadb
  include redis
  include elasticsearch
  include gearman::server
  include mongodb::server 
  #include predictionio::server
  
  package {["ruby-compass"]:
    ensure => installed
  }
  
  host { 'service-mysql':
    ip => '127.0.0.1',
    host_aliases => [ 'service-redis', 'service-queue', 'service-es' ],
  }
 
  exec { "streame-db": 
    command  => "mysql -u root -e \"CREATE DATABASE streame\";
     mysql -u root -e \"CREATE USER 'app'@'localhost' IDENTIFIED BY 'steverosado'\";
     mysql -u root -e \"GRANT ALL PRIVILEGES ON streame.* TO 'app'@'localhost'\";
     mysql -u root streame < /usr/src/streame/application/sql/schema.sql
    ",
    creates => "/var/lib/mysql/streame/",
    path    => ["/usr/bin", "/bin"],
    require => [Package["mariadb-server"],Exec["streame-webapp-code"]]
  }

}


class streame-webapp::dev {

 exec { "dev-tools":
    command  => "add-apt-repository -y ppa:chris-lea/node.js;
apt-get -y purge node nodejs;
apt-get update;
apt-get -y install rubygems librb-inotify-ruby nodejs;
npm install coffee-script jslint -g;
gem install compass",
    creates  => "/etc/apt/sources.list.d/chris-lea-node_js-precise.list",
    path     => ["/usr/bin", "/bin"],
    timeout  => 0,
    #refreshonly => true
  }
  
}

class streame-webapp::nginx {

  package {["nginx"]:
    ensure => installed
  }
  
  service { ["apache2"]:
    ensure => "stopped",
  }  
  
  service { ["nginx"]:
    ensure   => "running",
    require  => Package["nginx"],
  }  

  file { "/etc/nginx":
    ensure   => directory, # so make this a directory
    recurse  => true, # enable recursive directory management
    purge    => true, # purge all unmanaged junk
    force    => true, # also purge subdirs and links etc.
    owner    => "root",
    group    => "root",
    mode     => 0644,
    source   => "puppet:///modules/streame-webapp/nginx",
    require  => Package["nginx"],
    notify   => Service["nginx"] 
  }

}


class streame-webapp::php {

  package {[php-apc,php-pear,php5-cli,php5-common,php5-curl,php5-dev,php5-fpm,php5-gd,php5-geoip,php5-mysql,libgearman-dev]:
    ensure => installed,
    require => Exec["php55-install"],
  }

  service { ["php5-fpm"]:
    ensure   => "running",
    require  => Exec["php55-install"],
  }  
  
 exec { "php55-install":
    command  => "add-apt-repository ppa:ondrej/php5;apt-get update && apt-get remove -y php-apc php-pear php5-cli php5-common php5-curl php5-dev php5-fpm php5-gd php5-geoip php5-mysql &&
apt-get install -y php-apc php-pear php5-cli php5-common php5-curl php5-dev php5-fpm php5-gd php5-geoip php5-mysql
",
    creates  => "/usr/bin/php",
    path     => ["/usr/local/sbin","/usr/local/bin","/usr/sbin","/sbin","/usr/bin", "/bin"],
    timeout  => 0,
    #refreshonly => true
  }
  
  file { "/etc/php5":
    ensure   => directory, # so make this a directory
    recurse  => true, # enable recursive directory management
    purge    => true, # purge all unmanaged junk
    force    => true, # also purge subdirs and links etc.
    owner    => "root",
    group    => "root",
    mode     => 0644,
    source   => "puppet:///modules/streame-webapp/php5",
    require  => Exec["php55-install"],
    notify   => Service["php5-fpm"]
  }
  
  exec { "php-modules":
    command  => "pecl install -f gearman && pecl install -f mongo",
    creates  => "/usr/lib/php5/20121212/mongo.so",
    path     => ["/usr/bin", "/bin","/usr/sbin/"],
    timeout  => 0,
    require  => Exec["php55-install"],
  }
  
}


class streame-webapp::code {

  file {"/var/streame":
    ensure => "directory",
    owner  => "devuser",
    group  => "devuser",
    mode   => 0755,
    recurse => true
  }
  
  file {"/etc/profile.d/streame.sh":
    ensure => "file",
    owner  => "root",
    group  => "root",
    mode   => 0755,
    source => "puppet:///modules/streame-webapp/profile.d/streame.sh",
  }
    
  
  exec { "streame-webapp-code":
    command  => "sudo -u devuser git clone git@github.com:totallylegit-co/streame.git /usr/src/streame",
    creates  => "/usr/src/streame/.git",
    path     => ["/usr/bin", "/bin","/usr/sbin/"],
    timeout  => 0,
  }
  
  file {["/var/lib/streame","/var/lib/streame/logs","/var/lib/streame/cache","/var/lib/streame/templates_c"]:
    ensure => "directory",
    owner  => "www-data",
    group  => "www-data",
    mode   => 0777
  }
  
  cron { interval:
    command => "/usr/src/streame/application/workers/interval.php",
    user    => www-data,
    #minute  => 0
  }

}

class streame-webapp::worker {
  include gearman::server
  include streame-webapp::php
  include streame-webapp::code
  
  file { "/etc/init/app-task.conf":
    ensure   => file, # so make this a directory
    owner    => "root",
    group    => "root",
    mode     => 0644,
    source   => "puppet:///modules/streame-webapp/init/app-task.conf",
  }

  service { ["app-task"]:
    ensure  => "running",
    require => File["/etc/init/app-task.conf"]
  }  
}
