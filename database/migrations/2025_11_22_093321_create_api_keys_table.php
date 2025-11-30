<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * api_keys 表 - API 密钥管理
 *
 * 用于管理系统的 API 访问密钥
 * Agent 通过 API Key 进行身份认证和数据上报
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            // === 主键 ===
            $table->string('id')->primary(); // API Key 唯一标识（UUID）

            // === 基本信息 ===
            $table->string('name')->index(); // API Key 名称，便于识别用途
            $table->string('agent_id')->nullable()->index(); // 绑定的 Agent ID
            $table->string('key')->unique(); // API Key 密钥字符串，用于认证
            $table->boolean('enabled')->index()->default(true); // 是否启用：true=启用, false=禁用
            $table->string('created_by')->index(); // 创建人 ID，记录谁创建了此密钥

            // === 时间戳 ===
            $table->bigInteger('created_at'); // 创建时间（毫秒时间戳）
            $table->bigInteger('updated_at'); // 更新时间（毫秒时间戳）

            // === 索引 ===
            $table->index('key'); // API Key 索引，用于快速认证查询
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
