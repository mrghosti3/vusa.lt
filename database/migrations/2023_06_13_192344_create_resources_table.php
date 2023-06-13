<?php

use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('padalinys_id');
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            $table->boolean('is_reservable')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        $permissions = PermissionService::getPermissionsToCreate(['resources']);
        PermissionService::createPermissionsForModel($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');

        $permissions = PermissionService::getPermissionsToCreate(['resources']);
        Permission::whereIn('name', $permissions)->delete();
    }
};
