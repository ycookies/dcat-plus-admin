<?php

namespace Dcat\Admin\Support;

/**
 * ERP 首页的演示数据。
 *
 * 真实项目中可将本类替换为查询服务（订单、库存、客户等），
 * 首页控制器只消费本类返回的结构，不需要改动界面组装代码。
 */
class ErpDashboardData
{
    /**
     * 返回完整的经营驾驶舱数据。
     */
    public static function make(): array
    {
        $now = now();
        $weekMap = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];

        return [
            'period' => $now->format('Y 年 n 月 j 日') . ' · ' . $weekMap[$now->dayOfWeek],
            'banners' => [
                [
                    'theme' => 'indigo',
                    'image' => '/vendor/dcat-admin/images/login-bg.jpg',
                    'image_alt' => '销售增长数据看板',
                    'eyebrow' => '销售增长引擎',
                    'title' => '本周经营目标已完成 82%',
                    'description' => '销售额连续 4 天超过目标线，企业采购渠道贡献增长最明显。',
                    'value' => '¥803,000',
                    'value_label' => '近 7 日含税销售额',
                    'side_value' => '+18.6%',
                    'side_label' => '较上周增长',
                    'tag' => '重点关注 · 企业采购',
                    'icon' => 'feather icon-trending-up',
                ],
                [
                    'theme' => 'emerald',
                    'image' => '/vendor/dcat-admin/images/login_32-bg.jpg',
                    'image_alt' => '订单履约与后台管理',
                    'eyebrow' => '订单履约中心',
                    'title' => '今天 91 单已完成出库',
                    'description' => '履约效率保持稳定，建议优先处理 12 笔加急订单与华东一仓的补货申请。',
                    'value' => '76%',
                    'value_label' => '今日发货目标达成率',
                    'side_value' => '29',
                    'side_label' => '待处理订单',
                    'tag' => '运营提醒 · 加急订单',
                    'icon' => 'feather icon-package',
                ],
                [
                    'theme' => 'amber',
                    'image' => '/vendor/dcat-admin/images/login-bg.jpg',
                    'image_alt' => '库存管理与业务数据',
                    'eyebrow' => '库存健康度',
                    'title' => '18 个 SKU 需要补货跟进',
                    'description' => '核心 SKU 库存整体可控，企业级 SSD 与专业显示器已进入安全库存警戒区。',
                    'value' => '4',
                    'value_label' => '高优先级补货任务',
                    'side_value' => '96.2%',
                    'side_label' => '订单履约率',
                    'tag' => '库存预警 · 立即处理',
                    'icon' => 'feather icon-layers',
                ],
                [
                    'theme' => 'violet',
                    'image' => '/vendor/dcat-admin/images/login_32-bg.jpg',
                    'image_alt' => '项目与任务协同',
                    'eyebrow' => '业务协同看板',
                    'title' => '销售、采购与项目节奏统一协同',
                    'description' => '从订单到项目交付，关键节点与责任人都可在一个管理后台内快速追踪。',
                    'value' => '32',
                    'value_label' => '本周跨部门协同事项',
                    'side_value' => '8',
                    'side_label' => '今日待跟进',
                    'tag' => '效率提升 · 跨部门协作',
                    'icon' => 'feather icon-git-branch',
                ],
            ],
            'notices' => [
                ['message' => '运营提醒：华东一仓的企业级 SSD 1TB 已低于安全库存，请及时发起采购补货。'],
                ['message' => '销售快报：企业采购渠道近 7 日增长 26.4%，华北区域客户转化表现优秀。'],
                ['message' => '履约提示：当前有 12 笔加急订单待处理，建议在 14:00 前完成配货复核。'],
            ],
            'overview' => [
                [
                    'label' => '今日销售额',
                    'value' => '¥128,640',
                    'subtext' => '较昨日 +12.8%',
                    'trend' => 'up',
                    'icon' => 'feather icon-dollar-sign',
                    'color' => 'primary',
                    'sparkline' => [58, 71, 66, 82, 76, 94, 108],
                ],
                [
                    'label' => '待发货订单',
                    'value' => '86',
                    'subtext' => '其中加急订单 12 单',
                    'trend' => 'neutral',
                    'icon' => 'feather icon-package',
                    'color' => 'warning',
                    'sparkline' => [44, 51, 62, 57, 68, 73, 86],
                ],
                [
                    'label' => '库存预警',
                    'value' => '18',
                    'subtext' => '3 个 SKU 即将缺货',
                    'trend' => 'down',
                    'icon' => 'feather icon-alert-triangle',
                    'color' => 'danger',
                    'sparkline' => [31, 28, 24, 25, 22, 19, 18],
                ],
                [
                    'label' => '新增客户',
                    'value' => '42',
                    'subtext' => '本月累计 1,286 人',
                    'trend' => 'up',
                    'icon' => 'feather icon-user-plus',
                    'color' => 'success',
                    'sparkline' => [18, 25, 21, 32, 29, 36, 42],
                ],
            ],
            'salesTrend' => [
                'labels' => self::recentDayLabels($now),
                'actual' => [96, 112, 105, 128, 119, 114, 129],
                'target' => [90, 96, 101, 108, 112, 116, 120],
                'total' => '¥803,000',
                'growth' => '+18.6%',
            ],
            'channels' => [
                'labels' => ['直营网店', '企业采购', '线下门店', '分销渠道'],
                'series' => [42, 28, 18, 12],
                'colors' => ['#5b6df9', '#18b67a', '#f6a23c', '#7d89a7'],
            ],
            'categories' => [
                'labels' => ['智能硬件', '办公耗材', '企业服务', '精选配件', '其他'],
                'series' => [386, 248, 192, 156, 94],
            ],
            'fulfillment' => [
                'value' => 76,
                'target' => '今日发货目标 120 单',
                'completed' => '已完成 91 单',
                'pending' => '待处理 29 单',
            ],
            'orders' => [
                ['no' => 'SO-20260720-0186', 'customer' => '深圳市云帆科技有限公司', 'amount' => '¥12,860.00', 'status' => '待发货', 'tone' => 'warning', 'time' => '09:42'],
                ['no' => 'SO-20260720-0185', 'customer' => '杭州知远数字科技', 'amount' => '¥8,460.00', 'status' => '已支付', 'tone' => 'primary', 'time' => '09:35'],
                ['no' => 'SO-20260720-0184', 'customer' => '北京北极星商务咨询', 'amount' => '¥26,800.00', 'status' => '配货中', 'tone' => 'info', 'time' => '09:18'],
                ['no' => 'SO-20260720-0183', 'customer' => '广州创客邦电子商务', 'amount' => '¥4,280.00', 'status' => '已完成', 'tone' => 'success', 'time' => '09:04'],
                ['no' => 'SO-20260720-0182', 'customer' => '苏州启明智能制造', 'amount' => '¥16,900.00', 'status' => '待确认', 'tone' => 'secondary', 'time' => '08:56'],
            ],
            'inventory' => [
                ['sku' => 'DP-ROUTER-X1', 'name' => 'Dcat Pro 企业路由器 X1', 'stock' => 8, 'safety' => 30, 'warehouse' => '华东一仓'],
                ['sku' => 'DP-SSD-1T', 'name' => '企业级 SSD 1TB', 'stock' => 12, 'safety' => 40, 'warehouse' => '华南中心仓'],
                ['sku' => 'DP-DISPLAY-27', 'name' => '27 英寸专业显示器', 'stock' => 5, 'safety' => 20, 'warehouse' => '华东一仓'],
                ['sku' => 'DP-LICENSE-365', 'name' => '协作套件年度授权', 'stock' => 21, 'safety' => 50, 'warehouse' => '云服务仓'],
            ],
            'todos' => [
                ['title' => '复核 12 笔高风险订单', 'meta' => '销售订单 · 10:30 前', 'priority' => '高', 'tone' => 'danger'],
                ['title' => '补货申请：企业级 SSD 1TB', 'meta' => '库存中心 · 14:00 前', 'priority' => '中', 'tone' => 'warning'],
                ['title' => '同步华北大客户报价单', 'meta' => '客户管理 · 今天', 'priority' => '普通', 'tone' => 'primary'],
            ],
            'activities' => [
                ['time' => '10:24', 'title' => '财务已完成回款核销', 'content' => '订单 SO-20260719-0321 已确认到账 ¥36,000.00。', 'icon' => 'feather icon-check-circle', 'color' => 'success'],
                ['time' => '10:08', 'title' => '库存预警已触发', 'content' => '企业级 SSD 1TB 可用库存低于安全库存。', 'icon' => 'feather icon-alert-circle', 'color' => 'warning'],
                ['time' => '09:42', 'title' => '新增企业采购订单', 'content' => '深圳市云帆科技有限公司提交订单 ¥12,860.00。', 'icon' => 'feather icon-shopping-cart', 'color' => 'primary'],
                ['time' => '09:15', 'title' => '客户经理跟进已更新', 'content' => '华北区域重点客户已进入报价确认阶段。', 'icon' => 'feather icon-users', 'color' => 'info'],
            ],
            'quickActions' => [
                ['title' => '新建订单', 'description' => '快速录入销售订单', 'icon' => 'feather icon-shopping-bag', 'color' => 'primary'],
                ['title' => '发起采购', 'description' => '创建补货与采购申请', 'icon' => 'feather icon-clipboard', 'color' => 'warning'],
                ['title' => '商品入库', 'description' => '处理到货和库存变更', 'icon' => 'feather icon-inbox', 'color' => 'success'],
                ['title' => '客户建档', 'description' => '新增企业客户资料', 'icon' => 'feather icon-user-plus', 'color' => 'info'],
            ],
        ];
    }

    /**
     * 近 7 日趋势横轴：前 6 天显示实际日期，最后一天固定显示「今天」。
     */
    private static function recentDayLabels($now): array
    {
        $labels = [];
        for ($i = 6; $i >= 1; $i--) {
            $labels[] = $now->copy()->subDays($i)->format('n/j');
        }
        $labels[] = '今天';

        return $labels;
    }
}
