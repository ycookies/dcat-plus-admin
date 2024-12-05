<style>
    .mobile-box {
        width: 400px;
        height: 740px;
        padding: 35px 11px;
        background-color: #fff;
        border-radius: 30px;
        background-size: cover;
        position: relative;
        font-size: .85rem;
        float: left;
        margin-right: 1rem;
    }

    .head-bar {
        width: 378px;
        height: 64px;
        position: relative;
        background: url('{{admin_asset('@admin/images/head.png')}}') center no-repeat;
    }

    .head-bar div {
        position: absolute;
        text-align: center;
        width: 378px;
        font-size: 16px;
        font-weight: 600;
        height: 64px;
        line-height: 88px;
    }

    .mobile-box .show-box {
        height: 606px;
        width: 375px;
        overflow: auto;
        font-size: 12px;
        background-color: #f7f7f7;
    }

    .account-box {
        width: 100%;
        height: 60px;

        padding: 0 8px 8px;
    }

    .mobile-menus-box {
        width: 100%;
        background-color: #f7f7f7;
        padding: 0 8px;
        margin-top: 10px;
    }

    .mobile-menus-box .mobile-menu-title {
        padding: 10px 16px;
        font-size: 14px;
    }

    .mobile-menus-box > div {
        background-color: #fff;
        border-radius: 8px;
        height: 100%;
        padding-top: 10px;
    }

    .foot-nav {
        margin-bottom: 10px;
    }
</style>
<div id="app">
    <div class="mini-program-box">
        <div class="mobile-box">
            <div class="head-bar">
                <div>用户中心</div>
            </div>
            <div class="show-box">
                <div class="mobile-menus-box">
                    <div class="view-list">

                    </div>
                    <div class="view-row">
                        {{--<div class="mobile-menu-title">用户中心</div>--}}
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="feather icon-aperture f22 text-success"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="feather icon-briefcase f22"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="fa fa-book f22"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="fa fa-book f22"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="fa fa-book f22"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="fa fa-book f22"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="foot-nav">
                                    <div class="foot-nav-icon">
                                        <i class="fa fa-book f22"></i>
                                    </div>
                                    <div class="foot-nav-txt">我的足迹</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{--<script src="//cdn.jsdelivr.net/npm/sortablejs@1.8.3/Sortable.min.js"></script>--}}
{{--
<script src="{{admin_asset('@admin/dcat/plugins/vuedraggable@2.18.1/dist/vuedraggable.umd.min.js')}}"></script>
--}}
<script>
    Dcat.ready(function () {
        /*const app = new Vue({
            el: '#app',
            data() {
                return {
                    mobile_bg: '/statics/img/mall/mobile-background.png',
                    ruleForm: {
                        top_pic_url: '',
                        top_style: '1',
                        is_order_bar_status: '1', // 订单栏显示
                        is_foot_bar_status: '1', // 订单栏显示
                        is_menu_status: '1',
                        menu_title: '我的服务',
                        menu_style: '1',
                        menus: [],
                        order_bar: [
                            {
                                id: 1,
                                name: '待付款',
                                icon_url: '',
                            },
                            {
                                id: 2,
                                name: '待发货',
                                icon_url: '',
                            },
                            {
                                id: 3,
                                name: '待收货',
                                icon_url: '',
                            },
                            {
                                id: 4,
                                name: '已完成',
                                icon_url: '',
                            },
                            {
                                id: 5,
                                name: '售后',
                                icon_url: '',
                            },
                        ],
                        foot_bar: [
                            {
                                id: 1,
                                name: '我的收藏',
                                icon_url: '',
                            },
                            {
                                id: 2,
                                name: '我的足迹',
                                icon_url: '',
                            }
                        ],
                        account: [
                            {
                                id: 2,
                                name: '积分',
                                icon_url: '',
                            },
                            {
                                id: 3,
                                name: '余额',
                                icon_url: '',
                            },
                        ],
                        account_bar: {
                            status: '1',
                            integral: {
                                status: '1',
                                text: '积分',
                                icon: '',
                            },
                            balance: {
                                status: '1',
                                text: '余额',
                                icon: '',
                            },
                            coupon: {
                                status: '1',
                                text: '优惠券',
                                icon: '',
                            },
                            card: {
                                status: '1',
                                text: '卡券',
                                icon: '',
                            },
                        },
                    },
                    rules: {
                        top_pic_url: [
                            {required: true, message: '请选择顶部背景图片', trigger: 'change'},
                        ],
                        member_pic_url: [
                            {required: true, message: '请选择会员图标', trigger: 'change'},
                        ],
                        member_bg_pic_url: [
                            {required: true, message: '请选择普通会员背景图', trigger: 'change'},
                        ],
                    },
                    btnLoading: false,
                    cardLoading: false,
                    dialogForm: {},
                    dialogFormVisible: false,
                    dialogFormType: '',
                    dialogFormIndex: '',
                };
            },
            methods: {
                getDetail() {

                }
            },
            mounted: function () {
                this.getDetail();
            },
        });*/
    });
</script>