<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateTagsTable extends Migration {

  public function up()
  {
    Schema::create('tagging_tagged', function(Blueprint $table) {
      
      if (!Schema::hasColumn('tagging_tagged', 'user_id'))
      {
          $table->integer('user_id')->unsigned()->index();
      }

      if (!Schema::hasColumn('tagging_tagged', 'user_scope'))
      {
          $table->integer('user_scope')->unsigned()->index();
      }
    });


  }

  public function down()
  {

  }
}
