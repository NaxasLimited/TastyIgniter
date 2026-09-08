<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('naxas_restaurant_ops_pos_orders')
            || Schema::hasColumn('naxas_restaurant_ops_pos_orders', 'reservation_id')) {
            return;
        }

        Schema::table('naxas_restaurant_ops_pos_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('reservation_id')->nullable()->after('order_id');
            $table->index('reservation_id', 'rops_pos_reservation_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('naxas_restaurant_ops_pos_orders')
            || ! Schema::hasColumn('naxas_restaurant_ops_pos_orders', 'reservation_id')) {
            return;
        }

        Schema::table('naxas_restaurant_ops_pos_orders', function (Blueprint $table): void {
            $table->dropIndex('rops_pos_reservation_index');
            $table->dropColumn('reservation_id');
        });
    }
};
