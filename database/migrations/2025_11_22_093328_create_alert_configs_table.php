<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * alert_configs 表 - 告警规则配置
 *
 * 定义各种资源的告警阈值和触发条件
 * 支持 CPU、内存、磁盘、网络等多种告警规则
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alert_configs', function (Blueprint $table) {
            // === 主键 ===
            $table->string('id')->primary(); // 配置唯一标识（UUID）

            // === 基本信息 ===
            $table->string('agent_id')->nullable(); // 探针 ID，NULL 表示全局配置
            $table->string('name'); // 配置名称
            $table->boolean('enabled')->default(true); // 是否启用

            // === CPU 告警规则 ===
            $table->boolean('rule_cpu_enabled')->default(false); // 是否启用 CPU 告警
            $table->float('rule_cpu_threshold')->nullable(); // CPU 使用率阈值（百分比，0-100）
            $table->integer('rule_cpu_duration')->nullable(); // 持续时间阈值（秒），超过此时间才触发

            // === 内存告警规则 ===
            $table->boolean('rule_memory_enabled')->default(false); // 是否启用内存告警
            $table->float('rule_memory_threshold')->nullable(); // 内存使用率阈值（百分比，0-100）
            $table->integer('rule_memory_duration')->nullable(); // 持续时间阈值（秒）

            // === 磁盘告警规则 ===
            $table->boolean('rule_disk_enabled')->default(false); // 是否启用磁盘告警
            $table->float('rule_disk_threshold')->nullable(); // 磁盘使用率阈值（百分比，0-100）
            $table->integer('rule_disk_duration')->nullable(); // 持续时间阈值（秒）

            // === 网络断开告警规则 ===
            $table->boolean('rule_network_enabled')->default(false); // 是否启用网络断开告警
            $table->integer('rule_network_duration')->nullable(); // 断开持续时间阈值（秒）

            // === 时间戳 ===
            $table->bigInteger('created_at'); // 创建时间（毫秒时间戳）
            $table->bigInteger('updated_at'); // 更新时间（毫秒时间戳）

            // === 索引 ===
            $table->index('agent_id'); // 用于快速查询某探针的告警配置

            // === 外键约束 ===
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade'); // 探针删除时级联删除告警配置
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_configs');
    }
};
