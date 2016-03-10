<?php

return [
    ErrorCode::ERROR_DB                              => "database error",
    ErrorCode::ERROR_DB_NOT_SUPPORT                  => "database not support",
    ErrorCode::ERROR_DB_ACCESS_OBJ_ERROR             => "database access object error",
    ErrorCode::ERROR_DB_EXECUTE_ERROR                => "database execute error",
    ErrorCode::ERROR_DB_CONFIG_FILE_NOT_EXIST        => "database config file not exits",
    ErrorCode::ERROR_DB_FETCH_ERROR                  => "database fetch error",
    ErrorCode::ERROR_DB_WRITE_FAILED                 => "database write failed",
    ErrorCode::ERROR_DB_METHOD_NOT_ALLOWED           => "database method not allowed",
    ErrorCode::ERROR_DB_SQL_NOT_ALLOWED              => "sql not allowed",
    // io
    ErrorCode::ERROR_IO                              => "io error",
    ErrorCode::ERROR_IO_RESOURCE_NOT_STREAM          => "io resource not stream",
    ErrorCode::ERROR_FILE_MODE                       => "io error: file mode error",
    // cache
    ErrorCode::ERROR_CACHE                           => "cache error",
    ErrorCode::ERROR_CACHE_NOT_SUPPORT               => "cache not support",
    ErrorCode::ERROR_CACHE_ACCESS_OBJ_ERROR          => "cache access object error",
    ErrorCode::ERROR_CACHE_CONFIG_FILE_NOT_EXIST     => "cache config file not exist",
    // redis
    ErrorCode::ERROR_REDIS                           => "redis error",
    ErrorCode::ERROR_REDIS_CONFIG                    => "redis config error",
    ErrorCode::ERROR_REDIS_CONNECTION                => "redis connection error",
    ErrorCode::ERROR_REDIS_AUTH                      => "redis auth error",
    ErrorCode::ERROR_REDIS_CONFIG_FILE_NOT_EXIST     => "redis config file not exist",
    // solr
    ErrorCode::ERROR_SOLR                            => "solr error",
    ErrorCode::ERROR_SOLR_CONFIG                     => "solr config",
    ErrorCode::ERROR_SOLR_CONNECTION                 => "solr connection error",
    ErrorCode::ERROR_SOLR_CONFIG_FILE_NOT_EXIST      => "solr config file not exist",
    // view
    ErrorCode::ERROR_VIEW                            => "view error",
    ErrorCode::ERROR_VIEW_TYPE_NOT_SUPPORT           => "view type not support",
    ErrorCode::ERROR_VIEW_TEMPLATE_NOT_FOUND         => "view template not found",
    ErrorCode::ERROR_VIEW_CONFIG_FILE_NOT_EXIST      => "view config file not exist",
    ErrorCode::ERROR_VIEW_EMPTY                      => "view empty",
    ErrorCode::ERROR_VIEW_EXTENSION_NOT_LOAD         => "view extension not load",
    // queue
    ErrorCode::ERROR_QUEUE                           => "queue error",
    ErrorCode::ERROR_QUEUE_CONFIG                    => "queue config error",
    ErrorCode::ERROR_QUEUE_LOG                       => "queue log error",
    ErrorCode::ERROR_QUEUE_ADAPTER                   => "queue adapter error",
    ErrorCode::ERROR_QUEUE_WRITE                     => "queue write error",
    ErrorCode::ERROR_QUEUE_ADAPTER_MODEL_EMPTY       => "queue adapter model error",
    ErrorCode::ERROR_QUEUE_DATA_UPDATE_FAILED        => "queue data update failed",
    // queue_processor
    ErrorCode::ERROR_QUEUEPROCESSOR                  => "queue processor error",
    ErrorCode::ERROR_QUEUEPROCESSOR_CONFIG           => "queue processor config error",
    ErrorCode::ERROR_QUEUEPROCESSOR_COMMUNICATOR     => "queue processor communicator error",
    ErrorCode::ERROR_QUEUEPROCESSOR_QUEUE            => "queue processor queue error",
    // info collector
    ErrorCode::ERROR_INFO_COLLECTOR                  => "info collector error",
    // mail
    ErrorCode::ERROR_MAIL                            => "mail error",
    ErrorCode::ERROR_MAIL_SEND_FAILED                => "mail send error",
    // session
    ErrorCode::ERROR_SESSION                         => "session error",
    ErrorCode::ERROR_SESSION_UPDATE                  => "session update error",
    ErrorCode::ERROR_SESSION_ALREADY_START           => "session already started",
    ErrorCode::ERROR_SESSION_DECODE_ERROR            => "session decode error",
    ErrorCode::ERROR_SESSION_FILE_NOT_EXIST          => "session file not exist",
    ErrorCode::ERROR_SESSION_ID_INVALID              => "session id invalid",
    ErrorCode::ERROR_SESSION_UPDATE_OLD_WITH_NEW     => "session update with new error",
    ErrorCode::ERROR_SESSION_EXPIRED                 => "session expired",
    // model
    ErrorCode::ERROR_MODEL                           => "model error",
    // image
    ErrorCode::ERROR_IMAGE                           => "image error",
    ErrorCode::ERROR_IMAGE_OBJ_ERR                   => "image object error",
    ErrorCode::ERROR_IMAGE_WATERMARK_FILE            => "image watermark file error",
    ErrorCode::ERROR_IMAGE_DES_DIR_ACCESS            => "image destination dir can't access",
    ErrorCode::ERROR_IMAGE_OPEN                      => "image file open error",
    // paginator
    ErrorCode::ERROR_PAGINATOR                       => "pagination error",
    ErrorCode::ERROR_PAGINATOR_PAGESIZE              => "pagination page size error",
    // ------------------------- application----------------------------------
    // config
    ErrorCode::ERROR_APP                             => "application error",
    ErrorCode::ERROR_APP_CONFIG_FILE_NOT_EXIST       => "application config not exist",
    // const
    ErrorCode::ERROR_APP_DIR_NOT_DEFINED             => "APP_DIR not defined",
    ErrorCode::ERROR_APP_CONFIG_DIR_NOT_DEFINED      => "APP_CONFIG_DIR not defined",
    // envi
    ErrorCode::ERROR_APP_ENVI_NOT_DEFINED            => "APP_ENVI not defined",
    // bootstrap
    ErrorCode::ERROR_APP_BOOTSTRAP_NOT_FOUND         => "bootstrap not found",
    // auth
    ErrorCode::ERROR_AUTH_FUNCTION_NOT_DEFINED       => "auth function not defined",
    ErrorCode::ERROR_AUTH_LOGIC_EMPTY                => "auth logic empty",
    ErrorCode::ERROR_AUTH_FAILED                     => "auth failed",
    // runtime context
    ErrorCode::ERROR_RUNTIME_CONTEXT_NOT_EXIST       => "runtime context not exist",
    // url
    ErrorCode::ERROR_URL_EMPTY                       => "url empty",
    ErrorCode::ERROR_URL_INVALID                     => "url invalid",
    ErrorCode::ERROR_URL_PARSED                      => "url parsed error",
    ErrorCode::ERROR_DUPLICATE_REQUEST               => "duplicate request",
    ErrorCode::ERROR_REQUEST_INVALID_PARAM           => "request param invalid",
    ErrorCode::ERROR_REQUEST_NOT_AJAX                => "request not ajax",
    ErrorCode::ERROR_REQUEST_NOT_APP                 => "request not app",
    // debug
    ErrorCode::ERROR_APP_DEBUG_CONFIG_FILE_NOT_EXIST => "application debug config file not exist",
    // controller & action
    ErrorCode::ERROR_CONTROLLER_NOT_FOUND            => "controller not found",
    ErrorCode::ERROR_ACTION_NOT_FOUND                => "action not found",
    ErrorCode::ERROR_ACTION_NOT_CALLABLE             => "action not callable",
    // locale
    ErrorCode::ERROR_LOCALE_DIR_NOT_EXIST            => "locale directory not found",
    // general
    ErrorCode::ERROR_INVALID_PARAM                   => "param invalid",
    ErrorCode::ERROR_EMPTY_PARAM                     => "param empty",
    ErrorCode::ERROR_DEVICE_UNSUPPORT                => "device unsupported",
    ErrorCode::ERROR_NOT_ENOUGH_PARAM                => "not enough param",
    ErrorCode::ERROR_MAINLOOP_REACHED_MAXLIMIT       => "main loop reached max limit",
    // word split
    ErrorCode::ERROR_WORD_SPLIT_EXTENSION_NOT_LOAD   => "word split extension not loaded",
    ErrorCode::ERROR_WORD_SPLIT_CHARSET_NOT_MATCH    => "word split charset not match",
    ErrorCode::ERROR_WORD_SPLIT_RETURN_TYPE_ERROR    => "word split return type error",
    ErrorCode::ERROR_WORD_SPLIT_DIC_DIR_ERROR        => "word split directory error",
    // upload
    ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED      => "upload tmp file open failed",
    ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED      => "upload tmp file read failed",
    ErrorCode::ERROR_UPLOAD_FAILED                   => "upload failed",
    ErrorCode::ERROR_UPLOAD_FILE_EMPTY               => "upload file empty",
    // email
    ErrorCode::ERROR_EMAIL_INVALID                   => "email invalid",
    ErrorCode::ERROR_EMAIL_SEND_FAILED               => "email send failed",
    ErrorCode::ERROR_EMAIL_TEMPLATE_NOT_EXIST        => "email template not exist",
    ErrorCode::ERROR_EMAIL_INVALID_FROM              => "email from address invalid",
    ErrorCode::ERROR_EMAIL_TYPE                      => "email type error",
    ErrorCode::ERROR_EMAIL_TEMPLATE_NAME_EXIST       => "email template name already exist",
    // function not open to public
    ErrorCode::ERROR_UNDER_DEVELOP                   => "module still under develop",
    ErrorCode::ERROR_APP_IN_MAINTENANCE              => "application in maintenance",
];