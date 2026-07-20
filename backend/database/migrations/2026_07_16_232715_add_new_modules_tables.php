<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modificar orders.status (Solução nativa para PostgreSQL)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'awaiting_payment'::character varying, 'paid'::character varying, 'failed'::character varying, 'refunded'::character varying, 'partially_refunded'::character varying, 'chargeback'::character varying]::text[]))");
        }

        if (!Schema::hasColumn('orders', 'refunded_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('total');
            });
        }

        // 2. Posts
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('author');
                $table->longText('content');
                $table->string('cover_image')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->timestamps();
            });
        }

        // 3. Reviews
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained();
                $table->foreignId('product_id')->constrained();
                $table->foreignId('customer_id')->nullable()->constrained('users');
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->enum('status', ['private', 'published'])->default('private');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('review_settings')) {
            Schema::create('review_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(true);
                $table->boolean('auto_publish')->default(false);
                $table->json('suggested_phrases')->nullable();
                $table->timestamps();
            });
        }

        // 4. Store Settings
        if (!Schema::hasTable('store_settings')) {
            Schema::create('store_settings', function (Blueprint $table) {
                $table->id();
                $table->string('store_name')->default('MTD STORE');
                $table->text('description')->nullable();
                $table->string('cnpj')->nullable();
                $table->boolean('maintenance_mode')->default(false);
                $table->boolean('require_login')->default(false);
                $table->string('contact_email')->nullable();
                $table->json('social_links')->nullable();
                $table->json('business_hours')->nullable();
                $table->boolean('show_business_hours')->default(false);
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->string('primary_color', 7)->default('#dc2626');
                $table->string('secondary_color', 7)->default('#991b1b');
                $table->timestamps();
            });
        }

        // 5. Webhooks
        if (!Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url');
                $table->json('events');
                $table->string('secret')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
                $table->string('event');
                $table->json('payload');
                $table->unsignedSmallInteger('response_status')->nullable();
                $table->text('response_body')->nullable();
                $table->timestamps();
            });
        }

        // 6. Afiliados
        if (!Schema::hasTable('affiliate_withdrawals')) {
            Schema::create('affiliate_withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained();
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('affiliates', 'min_withdrawal')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->decimal('min_withdrawal', 10, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('affiliates', 'cookie_duration_days')) {
            Schema::table('affiliates', function (Blueprint $table) {
                $table->unsignedSmallInteger('cookie_duration_days')->default(30);
            });
        }

        // 7. Users
        if (!Schema::hasColumn('users', 'banned_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('banned_at')->nullable();
                $table->text('ban_reason')->nullable();
            });
        }

        // 8. UTM Tracking
        if (!Schema::hasTable('visits')) {
            Schema::create('visits', function (Blueprint $table) {
                $table->id();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('session_id');
                $table->foreignId('customer_id')->nullable()->constrained('users');
                $table->timestamp('visited_at');
            });
        }

        // 9. Variações de Produto
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('product_stock_items', 'variant_id')) {
            Schema::table('product_stock_items', function (Blueprint $table) {
                $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            });
        }

        // 10. Products e Categories
        if (!Schema::hasColumn('products', 'post_purchase_instructions')) {
            Schema::table('products', function (Blueprint $table) {
                $table->longText('post_purchase_instructions')->nullable();
            });
        }

        if (!Schema::hasColumn('products', 'delivery_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->enum('delivery_type', ['unique_key', 'file_download'])->default('unique_key');
            });
        }

        if (!Schema::hasColumn('categories', 'order')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->integer('order')->default(0);
            });
        }

        // 11. Cupons Avançados
        if (!Schema::hasColumn('coupons', 'min_purchase_amount')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->decimal('min_purchase_amount', 10, 2)->nullable();
            });
        }

        if (!Schema::hasColumn('coupons', 'allowed_payment_methods')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->json('allowed_payment_methods')->nullable();
            });
        }

        if (!Schema::hasTable('coupon_category')) {
            Schema::create('coupon_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->unique(['coupon_id', 'category_id']);
            });
        }

        if (!Schema::hasTable('coupon_allowed_user')) {
            Schema::create('coupon_allowed_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unique(['coupon_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_allowed_user');
        Schema::dropIfExists('coupon_category');
        
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['min_purchase_amount', 'usage_limit', 'expires_at', 'allowed_payment_methods']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['post_purchase_instructions', 'delivery_type']);
        });

        Schema::table('product_stock_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });

        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('visits');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_at', 'ban_reason']);
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn(['min_withdrawal', 'cookie_duration_days']);
        });

        Schema::dropIfExists('affiliate_withdrawals');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('review_settings');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('posts');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'awaiting_payment'::character varying, 'paid'::character varying, 'failed'::character varying, 'refunded'::character varying]::text[]))");
        }
    }
};
