<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Widgets\Form as WidgetForm;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Form as WidgetsForm;
use Dcat\Admin\Widgets\Tab;
use Dcat\Admin\Widgets\Modal;

// 基本配置
class WebConfigController extends Controller {

    public function index(Content $content) {
        return $content
            ->header('基本配置')
            ->description('')
            ->breadcrumb(['text' => '基本配置', 'uri' => ''])
            ->body($this->pageMain());
    }

    // 页面
    public function pageMain() {
        $tab  = Tab::make();
        // 第一个参数是选项卡标题，第二个参数是内容，第三个参数是是否选中
        $tab->add('后台配置', $this->tabAdmin(),false,'setting-tabAdmin');
        $tab->add('网站信息', $this->tabWeb(),false,'setting-tabWeb');
        $tab->add('短信/邮件', $this->tab0(),false,'setting-0');
        $tab->add('资源上传', $this->tab1(),false,'setting-1');
        $tab->add('微信支付', $this->tab22(),false,'setting-22');
        $tab->add('微信支付服务商', $this->tab2(),false,'setting-2');
        $tab->add('微信公众号', $this->tab3(),false,'setting-3');
        $tab->add('微信小程序', $this->tab4(),false,'setting-4');
        $tab->add('微信开放平台', $this->tab5(),false,'setting-5');
        $tab->add('企业微信', $this->tab6(),false,'setting-6');
        //$tab->add('平台服务热线', $this->tab7(),false,'setting-7');
        //$tab->add('数电发票', $this->tab8(),false,'setting-8');

        //$tab->add('子帐号',$this->tab3());
        // 添加选项卡链接
        //$tab->addLink('跳转链接', 'http://xxx');
        return $tab->withCard();
    }
    public function tabAdmin(){
        $formdata = admin_setting_group('sys_setting');
        $form1    = new WidgetsForm($formdata);
        $form1->confirm('确认已经填写完整了吗？');
        $form1->action('web-config/save');
        $form1->hidden('group_name')->value('sys_setting');
        $form1->radio('site_lang', '网站语言')
            ->options([
                'zh_CN' => '中文（简体）',
                'zh_TW' => '中文（繁体）',
                'en' => 'English'
            ])
            ->default(admin_setting('site_lang'));

        $form1->url('site_url', '网站网址')
            ->help('站点域名决定了静态资源（头像、图片等）的显示路径，可以包含端口号，例如 https://www.dcat-admin.com:8000')
            ->default(admin_setting('site_url'));
       /* $form1->text('site_title', Support::trans('main.site_title'))
            ->default(admin_setting('site_title'));
        $form1->text('site_logo_text', Support::trans('main.site_logo_text'))
            ->help('文本LOGO显示的优先度低于图片，当没有上传图片作为LOGO时，此项将生效')
            ->default(admin_setting('site_logo_text'));
        $form1->image('site_logo', Support::trans('main.site_logo'))
            ->autoUpload()
            ->uniqueName()
            ->default(admin_setting('site_logo'));
        $form1->image('site_logo_mini', Support::trans('main.site_logo_mini'))
            ->autoUpload()
            ->uniqueName()
            ->default(admin_setting('site_logo_mini'));
        $form1->switch('site_debug', Support::trans('main.site_debug'))
            ->help('*必须在env中打开APP_DEBUG设为 true，打开显示开发工具和异常捕获信息，关闭则只返回500状态码')
            ->default(admin_setting('site_debug'));*/


        $form1->disableResetButton();
        $card = Card::make('', $form1);
        return $card;
    }

    public function tabWeb(){
        $formdata = admin_setting_group('web_setting');
        $form1    = new WidgetsForm($formdata);
        $form1->confirm('确认已经填写完整了吗？');
        $form1->action('web-config/save');
        //$form1->html('<h3>短信</h3>');
        $form1->hidden('group_name')->value('web_setting');
        $form1->text('web_name','网站名称');
        $form1->text('web_slogan','网站标题');
        $form1->text('web_Domain','网站域名');
        $form1->text('web_url','网址地址');
        $form1->text('web_keywords','网站关键字');
        $form1->photo('web_logo','网站logo')
                    ->nametype('datetime')
                    ->remove(true)
                    ->help('单图，可删除,文件重命名方式:datetime');
        $form1->photo('web_ico','网站ICO')
                ->nametype('datetime')
                ->remove(true);
        $form1->textarea('web_description','网站描述');

        $form1->disableResetButton();
        $card = Card::make('', $form1);
        return $card;
    }

