<?php
use App\Enums\ResponseCodeEnum;
return [
    // HTTP 状态码描述（可选，包自带）
    // 自定义业务码描述
    ResponseCodeEnum::class => [
        // API Key 相关
        ResponseCodeEnum::API_KEY_REQUIRED => 'API Key 不能为空',
        ResponseCodeEnum::API_KEY_INVALID => 'API Key 无效',
        ResponseCodeEnum::API_KEY_EXPIRED => 'API Key 已过期',

        // Agent 相关
        ResponseCodeEnum::AGENT_NOT_FOUND => '探针不存在',
        ResponseCodeEnum::AGENT_ALREADY_EXISTS => '探针已存在',
        ResponseCodeEnum::AGENT_OFFLINE => '探针离线',

        // 指标数据相关
        ResponseCodeEnum::METRIC_INVALID_FORMAT => '指标数据格式错误',
        ResponseCodeEnum::METRIC_SAVE_FAILED => '指标数据保存失败',

        // 监控任务相关
        ResponseCodeEnum::MONITOR_TASK_NOT_FOUND => '监控任务不存在',
        ResponseCodeEnum::MONITOR_TASK_INVALID => '监控任务配置无效',

        // 告警相关
        ResponseCodeEnum::ALERT_CONFIG_NOT_FOUND => '告警配置不存在',
        ResponseCodeEnum::ALERT_CONFIG_INVALID => '告警配置无效',
    ],
];
