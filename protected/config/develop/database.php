<?php
return array(
    'hosts' => array(
        'db1' => array(
            'driver' => 'mysql',
            'replication' => true,
            'servers' => array(
                'm' => array(
                    'host' => 'localhost',
                    'port' => 3306,
                    'socket' => '/tmp/mysql-master.sock',
                    'dbname' => 'db_name',
                    'dbuser' => 'user_name',
                    'dbpass' => 'password',
                    'log' => array(
                            'enabled' => true,
                            'log_dir' => 'absolute_path_of_log',
                            'mode' => 'daily',
                            'slow_query' => 0.8
                    )
                ),
                's' => array(
                    array(
                        'host' => 'localhost',
                        'port' => 3307,
                        'socket' => '/tmp/mysql-slave1.sock',
                        'dbname' => 'db_name',
                        'dbuser' => 'user_name',
                        'dbpass' => 'password',
                        'log' => array(
                            'enabled' => true,
                            'log_dir' => 'absolute_path_of_log',
                            'mode' => 'daily',
                            'slow_query' => 0.8
                        )
                    ),
                    array(
                        'host' => 'localhost',
                        'port' => 3308,
                        'socket' => '/tmp/mysql-slave2.sock',
                        'dbname' => 'db_name',
                        'dbuser' => 'user_name',
                        'dbpass' => 'password',
                        'log' => array(
                            'enabled' => true,
                            'log_dir' => 'absolute_path_of_log',
                            'mode' => 'daily',
                            'slow_query' => 0.8
                        )
                    )
                ),
            )

        )
    ),
    'tables' => array(
        // tables should be defined here
    )
);
