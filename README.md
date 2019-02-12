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
- multiple database support(Mysql, postgreSQL)
- configurable database sharding
- master-slave support
- 1 master with N slaves load banlance
- multiple cache support(memcached, redis)
- search engine support(solr)
- multiple language
- multiple output(html, json)
- maintenance time setable
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
|-- lib             <- framework, library folder, make sure your libraries put here
|-- protected       <- source code
|   |-- config      <- config
|   |   |-- common  <- common part
|   |   |-- develop <- develop
|   |   |-- staging <- staging
|   |   |-- production <- production
|   |
|   |-- modules      <- modules
|   |   |-- testModule
|   |       |-- controller
|   |       |-- model
|   |       |-- logic
|   |-- plugins      <- customize plugins
|   |-- script       <- batch scripts
|   |-- sql          <- sql scripts
|   |-- view         <- view folder 
|
|-- Crawler.php      <- main class
|-- bootstrap.php    <- php settings or other
|-- public           <- open for public
    |-- index.php
    |-- img          <- images files
    |-- css          <-- css files
    |-- js           <-- javascript files
```

example:
  assuming your framework folder is under /home/user/project/framework-web
  your project folder is /home/user/project/your_project
  1. git clone git@git.warabi-pro.jp:engineer/framework-web.git /home/user/project/framework-web
  2. git clone git@git.warabi-pro.jp:warabi-pro/engineering-crawler.git /home/user/project/your_project
  3. mkdir /home/user/project/your_project/lib
  4. ln -s /home/user/project/framework-web /home/user/project/your_project/lib/mvc

