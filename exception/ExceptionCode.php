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
 * +---------------+----------------------------------+
 * |   type        |     name       |        range    |
 * +---------------+----------------+-----------------+
 * |               |      Db        |  1 ~ 100        |
 * |               |      I/O       |  100 ~ 200      |
 * |               |     Cache      |  200 ~ 300      |
 * |    Low        |     Redis      |  300 ~ 400      |
 * |               |     Solr       |  400 ~ 500      |
 * |               |     View       |  500 ~ 600      |
 * +---------------+----------------+-----------------+
 * |               |     Queue      |  1000 ~ 1100    |
 * |               | QueueProcessor |  1100 ~ 1200    |
 * |               |  InfoCollector |  1200 ~ 1300    |
 * |    Middle     |     Mail       |  1300 ~ 1400    |
 * |               |   Session      |  1400 ~ 1500    |
 * |               |   Model        |  1500 ~ 1600    |
 * +---------------+----------------+-----------------+
 * | application   |                |  2000 ~         |
 * +---------------+----------------+-----------------+
 *
 * @package core
 */
class ExceptionCode {

    /**
     * Db default error
     * @var int
     */
    const DB_DEFAULT_ERROR = 1;
    /**
     * Db not support
     * @var int
     */
    const DB_NOT_SUPPORT = 2;
    /**
     * Db connection object error
     * @var int
     */
    const DB_ACCESS_OBJ_ERROR = 3;
    /**
     * Db execute error
     * @var int
     */
    const DB_EXECUTE_ERROR = 4;
    /**
     * Db config not exist
     * @var int
     */
    const DB_CONFIG_NOT_EXIST = 5;
    /**
     * Db config not exist
     * @var int
     */
    const DB_FETCH_ERROR = 6;
    /**
     * I/O default error
     * @var int
     */
    const IO_DEFAULT_ERROR = 100;
    /**
     * I/O resource not stream
     * @var int
     */
    const IO_RESOURCE_NOT_STREAM = 101;
    /**
     * I/O file read mode error
     * @var int
     */
    const IO_FILE_MODE_ERROR = 102;
    /**
     * cache default error
     * @var int
     */
    const CACHE_DEFAULT_ERROR = 200;
    /**
     * Delete cache data error
     * @var int
     */
    const CACHE_NOT_SUPPORT = 201;
    /**
     * Cache connection object error
     * @var int
     */
    const CACHE_ACCESS_OBJ_ERROR = 202;
    /**
     * Db config not exist
     * @var int
     */
    const CACHE_CONFIG_NOT_EXIST = 203;
    /**
     * redis
     * @var int
     */
    const REDIS_DEFAULT_ERROR = 300;
    /**
     * redis
     * @var int
     */
    const REDIS_CONFIG_ERROR = 301;
    /**
     * redis
     * @var int
     */
    const REDIS_CONNECTION_ERROR = 302;
    /**
     * redis
     * @var int
     */
    const REDIS_AUTH_ERROR = 303;
    /**
     * redis
     * @var int
     */
    const REDIS_CONFIG_NOT_EXIST = 304;
    /**
     * Solr
     * @var int
     */
    const SOLR_DEFAULT_ERROR = 400;
    /**
     * Solr
     * @var int
     */
    const SOLR_CONFIG_ERROR = 401;
    /**
     * Solr
     * @var int
     */
    const SOLR_CONNECTION_ERROR = 402;
    /**
     * solr config not exist
     * @var int
     */
    const SOLR_CONFIG_NOT_EXIST = 403;
    /**
     * View
     * @var int
     */
    const VIEW_DEFAULT_ERROR = 500;
    /**
     * View
     * @var int
     */
    const VIEW_TYPE_NOT_SUPPORT = 501;
    /**
     * View
     * @var int
     */
    const VIEW_TEMPLATE_NOT_FOUND = 502;
    /**
     * View
     * @var int
     */
    const VIEW_CONFIG_FILE_NOT_FOUND = 503;
    /**
     * View empty
     * @var int
     */
    const VIEW_EMPTY = 504;
    /**
     * View
     * @var int
     */
    const VIEW_EXTENSION_NOT_LOAD = 505;
    /**
     * word split
     * @var int
     */
    const WORD_SPLIT_EXTENSION_NOT_LOAD = 600;
    /**
     * word split
     * @var int
     */
    const WORD_SPLIT_CHARSET_NOT_MATCH = 601;
    /**
     * word split return type error
     * @var int
     */
    const WORD_SPLIT_RETURN_TYPE = 602;
    /**
     * word split return type error
     * @var int
     */
    const WORD_SPLIT_DIC_DIR_ERROR = 603;
    /**
     * image
     * @var int
     */
    const IMAGE_DEFAULT_ERROR = 700;
    /**
     * image object empty
     * @var int
     */
    const IMAGE_OBJECT_EMPTY = 701;
    /**
     * image watermark file not exist
     * @var int
     */
    const IMAGE_WATERMARK_FILE_ERR = 702;
    /**
     * image destination dir access error
     * @var int
     */
    const IMAGE_DES_DIR_ACCESS_ERR = 703;
    /**
     * image open failed
     * @var int
     */
    const IMAGE_OPEN_FAILED = 704;
    /**
     * paginator default
     * @var int
     */
    const PAGINATOR_DEFAULT = 800;
    /**
     * paginator page size
     * @var int
     */
    const PAGINATOR_PAGESIZE = 801;
    /**
     * Queue
     * @var int
     */
    const QUEUE_DEFAULT_ERROR = 1000;
    /**
     * Queue
     * @var int
     */
    const QUEUE_CONFIG_ERROR = 1001;
    /**
     * Queue
     * @var int
     */
    const QUEUE_LOG_NOT_SET = 1002;
    /**
     * Queue
     * @var int
     */
    const QUEUE_ADAPTER_NOT_SET = 1003;
    /**
     * Queue Processor
     * @var int
     */
    const QUEUEPROCESSOR_DEFAULT_ERROR = 1004;
    /**
     * Queue
     * @var int
     */
    const QUEUEPROCESSOR_CONFIG_ERROR = 1005;
    /**
     * Queue
     * @var int
     */
    const QUEUEPROCESSOR_COMMUNICATOR_ERROR = 1006;
    /**
     * Queue
     * @var int
     */
    const QUEUEPROCESSOR_QUEUE_ERROR = 1007;
    /**
     * info collector
     * @var int
     */
    const INFO_COLLECTOR_DEFAULT_ERROR = 1200;
    /**
     * mail log
     * @var int
     */
    const MAIL_DEFAULT_ERROR = 1201;
    /**
     * SESSION
     * @var int
     */
    const SESSION_DEFAULT_ERROR = 1300;
    /**
     * SESSION update error
     * @var int
     */
    const SESSION_UPDATE_ERROR = 1301;
    /**
     * model
     * @var int
     */
    const MODEL_DEFAULT_ERROR = 1400;
    /**
     * request
     * @var int
     */
    const REQUEST_NOT_AJAX = 1500;
    /**
     * request
     * @var int
     */
    const REQUEST_NOT_APP = 1501;

