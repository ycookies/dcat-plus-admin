<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultRoleIdToAdminUsersTable extends Migration
{
    public function getConnection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }

    public function up()
    {
        $connection = $this->getConnection();
        $table = config('admin.database.users_table');
        $schema = Schema::connection($connection);

        if ($schema->hasColumn($table, 'default_role_id')) {
            return;
        }

        $schema->table($table, function (Blueprint $table) {
            // A nullable value keeps existing administrators compatible until
            // their assigned roles are next saved or they next sign in.
            $table->unsignedBigInteger('default_role_id')->nullable()->index();
        });
    }

    public function down()
    {
        $connection = $this->getConnection();
        $table = config('admin.database.users_table');
        $schema = Schema::connection($connection);

        if (! $schema->hasColumn($table, 'default_role_id')) {
            return;
        }

        $schema->table($table, function (Blueprint $table) {
            $table->dropColumn('default_role_id');
        });
    }
}
