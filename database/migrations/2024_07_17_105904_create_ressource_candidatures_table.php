<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ressource_candidatures', function (Blueprint $table) {
            $table->id();
            $table->string("original_name");
            $table->string("name");
            $table->string("url");
            $table->string("slug");
            $table->boolean('is_deleted')->default(false);
            
            $table->unsignedBigInteger('candidature_id');
            $table->foreign('candidature_id')
                    ->references('id')
                    ->on('candidatures')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ressource_candidatures');
    }
};
