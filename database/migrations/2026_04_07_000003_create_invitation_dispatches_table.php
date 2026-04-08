<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('message_type', 20);
            $table->text('outbound_message')->nullable();
            $table->string('outbound_asset_url')->nullable();
            $table->string('provider', 30)->default('zapi');
            $table->string('provider_message_id')->nullable();
            $table->string('provider_zaap_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('delivery_status', 30)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index(['guest_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_dispatches');
    }
};
