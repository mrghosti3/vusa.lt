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
        Schema::create('reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->json('name');
            $table->json('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->dateTime('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        $permissions = PermissionService::getPermissionsToCreate(['reservations']);
        PermissionService::createPermissionsForModel($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');

        $permissions = PermissionService::getPermissionsToCreate(['reservations']);
        Permission::whereIn('name', $permissions)->delete();
    }
};