    // ----------------------------- APPLICATION LEVEL --------------------------------------------
    /**
     * application error
     * @var int
     */
    const APP_DEFAULT_ERROR = 2000;
    /**
     * config file not exist
     * @var
     */
    const APP_CONFIG_FILE_NOT_EXIST = 2001;
    /**
     * config file not exist
     * @var
     */
    const APP_CONFIG_DIR_NOT_DEFINED = 2002;
    /**
     * application environment const not defined
     * @var
     */
    const APP_ENVI_NOT_DEFINED = 2003;
    /**
     * application auth call back function not defined
     * @var
     */
    const APP_AUTH_CALLBACK_AUTH = 2004;
    /**
     * auth logic empty
     * @var
     */
    const APP_AUTH_LOGIC_EMPTY = 2005;
    /**
     * application auth failed
     * @var
     */
    const APP_AUTH_FAILED = 2006;
    /**
     * applicaiton dir not defined
     * @var int
     */
    const APP_DIR_NOT_DEFINED = 2007;
    /**
     * applicaiton bootstrap not found
     * @var int
     */
    const APP_BOOTSTRAP_NOT_FOUND = 2008;
    /**
     * runtime  context not initialized
     * @var int
     */
    const APP_RUNTIME_CONTEXT_NOT_EXIST = 2009;
    /**
     * application level url empty
     * @var int
     */
    const APP_URL_EMPTY = 2010;
    /**
     * business level invalid url
     * @var int
     */
    const APP_URL_INVALID = 2011;
    /**
     * invalid param
     * @var int
     */
    const APP_URL_INVALID_PARAM = 2012;
    /**
     * business level parse url error
     * @var int
     */
    const APP_URL_PARSE_ERROR = 2013;
    /**
     * business level controller not found
     * @var int
     */
    const APP_CONTROLLER_NOT_FOUND = 2014;
    /**
     * application level action not found
     * @var int
     */
    const APP_ACTION_NOT_FOUND = 2015;
    /**
     * business level action not callable
     * @var int
     */
    const APP_ACTION_NOT_CALLABLE = 2016;
    /**
     * locale dir not exist
     * @var int
     */
    const APP_LOCALE_DIR_NOT_EXIST = 2017;
    /**
     * main loop reach upper limit
     * @var int
     */
    const APP_MAINLOOP_REACHED_MAXLIMIT = 2018;
    /**
     * debug config file not exist
     * @var int
     */
    const APP_DEBUG_CONFIG_FILE_NOT_EXIST = 2019;
    /**
     * application is in maintenance
     * @var int
     */
    const APP_IN_MAINTENANCE = 2020;

}
