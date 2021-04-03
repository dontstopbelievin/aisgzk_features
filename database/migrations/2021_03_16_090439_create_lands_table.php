<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lands', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('CadNumber')->unique();
            $table->json("feature")->nullable();
            $table->json("feature_gps")->nullable();
            $table->dateTime("ActualDate")->nullable();
            $table->json("SemanticData")->nullable();
            $table->json("Geometry")->nullable();
            $table->json("GzkObject")->nullable();
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
        Schema::dropIfExists('lands');
    }
}
