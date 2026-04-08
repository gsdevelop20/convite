<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host_name');
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->string('location_name');
            $table->string('location_address');
            $table->text('invitation_message_template');
            $table->string('invitation_asset_type', 20)->default('text');
            $table->string('invitation_asset_url')->nullable();
            $table->unsignedSmallInteger('reminder_days_before')->default(15);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
