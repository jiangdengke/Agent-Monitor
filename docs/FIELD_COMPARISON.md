# 数据库字段完整性对比报告

## ✅ 完全一致的表（18 个）

### 1. agents (探针表)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | string UUID | ✅ |
| Name | name | string | ✅ |
| Hostname | hostname | string | ✅ |
| IP | ip | string | ✅ |
| OS | os | string | ✅ |
| Arch | arch | string | ✅ |
| Version | version | string | ✅ |
| Platform | platform | string | ✅ |
| Location | location | string | ✅ |
| ExpireTime | expire_time | int64 | ✅ |
| Status | status | int | ✅ |
| LastSeenAt | last_seen_at | int64 | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 2. api_keys (API 密钥)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | string UUID | ✅ |
| Name | name | string | ✅ |
| Key | key | string | ✅ |
| Enabled | enabled | bool | ✅ |
| CreatedBy | created_by | string | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 3. cpu_metrics (CPU 指标)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| UsagePercent | usage_percent | float64 | ✅ |
| LogicalCores | logical_cores | int | ✅ |
| PhysicalCores | physical_cores | int | ✅ |
| ModelName | model_name | string | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |
| - | created_at | timestamp | ➕ Laravel |

### 4. memory_metrics (内存指标)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| Total | total | uint64 | ✅ |
| Used | used | uint64 | ✅ |
| Free | free | uint64 | ✅ |
| UsagePercent | usage_percent | float64 | ✅ |
| SwapTotal | swap_total | uint64 | ✅ |
| SwapUsed | swap_used | uint64 | ✅ |
| SwapFree | swap_free | uint64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 5. disk_metrics (磁盘指标)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| MountPoint | mount_point | string | ✅ |
| Total | total | uint64 | ✅ |
| Used | used | uint64 | ✅ |
| Free | free | uint64 | ✅ |
| UsagePercent | usage_percent | float64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 6. disk_io_metrics (磁盘 IO)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| Device | device | string | ✅ |
| ReadCount | read_count | uint64 | ✅ |
| WriteCount | write_count | uint64 | ✅ |
| ReadBytes | read_bytes | uint64 | ✅ |
| WriteBytes | write_bytes | uint64 | ✅ |
| ReadBytesRate | read_bytes_rate | uint64 | ✅ |
| WriteBytesRate | write_bytes_rate | uint64 | ✅ |
| ReadTime | read_time | uint64 | ✅ |
| WriteTime | write_time | uint64 | ✅ |
| IoTime | io_time | uint64 | ✅ |
| IopsInProgress | iops_in_progress | uint64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 7. network_metrics (网络指标)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| Interface | interface | string | ✅ |
| BytesSentRate | bytes_sent_rate | uint64 | ✅ |
| BytesRecvRate | bytes_recv_rate | uint64 | ✅ |
| BytesSentTotal | bytes_sent_total | uint64 | ✅ |
| BytesRecvTotal | bytes_recv_total | uint64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 8. load_metrics (系统负载)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| Load1 | load1 | float64 | ✅ |
| Load5 | load5 | float64 | ✅ |
| Load15 | load15 | float64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 9. gpu_metrics (GPU 指标)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| Index | index | int | ✅ |
| Name | name | string | ✅ |
| Utilization | utilization | float64 | ✅ |
| MemoryTotal | memory_total | uint64 | ✅ |
| MemoryUsed | memory_used | uint64 | ✅ |
| MemoryFree | memory_free | uint64 | ✅ |
| Temperature | temperature | float64 | ✅ |
| PowerDraw | power_draw | float64 | ✅ |
| FanSpeed | fan_speed | float64 | ✅ |
| PerformanceState | performance_state | string | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 10. temperature_metrics (温度指标)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| SensorKey | sensor_key | string | ✅ |
| SensorLabel | sensor_label | string | ✅ |
| Temperature | temperature | float64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 11. host_metrics (主机信息)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentID | agent_id | string | ✅ |
| OS | os | string | ✅ |
| Platform | platform | string | ✅ |
| PlatformVersion | platform_version | string | ✅ |
| KernelVersion | kernel_version | string | ✅ |
| KernelArch | kernel_arch | string | ✅ |
| Uptime | uptime | uint64 | ✅ |
| BootTime | boot_time | uint64 | ✅ |
| Procs | procs | uint64 | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 12. monitor_tasks (监控任务)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | string UUID | ✅ |
| Name | name | string | ✅ |
| Type | type | string | ✅ |
| Target | target | string | ✅ |
| Description | description | string | ✅ |
| Enabled | enabled | bool | ✅ |
| ShowTargetPublic | show_target_public | bool | ✅ |
| Interval | interval | int | ✅ |
| AgentIds | agent_ids | JSON | ✅ |
| HTTPConfig | http_config | JSON | ✅ |
| TCPConfig | tcp_config | JSON | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 13. monitor_metrics (监控结果)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | uint auto | ✅ |
| AgentId | agent_id | string | ✅ |
| MonitorId | monitor_id | string | ✅ |
| Type | type | string | ✅ |
| Target | target | string | ✅ |
| Status | status | string | ✅ |
| StatusCode | status_code | int | ✅ |
| ResponseTime | response_time | int64 | ✅ |
| Error | error | string | ✅ |
| Message | message | string | ✅ |
| ContentMatch | content_match | bool | ✅ |
| CertExpiryTime | cert_expiry_time | int64 | ✅ |
| CertDaysLeft | cert_days_left | int | ✅ |
| Timestamp | timestamp | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 14. monitor_stats (监控统计)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | string UUID | ✅ |
| AgentID | agent_id | string | ✅ |
| MonitorId | monitor_id | string | ✅ |
| MonitorType | monitor_type | string | ✅ |
| Target | target | string | ✅ |
| CurrentResponse | current_response | int64 | ✅ |
| AvgResponse24h | avg_response_24h | int64 | ✅ |
| Uptime24h | uptime_24h | float64 | ✅ |
| Uptime30d | uptime_30d | float64 | ✅ |
| CertExpiryDate | cert_expiry_date | int64 | ✅ |
| CertExpiryDays | cert_expiry_days | int | ✅ |
| TotalChecks24h | total_checks_24h | int64 | ✅ |
| SuccessChecks24h | success_checks_24h | int64 | ✅ |
| TotalChecks30d | total_checks_30d | int64 | ✅ |
| SuccessChecks30d | success_checks_30d | int64 | ✅ |
| LastCheckTime | last_check_time | int64 | ✅ |
| LastCheckStatus | last_check_status | string | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 15. alert_configs (告警配置)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | string UUID | ✅ |
| AgentID | agent_id | string | ✅ |
| Name | name | string | ✅ |
| Enabled | enabled | bool | ✅ |
| Rules.CPUEnabled | rule_cpu_enabled | bool | ✅ |
| Rules.CPUThreshold | rule_cpu_threshold | float64 | ✅ |
| Rules.CPUDuration | rule_cpu_duration | int | ✅ |
| Rules.MemoryEnabled | rule_memory_enabled | bool | ✅ |
| Rules.MemoryThreshold | rule_memory_threshold | float64 | ✅ |
| Rules.MemoryDuration | rule_memory_duration | int | ✅ |
| Rules.DiskEnabled | rule_disk_enabled | bool | ✅ |
| Rules.DiskThreshold | rule_disk_threshold | float64 | ✅ |
| Rules.DiskDuration | rule_disk_duration | int | ✅ |
| Rules.NetworkEnabled | rule_network_enabled | bool | ✅ |
| Rules.NetworkDuration | rule_network_duration | int | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 16. alert_records (告警记录)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | int64 auto | ✅ |
| AgentID | agent_id | string | ✅ |
| ConfigID | config_id | string | ✅ |
| ConfigName | config_name | string | ✅ |
| AlertType | alert_type | string | ✅ |
| Message | message | string | ✅ |
| Threshold | threshold | float64 | ✅ |
| ActualValue | actual_value | float64 | ✅ |
| Level | level | string | ✅ |
| Status | status | string | ✅ |
| FiredAt | fired_at | int64 | ✅ |
| ResolvedAt | resolved_at | int64 | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 17. audit_results (审计结果)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | int64 auto | ✅ |
| AgentID | agent_id | varchar(64) | ✅ |
| Type | type | varchar(32) | ✅ |
| Result | result | text | ✅ |
| StartTime | start_time | int64 | ✅ |
| EndTime | end_time | int64 | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

