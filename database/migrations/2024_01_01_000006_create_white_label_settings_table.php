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
        Schema::create('white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Branding
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('sidebar_logo_url')->nullable();
            $table->string('login_background_url')->nullable();
            
            // Colors
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#6B7280');
            $table->string('accent_color')->default('#10B981');
            $table->string('background_color')->default('#FFFFFF');
            $table->string('text_color')->default('#1F2937');
            
            // Company Info
            $table->string('company_name')->nullable();
            $table->string('app_name')->nullable();
            $table->string('tagline')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('dashboard_welcome_message')->nullable();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Custom Code
            $table->longText('custom_css')->nullable();
            $table->longText('custom_js')->nullable();
            
            // Email Templates
            $table->longText('email_template_header')->nullable();
            $table->longText('email_template_footer')->nullable();
            
            // JSON Fields
            $table->json('social_links')->nullable();
            $table->json('contact_info')->nullable();
            $table->json('features_enabled')->nullable();
            $table->json('modules_config')->nullable();
            $table->json('theme_config')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('white_label_settings');
    }
};