    // 短信-邮件
    public function tab0() {
        $formdata = admin_setting_group('sms_mail');
        $form1    = new WidgetsForm($formdata);
        $form1->confirm('确认已经填写完整了吗？');
        $form1->action('web-config/save');
        $form1->html('<h3>短信</h3>');
        $form1->hidden('group_name')->value('sms_mail');
        $form1->hidden('is_refresh')->value(1);
        $form1->radio('sms_channel', '短信通道')->options(['aliyun' => '阿里云'])->value('aliyun');
        $form1->text('sms_aliyun_key', 'key')->required();
        $form1->text('sms_aliyun_secret', 'secret')->required();
        $form1->text('sms_aliyun_sign', '短信签名')->required();
        $form1->table('sms_aliyun_template', '短信模板', function (NestedForm $table) {
            $table->text('sms_aliyun_key', '模板标识')->prepend('')->required();
            $table->text('sms_aliyun_template_id', '模板code')->prepend('')->required();
            $table->text('sms_aliyun_template_name', '模板名')->prepend('')->required();
            $table->text('sms_aliyun_template_contents', '模板内容')->prepend('')->required();
        });

        // 设置小程序隐私信息
        $form2 = new WidgetsForm();
        $form2->action('/yisiSettingSave');
        $form2->confirm('确认现在提交吗？');
        $form2->html('测试短信');
        $form2->disableResetButton();

        $modal = Modal::make()
            ->lg()
            ->title('测试短信')
            ->body($form2)
            ->button('<button class="btn btn-white btn-outline">测试短信</button>');

        $form1->html($modal);
        $form1->html('请先去 <a target="_blank" href="https://cn.aliyun.com/"> 阿里云 </a> 申请短信签名写短信模板，然后拿回信息填在这里');
        $form1->divider();
        $form1->html('<h3>邮件配置</h3>');
        $form1->text('mail_host', 'SMTP 服务器')->required();
        $form1->text('mail_port', '端口')->help('只是纯数字.例：475')->required();
        $form1->radio('mail_encryption', '安全')->options(['1' => '无', '2' => 'SSL', '3' => 'TLS'])->value('1');
        $form1->text('mail_username', '账号')->required();
        $form1->text('mail_password', '密码')->required();
        $form1->text('mail_from_address', '发件人名称')->required();
        $form1->text('mail_from_test', '测试收件箱');
        $form1->table('mail_template', '邮件内容模板', function (NestedForm $table) {
            $table->text('mail_template_name', '模板名')->prepend('')->required();
            $table->textarea('mail_template_contents', '模板内容')->required();
        });
        $form1->disableResetButton();
        $card = Card::make('短信/邮件', $form1);
        return $card;
    }

    public function tab1() {

        $formdata = admin_setting_group('oss');
        $form     = new WidgetsForm($formdata);
        $form->action('web-config/save');
        $form->confirm('确认已经填写完整了吗？');
        $form->hidden('group_name')->value('oss');
        //$form->html($alert = Alert::make('内容', '提示')->success());
        $form->radio('oss_ossType', '默认上传方式')->options(['1' => '阿里云oss'])->value('1');
        $form->text('oss_bucket', '存储空间名称 Bucket')->help('注意：这里不要填-appid，点击查看教程')->width(140)->required();
        $form->text('oss_region', '所属地域 Region')
            ->help('')->placeholder('')
            ->required();
        $form->text('oss_secretId', 'SecretId')
            ->width(140)->placeholder('')
            ->required();
        $form->text('oss_secretKey', 'SecretKey')
            ->width(140)
            ->placeholder('')
            ->required();
        $form->text('oss_appId', 'AppId')
            ->help('')
            ->width(140)
            ->placeholder('');

        $form->url('oss_domain', 'Domain')->help('请补全http:// 或 https://，例如：http://img.chongyeapp.com')->width(140)->placeholder('');
        $form->disableResetButton();
        $card = Card::make('上传存储配置', $form);
        return $card;
    }
    // 微信支付
    public function tab22() {
        $formdata = admin_setting_group('wx_pay_config');
        $form     = new WidgetsForm($formdata);
        $form->action('web-config/save');
        $form->confirm('确认已经填写完整了吗？');
        $form->hidden('group_name')->value('wx_pay_config');
        $form->html('<h3>微信支付</h3>');
        $form->text('app_id', '公众号app_id')->required();
        $form->text('secret', '公众号secret')->required();
        $form->text('mch_id', '商户号')->required();
        $form->text('pay_key', '支付密钥');
        $form->text('cert_path', '证书 cert_path');
        $form->text('key_path', '证书key_path');
        $form->text('platform_pub_id', '微信支付公钥ID');
        $form->text('platform_pub_cert', '微信支付公钥')->help('公钥文件路径');
        $form->text('notify_url', '异步通知地址');
        $form->photo('photo','图片')
            ->nametype('datetime')
            ->remove(true)
            ->help('单图，可删除');
        $form->disableResetButton();
        $card = Card::make('微信支付参数', $form);
        return $card;
    }