### 18. properties (通用配置)
| 原项目字段 | Laravel 字段 | 类型 | 状态 |
|-----------|-------------|------|------|
| ID | id | string | ✅ |
| Name | name | string | ✅ |
| Value | value | text | ✅ |
| CreatedAt | created_at | int64 | ✅ |
| UpdatedAt | updated_at | int64 | ✅ |
| - | organization_id | foreignId | ➕ 多租户 |

---

## ➕ 多租户增强（3 个表）

这些表是 Laravel 版本的增强功能，原项目不包含：

### 19. organizations (组织/租户)
- 用于多租户隔离

### 20. organization_user (组织成员)
- 用于多租户成员管理

### 21. users (用户表)
- Laravel 标准用户表

---

## 📊 统计总结

| 项目 | 数量 | 说明 |
|------|------|------|
| 原项目表 | 18 个 | 完全一致 ✅ |
| 多租户表 | 3 个 | Laravel 增强 |
| 总表数 | 21 个 | - |
| 字段缺失 | 0 个 | ✅ 无缺失 |
| 字段多余 | 仅多租户 | ✅ 符合预期 |
| 数据类型 | 完全一致 | ✅ |
| 索引策略 | 完全一致 + 增强 | ✅ |

---

## ✅ 结论

**数据库设计与原项目完全一致！**

- ✅ 所有 18 个原项目表的字段都完整存在
- ✅ 字段命名遵循 snake_case（Laravel 规范）
- ✅ 字段类型完全匹配（BIGINT 毫秒时间戳、UUID 字符串主键）
- ✅ 索引策略完全一致（idx_agent_time 等）
- ✅ 新增的字段仅为多租户支持（organization_id）和 Laravel 辅助字段（created_at timestamp）
- ✅ 可以直接对接原 Go Agent，无需修改 Agent 代码

**唯一差异：** 增加了 3 个多租户表（organizations, organization_user, users），这是功能增强，不影响与原项目的兼容性。
