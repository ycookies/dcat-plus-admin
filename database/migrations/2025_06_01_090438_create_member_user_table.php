<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username',50)->default('')->comment('用户名');
            $table->string('phone',20)->default('')->comment('手机');
            $table->string('email',100)->nullable()->comment('邮箱');
            $table->string('password',100)->comment('密码');
            $table->timestamp('lastLoginTime')->nullable()->comment('上次登录时间');
            $table->string('lastLoginIp',20)->nullable()->comment('上次登录Ip');
            $table->tinyInteger('phoneVerified')->nullable()->comment('手机已验证');
            $table->tinyInteger('emailVerified')->nullable()->comment('邮箱已验证');
            $table->string('avatar',200)->nullable()->comment('头像(小)');
            $table->string('avatarMedium',200)->nullable()->comment('头像(中)');
            $table->string('avatarBig',200)->nullable()->comment('头像(大)');
            $table->tinyInteger('gender')->nullable()->comment('性别');
            $table->string('realname',20)->nullable()->comment('真实姓名');
            $table->string('signature',255)->nullable()->comment('个性签名');
            $table->integer('vipId')->default('1')->nullable()->comment('vipID');
            $table->dateTime('vipExpire')->nullable()->comment('vip过期时间');
            $table->string('nickname',100)->nullable()->comment('昵称');
            $table->tinyInteger('status')->nullable()->comment('状态');
            $table->float('balance',10,2)->default('0.00')->nullable()->comment('余额');
            $table->float('freeze_price',10,2)->default('0.00')->nullable()->comment('冻结金额');
            $table->integer('groupId')->nullable()->comment('所属分组');
            $table->bigInteger('deleteAtTime')->nullable()->comment('删除时间');
            $table->tinyInteger('isDeleted')->nullable()->comment('已删除');
            $table->integer('messageCount')->nullable()->comment('未读消息数量');
            $table->string('registerIp',20)->nullable()->comment('注册IP');
            $table->tinyInteger('is_certified')->default('0')->nullable()->comment('实名认证');
            $table->integer('parent_id')->nullable()->comment('上级');
            $table->integer('temp_parent_id')->nullable()->comment('临时上级');
            $table->dateTime('junior_at')->nullable()->comment('成为下级时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_users');
    }
}
