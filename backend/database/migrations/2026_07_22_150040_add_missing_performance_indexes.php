<?php

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
        Schema::table('products', function (Blueprint $table) {
            // Verifica se a coluna 'category_id' existe, que só está sendo usada nas consultas do panel
            if (Schema::hasColumn('products', 'category_id')) {
                $table->index('category_id');
            }
            if (Schema::hasColumn('products', 'is_active')) {
                $table->index('is_active');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // status e created_at já foram cobertos. Vamos cobrir user_id e created_at no plural
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->index('user_id');
            }
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropIndex(['category_id']);
            }
            if (Schema::hasColumn('products', 'is_active')) {
                $table->dropIndex(['is_active']);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->dropIndex(['user_id']);
            }
            $table->dropIndex(['created_at']);
        });
    }
};
