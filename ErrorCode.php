<?php

/**
 * ErrorCode
 * =======================================================
 * error codes
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class ErrorCode {

    // db
    const ERROR_DB = -1;
    const ERROR_DB_NOT_SUPPORT = -2;
    const ERROR_DB_ACCESS_OBJ_ERROR = -3;
    const ERROR_DB_EXECUTE_ERROR = -4;
    const ERROR_DB_CONFIG_FILE_NOT_EXIST = -5;
    const ERROR_DB_FETCH_ERROR = -6;
    const ERROR_DB_WRITE_FAILED = -7;
    const ERROR_DB_METHOD_NOT_ALLOWED = -8;
    const ERROR_DB_SQL_NOT_ALLOWED = -9;
    // io
    const ERROR_IO = -100;
    const ERROR_IO_RESOURCE_NOT_STREAM = -101;
    const ERROR_FILE_MODE = -102;
    // cache
    const ERROR_CACHE = -200;
    const ERROR_CACHE_NOT_SUPPORT = -201;
    const ERROR_CACHE_ACCESS_OBJ_ERROR = -202;
    const ERROR_CACHE_CONFIG_FILE_NOT_EXIST = -203;
    // redis
    const ERROR_REDIS = -300;
    const ERROR_REDIS_CONFIG = -301;
    const ERROR_REDIS_CONNECTION = -302;
    const ERROR_REDIS_AUTH = -303;
    const ERROR_REDIS_CONFIG_FILE_NOT_EXIST = -304;
    // solr
    const ERROR_SOLR = -400;
    const ERROR_SOLR_CONFIG = -401;
    const ERROR_SOLR_CONNECTION = -402;
    const ERROR_SOLR_CONFIG_FILE_NOT_EXIST = -403;
    // view
    const ERROR_VIEW = -500;
    const ERROR_VIEW_TYPE_NOT_SUPPORT = -501;
    const ERROR_VIEW_TEMPLATE_NOT_FOUND = -502;
    const ERROR_VIEW_CONFIG_FILE_NOT_EXIST = -503;
    const ERROR_VIEW_EMPTY = -504;
    const ERROR_VIEW_EXTENSION_NOT_LOAD = -505;
    // queue
    const ERROR_QUEUE = -600;
    const ERROR_QUEUE_CONFIG = -601;
    const ERROR_QUEUE_LOG = -602;
    const ERROR_QUEUE_ADAPTER = -603;
    const ERROR_QUEUE_WRITE = -604;
    // queue_processor
    const ERROR_QUEUEPROCESSOR = -700;
    const ERROR_QUEUEPROCESSOR_CONFIG = -701;
    const ERROR_QUEUEPROCESSOR_COMMUNICATOR = -702;
    const ERROR_QUEUEPROCESSOR_QUEUE = -703;
    // info collector
    const ERROR_INFO_COLLECTOR = -800;
    // mail
    const ERROR_MAIL = -900;
    const ERROR_MAIL_SEND_FAILED = -901;
    // session
    const ERROR_SESSION = -1000;
    const ERROR_SESSION_UPDATE = -1001;
    const ERROR_SESSION_ALREADY_START = -1002;
    const ERROR_SESSION_DECODE_ERROR = -1003;
    const ERROR_SESSION_FILE_NOT_EXIST = -1004;
    const ERROR_SESSION_ID_INVALID = -1005;
    const ERROR_SESSION_UPDATE_OLD_WITH_NEW = -1006;
    const ERROR_SESSION_EXPIRED = -1007;
    // model
    const ERROR_MODEL = -1100;
    // ------------------------- application----------------------------------
    // config
    const ERROR_APP = -1200;
    const ERROR_APP_CONFIG_FILE_NOT_EXIST = -1201;
    // const
    const ERROR_APP_DIR_NOT_DEFINED = -1202;
    const ERROR_APP_CONFIG_DIR_NOT_DEFINED = -1203;
    // envi
    const ERROR_APP_ENVI_NOT_DEFINED = -1204;
    // bootstrap
    const ERROR_APP_BOOTSTRAP_NOT_FOUND = -1205;
    // auth
    const ERROR_AUTH_FUNCTION_NOT_DEFINED = -1206;
    const ERROR_AUTH_LOGIC_EMPTY = -1207;
    const ERROR_AUTH_FAILED = -1208;
    // runtime context
    const ERROR_RUNTIME_CONTEXT_NOT_EXIST = -1209;
    // url
    const ERROR_URL_EMPTY = -1210;
    const ERROR_URL_INVALID = -1211;
    const ERROR_URL_PARSED = -1212;
    const ERROR_DUPLICATE_REQUEST = -1213;
    const ERROR_REQUEST_INVALID_PARAM = -1214;
    const ERROR_REQUEST_NOT_AJAX = -1215;
    // controller & action
    const ERROR_CONTROLLER_NOT_FOUND = -1250;
    const ERROR_ACTION_NOT_FOUND = -1251;
    const ERROR_ACTION_NOT_CALLABLE = -1252;
    // locale
    const ERROR_LOCALE_DIR_NOT_EXIST = -1300;
    // general
    const ERROR_INVALID_PARAM = -1350;
    const ERROR_EMPTY_PARAM = -1351;
    const ERROR_DEVICE_UNSUPPORT = -1352;
    const ERROR_NOT_ENOUGH_PARAM = -1353;
    const ERROR_MAINLOOP_REACHED_MAXLIMIT = -1354;
    const ERROR_WORD_SPLIT_EXTENSION_NOT_LOAD = -1355;
    const ERROR_WORD_SPLIT_CHARSET_NOT_MATCH = -1356;
    const ERROR_WORD_SPLIT_RETURN_TYPE_ERROR = -1357;
    const ERROR_WORD_SPLIT_DIC_DIR_ERROR = -1358;
    // upload
    const ERROR_UPLOAD_TMPFILE_OPEN_FAILED = -1400;
    const ERROR_UPLOAD_TMPFILE_READ_FAILED = -1401;
    const ERROR_UPLOAD_FAILED = -1402;
    const ERROR_UNDER_DEVELOP = -1403;







    const ERROR_SOCKET_INVALID_COMMAND = -100;
    const ERROR_SOCKET_COMMAND_NOT_SUPPORT = -101;
    const ERROR_SOCKET_COMMAND_INVALID_PARAMS = -102;
    const ERROR_SOCKET_COMMAND_QUERY_PARSE_ERROR = -103;
    const ERROR_SOCKET_COMMAND_METHOD_NOT_EXIST = -104;
    const ERROR_SOCKET_COMMAND_CONFIG_NOT_EXIST = -105;
    const ERROR_SOCKET_COMMAND_CONNECTION_NOT_EXIST = -105;
}
