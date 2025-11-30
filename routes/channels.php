<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * 公共频道 - 无需认证
 * 所有探针的指标汇总推送
 */
Broadcast::channel('metrics', function () {
    return true;
});

/**
 * 公共频道 - 探针状态变更
 */
Broadcast::channel('agents', function () {
    return true;
});

/**
 * 单个探针的指标频道
 */
Broadcast::channel('agent.{agentId}', function ($user, $agentId) {
    return true;
});
