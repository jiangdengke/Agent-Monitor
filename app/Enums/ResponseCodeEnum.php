<?php
namespace App\Enums;

use Jiannei\Enum\Laravel\Support\Traits\EnumEnhance;

enum ResponseCodeEnum: int
{
    use EnumEnhance;

    // ==================== 通用业务码 ====================
    // 成功
    case SUCCESS = 200000;

    // 客户端错误
    case CLIENT_PARAMETER_ERROR = 400001;
    case CLIENT_VALIDATION_ERROR = 400002;
    case UNAUTHORIZED = 401000;
    case FORBIDDEN = 403000;
    case NOT_FOUND = 404000;

    // 服务端错误
    case SERVER_ERROR = 500001;
    case SERVICE_UNAVAILABLE = 500002;
    case DATABASE_ERROR = 500003;

    // ==================== API Key 认证模块（1xx）====================
    // 客户端错误
    case API_KEY_REQUIRED = 400101;
    case API_KEY_INVALID = 400102;
    case API_KEY_EXPIRED = 400103;

    // ==================== Agent 探针模块（2xx）====================
    // 成功
    case AGENT_REGISTER_SUCCESS = 200201;
    case AGENT_UPDATE_SUCCESS = 200202;
    case AGENT_HEARTBEAT_SUCCESS = 200203;

    // 客户端错误
    case AGENT_NOT_FOUND = 400201;
    case AGENT_ALREADY_EXISTS = 400202;
    case AGENT_OFFLINE = 400203;
    case AGENT_INVALID_DATA = 400204;

    // ==================== Metric 指标数据模块（3xx）====================
    // 成功
    case METRIC_SAVE_SUCCESS = 200301;
    case METRIC_BATCH_SAVE_SUCCESS = 200302;

    // 客户端错误
    case METRIC_INVALID_FORMAT = 400301;
    case METRIC_MISSING_REQUIRED_FIELD = 400302;
    case METRIC_INVALID_TIMESTAMP = 400303;

    // 服务端错误
    case METRIC_SAVE_FAILED = 500301;
    case METRIC_DATABASE_ERROR = 500302;

    // ==================== Monitor 监控任务模块（4xx）====================
    // 成功
    case MONITOR_TASK_CREATE_SUCCESS = 200401;
    case MONITOR_TASK_UPDATE_SUCCESS = 200402;
    case MONITOR_TASK_DELETE_SUCCESS = 200403;

    // 客户端错误
    case MONITOR_TASK_NOT_FOUND = 400401;
    case MONITOR_TASK_INVALID = 400402;
    case MONITOR_TASK_ALREADY_EXISTS = 400403;

    // ==================== Alert 告警模块（5xx）====================
    // 成功
    case ALERT_CONFIG_CREATE_SUCCESS = 200501;
    case ALERT_CONFIG_UPDATE_SUCCESS = 200502;
    case ALERT_SEND_SUCCESS = 200503;

    // 客户端错误
    case ALERT_CONFIG_NOT_FOUND = 400501;
    case ALERT_CONFIG_INVALID = 400502;
    case ALERT_CHANNEL_INVALID = 400503;

    // 服务端错误
    case ALERT_SEND_FAILED = 500501;
    case ALERT_CHANNEL_ERROR = 500502;
}
