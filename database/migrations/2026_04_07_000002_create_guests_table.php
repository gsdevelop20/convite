<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_e164', 25);
            $table->string('normalized_phone', 25);
            $table->json('metadata_json')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('reminder_scheduled_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->string('response_channel', 20)->default('whatsapp');
            $table->string('current_status', 40)->default('pending');
            $table->unsignedSmallInteger('companions_count')->default(0);
            $table->timestamp('last_response_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'normalized_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
