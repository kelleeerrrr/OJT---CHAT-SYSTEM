<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add role column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin', 'superadmin'])->default('user')->after('email');
            $table->boolean('is_chat_denied')->default(false)->after('role');
            $table->timestamp('chat_denied_at')->nullable()->after('is_chat_denied');
            $table->unsignedBigInteger('chat_denied_by')->nullable()->after('chat_denied_at');
            $table->foreign('chat_denied_by')->references('id')->on('users')->nullOnDelete();
        });

        // Messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->text('body');                    // sanitized content
            $table->text('body_raw')->nullable();    // original (admin audit only, never exposed)
            $table->boolean('has_bad_words')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['sender_id', 'receiver_id']);
            $table->index('created_at');
        });

        // Blocked / denied users log
        Schema::create('chat_deny_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_id');
            $table->enum('action', ['denied', 'restored']);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_deny_logs');
        Schema::dropIfExists('messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['chat_denied_by']);
            $table->dropColumn(['role', 'is_chat_denied', 'chat_denied_at', 'chat_denied_by']);
        });
    }
};
