<?php
namespace App\Enums;

class ResponseCodeEnum
{
    // 通用业务码
    const SUCCESS = 200;
    const FAIL = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const SERVER_ERROR = 500;

    // API Key 相关
    const API_KEY_REQUIRED = 40101;
    const API_KEY_INVALID = 40102;
    const API_KEY_EXPIRED = 40103;

    // Agent 相关
    const AGENT_NOT_FOUND = 40401;
    const AGENT_ALREADY_EXISTS = 40001;
    const AGENT_OFFLINE = 40002;
    const AGENT_REGISTER_SUCCESS_AGAIN = 40003;
    const AGENT_REGISTER_SUCCESS = 40004;
    // 指标数据相关
    const METRIC_INVALID_FORMAT = 42201;
    const METRIC_SAVE_FAILED = 50001;

    // 监控任务相关
    const MONITOR_TASK_NOT_FOUND = 40402;
    const MONITOR_TASK_INVALID = 42202;

    // 告警相关
    const ALERT_CONFIG_NOT_FOUND = 40403;
    const ALERT_CONFIG_INVALID = 42203;
}
