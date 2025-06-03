<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberOauthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_oauth', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_user_id')->comment('用户ID');
            $table->string('type',20)->default('')->comment('类型');
            $table->string('open_id',255)->default('')->comment('OpenId');
            $table->string('info_nick',255)->default('')->comment('昵称');
            $table->string('info_avatar',255)->default('')->comment('头像');
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
        Schema::dropIfExists('member_oauth');
    }
}
