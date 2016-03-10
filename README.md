# Framework (Web)
=====================
Framework for web server. A pure MVC(Model-View-Controller) build framework with
module support.

## Environment
- linux is recommanded
- PHP 5.3+

## Features
```
- MVC
- Multiple database support(Mysql, postgreSQL)
- Configurable database sharding(Partition Support)
- Master-slave support
- 1 master with N slaves framework level load banlance
- Multiple cache support(memcached, redis)
- Search engine support(solr)
- Multiple view support(html, json)
- Maintenance mode
- Simple job queue
```

## Need PHP Modules
```
- gettext
- PDO
- mysql
- memcached
- phpredis
- solr
- json
- mcrypt
```


# How to use
=====================
Before you start to use make sure to clone the framework-web library to your local
disk and make a symbolic link to your lib directory
```
directory structure
/ project folder
|-- lib                             <- framework, library folder, make sure your libraries put here
|-- protected                       <- source code
|   |-- config                      <- config
|   |   |-- common                  <- common part
|   |   |-- develop                 <- develop
|   |   |-- staging                 <- staging
|   |   |-- production              <- production
|   |
|   |-- modules                     <- modules
|   |   |-- testModule
|   |       |-- controller          <- controller
|   |       |-- model               <- business model
|   |       |-- logic               <- business logic
|   |-- plugins                     <- customize plugins
|   |-- script                      <- batch scripts
|   |-- sql                         <- sql scripts
|   |-- view                        <- view folder
|
|-- Crawler.php                     <- main Application class
|-- bootstrap.php                   <- php settings or other
|-- public                          <- open for public(Document Root)
    |-- index.php
    |-- img                         <- images files
    |-- css                         <-- css files
    |-- js                          <-- javascript files
```

example:
  assuming your framework folder is under /home/user/project/framework-web
  your project folder is /home/user/project/your_project
  1. git clone git@git.warabi-pro.jp:engineer/framework-web.git /home/user/project/framework-web
  2. git clone git@git.warabi-pro.jp:warabi-pro/engineering-crawler.git /home/user/project/your_project
  3. mkdir /home/user/project/your_project/lib
  4. ln -s /home/user/project/framework-web /home/user/project/your_project/lib/mvc

