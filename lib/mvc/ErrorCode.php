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

    const ERROR_SESSION_DEFAULT = -1;
    const ERROR_SESSION_ALREADY_START = -2;
    const ERROR_SESSION_DECODE_ERROR = -3;
    const ERROR_SESSION_FILE_NOT_EXIST = -4;
    const ERROR_SESSION_ID_INVALID = -5;

    const ERROR_SOCKET_INVALID_COMMAND = -100;
    const ERROR_SOCKET_COMMAND_NOT_SUPPORT = -101;
    const ERROR_SOCKET_COMMAND_INVALID_PARAMS = -102;
    const ERROR_SOCKET_COMMAND_QUERY_PARSE_ERROR = -103;
    const ERROR_SOCKET_COMMAND_METHOD_NOT_EXIST = -104;
    const ERROR_SOCKET_COMMAND_CONFIG_NOT_EXIST = -105;
    const ERROR_SOCKET_COMMAND_CONNECTION_NOT_EXIST = -105;

    const ERROR_REQUEST_INVALID_PARAM = -200;

}
