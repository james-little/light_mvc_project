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
    const ERROR_DB                       = -1;
    const ERROR_DB_NOT_SUPPORT           = -2;
    const ERROR_DB_ACCESS_OBJ_ERROR      = -3;
    const ERROR_DB_EXECUTE_ERROR         = -4;
    const ERROR_DB_CONFIG_FILE_NOT_EXIST = -5;
    const ERROR_DB_FETCH_ERROR           = -6;
    const ERROR_DB_WRITE_FAILED          = -7;
    const ERROR_DB_METHOD_NOT_ALLOWED    = -8;
    const ERROR_DB_SQL_NOT_ALLOWED       = -9;
    // io
    const ERROR_IO                     = -100;
    const ERROR_IO_RESOURCE_NOT_STREAM = -101;
    const ERROR_FILE_MODE              = -102;
    // cache
    const ERROR_CACHE                       = -150;
    const ERROR_CACHE_NOT_SUPPORT           = -151;
    const ERROR_CACHE_ACCESS_OBJ_ERROR      = -152;
    const ERROR_CACHE_CONFIG_FILE_NOT_EXIST = -153;
    // redis
    const ERROR_REDIS                       = -200;
    const ERROR_REDIS_CONFIG                = -201;
    const ERROR_REDIS_CONNECTION            = -202;
    const ERROR_REDIS_AUTH                  = -203;
    const ERROR_REDIS_CONFIG_FILE_NOT_EXIST = -204;
    // solr
    const ERROR_SOLR                       = -250;
    const ERROR_SOLR_CONFIG                = -251;
    const ERROR_SOLR_CONNECTION            = -252;
    const ERROR_SOLR_CONFIG_FILE_NOT_EXIST = -253;
    // view
    const ERROR_VIEW                       = -300;
    const ERROR_VIEW_TYPE_NOT_SUPPORT      = -301;
    const ERROR_VIEW_TEMPLATE_NOT_FOUND    = -302;
    const ERROR_VIEW_CONFIG_FILE_NOT_EXIST = -303;
    const ERROR_VIEW_EMPTY                 = -304;
    const ERROR_VIEW_EXTENSION_NOT_LOAD    = -305;
    // queue
    const ERROR_QUEUE                     = -350;
    const ERROR_QUEUE_CONFIG              = -351;
    const ERROR_QUEUE_LOG                 = -352;
    const ERROR_QUEUE_ADAPTER             = -353;
    const ERROR_QUEUE_WRITE               = -354;
    const ERROR_QUEUE_ADAPTER_MODEL_EMPTY = -355;
    const ERROR_QUEUE_DATA_UPDATE_FAILED  = -356;
    // queue_processor
    const ERROR_QUEUEPROCESSOR              = -400;
    const ERROR_QUEUEPROCESSOR_CONFIG       = -401;
    const ERROR_QUEUEPROCESSOR_COMMUNICATOR = -402;
    const ERROR_QUEUEPROCESSOR_QUEUE        = -403;
    // info collector
    const ERROR_INFO_COLLECTOR = -450;
    // mail
    const ERROR_MAIL             = -500;
    const ERROR_MAIL_SEND_FAILED = -501;
    // session
    const ERROR_SESSION                     = -550;
    const ERROR_SESSION_UPDATE              = -551;
    const ERROR_SESSION_ALREADY_START       = -552;
    const ERROR_SESSION_DECODE_ERROR        = -553;
    const ERROR_SESSION_FILE_NOT_EXIST      = -554;
    const ERROR_SESSION_ID_INVALID          = -555;
    const ERROR_SESSION_UPDATE_OLD_WITH_NEW = -556;
    const ERROR_SESSION_EXPIRED             = -557;
    // model
    const ERROR_MODEL = -600;
    // image
    const ERROR_IMAGE                = -650;
    const ERROR_IMAGE_OBJ_ERR        = -651;
    const ERROR_IMAGE_WATERMARK_FILE = -652;
    const ERROR_IMAGE_DES_DIR_ACCESS = -653;
    const ERROR_IMAGE_OPEN           = -654;
    // paginator
    const ERROR_PAGINATOR          = -700;
    const ERROR_PAGINATOR_PAGESIZE = -701;
    // ------------------------- application----------------------------------
    // config
    const ERROR_APP                       = -1000;
    const ERROR_APP_CONFIG_FILE_NOT_EXIST = -1001;
    // const
    const ERROR_APP_DIR_NOT_DEFINED        = -1002;
    const ERROR_APP_CONFIG_DIR_NOT_DEFINED = -1003;
    // envi
    const ERROR_APP_ENVI_NOT_DEFINED = -1004;
    // bootstrap
    const ERROR_APP_BOOTSTRAP_NOT_FOUND = -1005;
    // auth
    const ERROR_AUTH_FUNCTION_NOT_DEFINED = -1006;
    const ERROR_AUTH_LOGIC_EMPTY          = -1007;
    const ERROR_AUTH_FAILED               = -1008;
    // runtime context
    const ERROR_RUNTIME_CONTEXT_NOT_EXIST = -1009;
    // url
    const ERROR_URL_EMPTY             = -1010;
    const ERROR_URL_INVALID           = -1011;
    const ERROR_URL_PARSED            = -1012;
    const ERROR_DUPLICATE_REQUEST     = -1013;
    const ERROR_REQUEST_INVALID_PARAM = -1014;
    const ERROR_REQUEST_NOT_AJAX      = -1015;
    const ERROR_REQUEST_NOT_APP       = -1016;
    // debug
    const ERROR_APP_DEBUG_CONFIG_FILE_NOT_EXIST = -1016;
    // controller & action
    const ERROR_CONTROLLER_NOT_FOUND = -1050;
    const ERROR_ACTION_NOT_FOUND     = -1051;
    const ERROR_ACTION_NOT_CALLABLE  = -1052;
    // locale
    const ERROR_LOCALE_DIR_NOT_EXIST = -1053;
    // general
    const ERROR_INVALID_PARAM             = -1054;
    const ERROR_EMPTY_PARAM               = -1055;
    const ERROR_DEVICE_UNSUPPORT          = -1056;
    const ERROR_NOT_ENOUGH_PARAM          = -1057;
    const ERROR_MAINLOOP_REACHED_MAXLIMIT = -1058;
    // word split
    const ERROR_WORD_SPLIT_EXTENSION_NOT_LOAD = -1059;
    const ERROR_WORD_SPLIT_CHARSET_NOT_MATCH  = -1060;
    const ERROR_WORD_SPLIT_RETURN_TYPE_ERROR  = -1067;
    const ERROR_WORD_SPLIT_DIC_DIR_ERROR      = -1068;
    // upload
    const ERROR_UPLOAD_TMPFILE_OPEN_FAILED = -1069;
    const ERROR_UPLOAD_TMPFILE_READ_FAILED = -1070;
    const ERROR_UPLOAD_FAILED              = -1071;
    const ERROR_UPLOAD_FILE_EMPTY          = -1072;
    // email
    const ERROR_EMAIL_INVALID             = -1073;
    const ERROR_EMAIL_SEND_FAILED         = -1074;
    const ERROR_EMAIL_TEMPLATE_NOT_EXIST  = -1075;
    const ERROR_EMAIL_INVALID_FROM        = -1076;
    const ERROR_EMAIL_TYPE                = -1077;
    const ERROR_EMAIL_TEMPLATE_NAME_EXIST = -1078;
    // function not open to public
    const ERROR_UNDER_DEVELOP = -1079;
    // maintenance
    const ERROR_APP_IN_MAINTENANCE = -1080;
}
