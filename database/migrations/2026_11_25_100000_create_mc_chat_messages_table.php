<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mc_chat_messages')) {
            return;
        }

        Schema::create('mc_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ign', 16)->index();
            $table->string('body', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mc_chat_messages');
    }
};