    // 微信支付服务商
    public function tab2() {
        $formdata = admin_setting_group('wx_pay_isv');
        $form     = new WidgetsForm($formdata);
        $form->action('web-config/save');
        $form->confirm('确认已经填写完整了吗？');
        $form->hidden('group_name')->value('wx_pay_isv');
        $form->html('<h3>微信支付服务商</h3>');
        $form->text('isv_app_id', 'app_id')->required();
        $form->text('isv_secret', 'secret')->required();
        $form->text('isv_mch_id', '服务商 商户号')->required();
        $form->text('isv_key', 'api key');
        $form->text('isv_cert_path', '证书 cert_path');
        $form->text('isv_key_path', '证书key_path');
        $form->text('isv_platform_pub_id', '微信支付公钥ID');
        $form->text('isv_platform_pub_cert', '微信支付公钥')->help('公钥文件路径');
        $form->text('isv_notify_url', '异步通知地址');
        $form->disableResetButton();
        $card = Card::make('支付服务商参数', $form);
        return $card;
    }

    // 微信公众号
    public function tab3() {
        $formdata = admin_setting_group('wx_gzh');
        $form     = new WidgetsForm($formdata);
        $form->action('web-config/save');
        $form->confirm('确认已经填写完整了吗？');
        $form->hidden('group_name')->value('wx_gzh');
        $form->html('<h3>微信公众号</h3>');
        $form->text('gzh_app_id', 'app_id')->required();
        $form->text('gzh_secret', 'secret')->required();
        $form->text('gzh_notify_url', '异步通知地址')->placeholder('');
        $form->text('gzh_Token', 'Token')->placeholder('');
        $form->text('gzh_aesKey', 'aesKey')->placeholder('');
        $form->text('gzh_staff', 'staff')->placeholder('');
        $form->text('gzh_wxRobot', 'wxRobot')->placeholder('');
        $form->html('温馨提示：获取前请先确认您已获得模板消息的使用权限，并且模板消息中没有任何数据。获取后请不要到公众号后台 删除相应的模板消息，否则会影响模板消息正常使用。');
        $form->table('gzh_template', '模板消息', function (NestedForm $table) {
            $table->text('gzh_template_key', '模板字段')->prepend('')->required();
            $table->text('gzh_template_name', '模板名')->prepend('')->required();
            $table->text('gzh_template_id', '模板ID')->prepend('')->required();
        });
        $form->disableResetButton();
        $card = Card::make('公众号参数配置', $form);
        return $card;
    }

    // 微信小程序
    public function tab4() {
        $formdata = admin_setting_group('wxmin');
        $form     = new WidgetsForm($formdata);
        $form->action('web-config/save');
        $form->confirm('确认已经填写完整了吗？');
        $form->hidden('group_name')->value('wxmin');
        $form->html('<h3>微信小程序</h3>');
        $form->text('wxmin_app_id', 'app_id')->required();
        $form->text('wxmin_secret', 'secret')->required();
        $form->text('wxmin_notify_url', '异步通知地址')->placeholder('');
        $form->text('wxmin_Token', 'Token')->placeholder('');
        $form->text('wxmin_aesKey', 'aesKey')->placeholder('');
        $form->text('wxmin_staff', 'staff')->placeholder('');
        $form->text('wxmin_wxRobot', 'wxRobot')->placeholder('');
        $form->html('温馨提示：获取前请先确认您已获得模板消息的使用权限，并且模板消息中没有任何数据。获取后请不要到公众号后台 删除相应的模板消息，否则会影响模板消息正常使用。');
        $form->table('wxmin_template', '订阅消息', function (NestedForm $table) {
            $table->text('wxmin_template_name', '消息模板名')->prepend('')->required();
            $table->text('wxmin_template_id', '消息ID')->prepend('')->required();
        });
        $form->disableResetButton();
        $card = Card::make('微信小程序参数配置', $form);
        return $card;
    }

    // 微信开放平台
    public function tab5() {
        $formdata = admin_setting_group('wxopen');
        $form     = new WidgetsForm($formdata);
        $form->action('web-config/save');
        $form->confirm('确认已经填写完整了吗？');
        $form->hidden('group_name')->value('wxopen');
        $form->html('<h3>微信开放平台</h3>');
        $form->text('wxopen_app_id', 'app_id')->required();
        $form->text('wxopen_secret', 'secret')->required();
        $form->text('wxopen_Token', 'Token')->required();
        $form->text('wxopen_aesKey', 'aesKey')->required();
        $form->text('wxopen_oauth_url', '授权回调地址')->placeholder('异步通知地址');
        $form->text('wxopen_notify_url', '消息通知地址')->placeholder('异步通知地址');

        $form->disableResetButton();
        $card = Card::make('微信开放平台参数配置', $form);
        return $card;
    }

