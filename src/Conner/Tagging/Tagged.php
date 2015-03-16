<?php namespace Conner\Tagging;

/**
 * Copyright (C) 2014 Robert Conner
 */
class Tagged extends \Eloquent {

  protected $table = 'tagging_tagged';
  public $timestamps = false;
  protected $appends = ['name', 'slug', 'scope'];
  protected $fillable = ['tag_name', 'tag_slug'];
  protected $hidden = array('user_id', 'user_scope', 'taggable_type', 'taggable_id');

  public function taggable() {
    return $this->morphTo();
  }

  public function getNameAttribute(){
    return $this->tag_name;
  }

  public function getSlugAttribute(){
    return $this->tag_slug;
  }

  public function getIdAttribute()
  {
    $obj = \Tag::where('slug', $this->tag_slug)->first();
    return $obj->id;
  }

  public function getScopeAttribute(){
    return $this->user_scope;
  }
}