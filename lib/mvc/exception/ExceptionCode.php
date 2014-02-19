<?php

/**
 * ExceptionCode
 * =======================================================
 * exception codes
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace exception;

/**
 * <pre>
 * +--------------------+------------------------------------------------------------------------+
 * |     type           |    detail                                                              |
 * +--------------------+------------------------------------------------------------------------+
 * | Db                 | Database error                                                         |
 * | RemoteAccess       | communication error with remote server                                 |
 * | InvalidData        | invalide data                                                          |
 * | Implementation     | implementation error                                                   |
 * | DefensiveException | maybe from outside attack                                              |
 * +--------------------+------------------------------------------------------------------------+
 * </pre>
 * code value range:
 * <pre>
 * +---------------+----------------------------------+
 * |   type        |     name       |        range    |
 * +---------------+----------------+-----------------+
 * |               |      Db        |  2000 ~ 2999    |
 * |               |      I/O       |  3000 ~ 3999    |
 * |    Low        |     Cache      |  4000 ~ 4999    |
 * |               |     View       |  5000 ~ 5499    |
 * +---------------+----------------+-----------------+
 * | Business      |                |  10000 ~        |
 * +---------------+----------------+-----------------+
 *
 * @package core
 */
class ExceptionCode {

    /**
     * application error
     * @var int
     */
    const DEFAULT_APP_ERROR = 0;
    /**
     * Db default error
     * @var int
     */
    const DB_DEFAULT_ERROR = 2000;
    /**
     * Db access error
     * @var int
     */
    const DB_ACCESS_ERROR = 2001;
    /**
     * Insert data error
     * @var int
     */
    const DB_INSERT_ERROR = 2002;
    /**
     * Update data error
     * @var int
     */
    const DB_UPDATE_ERROR = 2003;
    /**
     * Delete data error
     * @var int
     */
    const DB_DELETE_ERROR = 2004;
    /**
     * unknown table column
     * @var
     */
    const DB_UNKNOWN_COLUMN = 2005;
    /**
     * Db not support
     * @var int
     */
    const DB_NOT_SUPPORT = 2006;
    /**
     * Db connection object error
     * @var int
     */
    const DB_ACCESS_OBJ_ERROR = 2007;
    /**
     * Db execute error
     * @var int
     */
    const DB_EXECUTE_ERROR = 2008;
    /**
     * cache default error
     * @var int
     */
    const CACHE_DEFAULT_ERROR = 4000;
    /**
     * access cache server error
     * @var int
     */
    const CACHE_ACCESS_ERROR = 4001;
    /**
     * Insert cache data error
     * @var int
     */
    const CACHE_INSERT_ERROR = 4002;
    /**
     * Update cache data error
     * @var int
     */
    const CACHE_UPDATE_ERROR = 4003;
    /**
     * Delete cache data error
     * @var int
     */
    const CACHE_DELETE_ERROR = 4004;
    /**
     * Delete cache data error
     * @var int
     */
    const CACHE_NOT_SUPPORT = 4005;
    /**
     * Cache connection object error
     * @var int
     */
    const CACHE_ACCESS_OBJ_ERROR = 4006;
    /**
     * I/O default error
     * @var int
     */
    const IO_DEFAULT_ERROR = 3000;
    /**
     * I/O resource not stream
     * @var int
     */
    const IO_RESOURCE_NOT_STREAM = 3001;
    /**
     * View
     * @var int
     */
    const VIEW_DEFAULT_ERROR = 5000;
    /**
     * View
     * @var int
     */
    const VIEW_TYPE_NOT_SUPPORT = 5001;
    /**
     * View
     * @var int
     */
    const VIEW_TEMPLATE_NOT_FOUND = 5002;
    /**
     * View
     * @var int
     */
    const VIEW_CONFIG_FILE_NOT_FOUND = 5003;
    /**
     * Nosql
     * @var int
     */
    const NOSQL_DEFAULT_ERROR = 6000;
    /**
     * Nosql
     * @var int
     */
    const NOSQL_CONFIG_ERROR = 6001;
    /**
     * Nosql
     * @var int
     */
    const NOSQL_CONNECTION_ERROR = 6002;
    /**
     * Socket
     * @var int
     */
    const SOCKET_DEFAULT_ERROR = 7000;


    // ----------------------------- BUSINESS LEVEL --------------------------------------------
    /**
     * config file not exist
     * @var
     */
    const CONFIG_FILE_NOT_EXIST = 15000;
    /**
     * config file data error
     * @var
     */
    const CONFIG_FILE_DATA_ERROR = 15001;
    /**
     * config file syntax error
     * @var
     */
    const CONFIG_FILE_SYNTAX_ERROR = 15003;
    /**
     * config file not exist
     * @var
     */
    const CONFIG_CONST_NOT_DEFINED = 15004;
    /**
     * application environment const not defined
     * @var
     */
    const APPLICATION_ENVI_NOT_DEFINED = 15005;
    /**
     * application environment const not defined
     * @var
     */
    const APPLICATION_AUTH_CALLBACK_AUTH = 15006;
    /**
     * business level default error
     * @var int
     */
    const BUSINESS_DEFAULT_ERROR = 10000;
    /**
     * InvalidDataException
     * @var int
     */
    const BUSINESS_INVALID_DATA_ERROR = 10001;
    /**
     * business level url empty
     * @var int
     */
    const BUSINESS_URL_EMPTY = 10002;
    /**
     * business level invalid url
     * @var int
     */
    const BUSINESS_URL_INVALID = 10003;
    /**
     * business level parse url error
     * @var int
     */
    const BUSINESS_URL_PARSE_ERROR = 10004;
    /**
     * business level controller not found
     * @var int
     */
    const BUSINESS_CONTROLLER_NOT_FOUND = 10005;
    /**
     * business level action not found
     * @var int
     */
    const BUSINESS_ACTION_NOT_FOUND = 10006;
    /**
     * business level action not callable
     * @var int
     */
    const BUSINESS_ACTION_NOT_CALLABLE = 10007;
    /**
     * lack of class
     * @var int
     */
    const BUSINESS_LACK_OF_CLASS = 10008;
    /**
     * lack of database config file
     * @var int
     */
    const BUSINESS_DB_CONFIG_NOT_EXIST = 10009;
    /**
     * lack of cache config file
     * @var int
     */
    const BUSINESS_CACHE_CONFIG_NOT_EXIST = 10010;
    /**
     * add event handler error: event handler not callable
     * @var int
     */
    const BUSINESS_INFO_COLLECTOR_EVENT_HANDLER_NOT_CALLABLE = 20001;
    /**
     * add event handler error: not suppoerted event
     * @var int
     */
    const BUSINESS_INFO_COLLECTOR_EVENT_HANDLER_NOT_SUPPORT = 20002;


}