    // 企业微信
    public function tab6() {
        $formdata6 = admin_setting_group('qywx');
        $form6     = new WidgetsForm($formdata6);
        $form6->action('web-config/save');
        $form6->confirm('确认已经填写完整了吗？');
        $form6->hidden('group_name')->value('qywx');
        $form6->html('<h3>企业微信</h3>');
        $form6->text('qywx_app_id', 'app_id')->required();
        $form6->text('qywx_secret', 'secret')->required();
        $form6->text('qywx_notify_url', '异步通知地址')->placeholder('异步通知地址');
        $form6->disableResetButton();
        $card = Card::make('企业微信参数配置', $form6);
        return $card;
    }

    // 平台服务热线
    public function tab7() {
        $formdata7 = admin_setting_group('platform_fuwu_info');
        $form7     = new WidgetsForm($formdata7);
        $form7->action('web-config/save');
        $form7->confirm('确认已经填写完整了吗？');
        $form7->hidden('group_name')->value('platform_fuwu_info');

        $form7->text('fuwu_phone', '服务电话')->required();
        $form7->text('fuwu_email', '服务邮箱')->required();
        $form7->image('fuwu_wx', '企业客服微信')
            ->width(3)
            ->url('/upload/storage')
            //->dimensions(['width' => 200, 'height' => 273])
            ->removable(false)
            ->saveFullUrl()
            ->autoUpload()
            ->autoSave(false)
            ->help('图片尺寸:200*273')
            ->required();
        $form7->disableResetButton();
        $card = Card::make('平台服务热线', $form7);
        return $card;
    }

    // 数电发票
    public function tab8() {
        $formdata8 = admin_setting_group('invoice_configs');
        $form8     = new WidgetsForm($formdata8);
        $form8->action('web-config/save');
        $form8->confirm('确认已经填写完整了吗？');
        $form8->hidden('group_name')->value('invoice_configs');

        $form8->html('温馨提示：正式开票后，请小心选择开票环境');

        $form8->radio('default_debug', '环境选择')
            ->options(['debug' => '测试', 'live' => '正式'])
            ->required();

        $form8->html('<h4>测试环境配置</h4>');
        $form8->text('debug_appKey', '测试 第三方应用 APP Key')->required();
        $form8->text('debug_appSecret', '测试 第三方应用 App Secret')->required();
        $form8->text('debug_apiUrl', '测试 api请求地址')->required();
        $form8->text('debug_callbackurl', '测试 异步通知地址')->required();

        $form8->html('<h4>正式环境配置</h4>');
        $form8->text('live_appKey', '第三方应用 APP Key')->required();
        $form8->text('live_appSecret', '第三方应用 App Secret')->required();
        $form8->text('live_apiUrl', 'api请求地址')->required();
        $form8->text('live_callbackurl', '异步通知地址')->required();


        $form8->disableResetButton();
        $card = Card::make('数电发票第三方应用配置', $form8);
        return $card;
    }

    /**
     * @desc 参数存改
     */
    public function saveData(Request $request)
    {
        $group_name = $request->get('group_name');
        $validator   = \Validator::make($request->all(), [
            'group_name' => 'required',
        ], [
            'group_name.required' => '分组名 不能为空',
        ]);
        if ($validator->fails()) {
            return (new WidgetForm())->response()->error($validator->errors()->first());
        }
        $group_data = $request->except(['group_name','_token']);
        admin_setting_group($group_name,$group_data);
        if($group_name == 'sys_setting'){
            // 设置语言环境
            app()->setLocale($request->get('site_lang'));
        }

        return (new WidgetForm())->response()->success('保存成功')->refresh();
    }

        /**
         *切换语言
         */
        public function localeSwitching(Request $request) {
            $locale = $request->get('locale');
            $validator   = \Validator::make($request->all(), [
                'locale' => 'required',
            ], [
                'locale.required' => '请选择语言',
            ]);
            $support_locale_map = config('admin.support_locale_map');
            // 查找对应的 key
            $new_locale = array_search($locale, $support_locale_map);

            if (isLaravel11OrNewer()) {
                // Laravel 11+ 的处理逻辑
                // 要更新的环境变量
                $updates = [
                    'APP_LOCALE' => $new_locale,
                    'APP_FALLBACK_LOCALE' =>$new_locale,
                    'APP_FAKER_LOCALE' => $new_locale,
                ];
                updateEnv($updates);
            }else{
                // Laravel 10 及更早版本的处理逻辑
                $appPath = config_path('app.php');
                $content = file_get_contents($appPath);
                $old_locale = config('app.locale');
                // edit locale
                $content = str_replace("'$old_locale'", "'$new_locale'", $content);
                $content = str_replace("'$old_locale'","'.$new_locale.'",$content);

                file_put_contents($appPath, $content);
            }
            return (new WidgetForm())->response()->success('设置成功')->refresh();
        }
}
