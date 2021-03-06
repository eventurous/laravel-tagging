<?php namespace Conner\Tagging;

use Illuminate\Support\Str;
use Conner\Tagging\TaggingUtil;

/**
 * Copyright (C) 2014 Robert Conner
 */
trait TaggableTrait {

	/**
	 * Return collection of tags related to the tagged model
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function tagged() {
		return $this->morphMany('Conner\Tagging\Tagged', 'taggable');
	}
	
	/**
	 * Perform the action of tagging the model with the given string
	 *
	 * @param $tagName string or array
	 * @param $user_id int
	 * @param $scope int
	 */
	public function tag($tagNames, $user_id = null, $scope = null) {
		$tagNames = TaggingUtil::makeTagArray($tagNames);
		
		foreach($tagNames as $tagName) {
			$this->addTag($tagName, $user_id, $scope);
		}
	}

	/**
	 * Return array of the tag names related to the current model
	 *
	 * @return array
	 */
	public function tagNames() {
		$tagNames = array();
		$tagged = $this->tagged()->get(array('tag_name'));

		foreach($tagged as $tagged) {
			$tagNames[] = $tagged->tag_name;
		}
		
		return $tagNames;
	}

	/**
	 * Return array of the tag slugs related to the current model
	 *
	 * @return array
	 */
	public function tagSlugs() {
		$tagSlugs = array();
		$tagged = $this->tagged()->get(array('tag_slug'));

		foreach($tagged as $tagged) {
			$tagSlugs[] = $tagged->tag_slug;
		}
		
		return $tagSlugs;
	}
	
	/**
	 * Remove the tag from this model
	 *
	 * @param $tagName string or array (or null to remove all tags)
	 */
	public function untag($tagNames=null, $user_id = null, $scope = null) {
		if(is_null($tagNames)) {
			$currentTagNames = $this->tagNames();
			foreach($currentTagNames as $tagName) {
				$this->removeTag($tagName, $user_id, $scope);
			}
			return;
		}
		
		$tagNames = TaggingUtil::makeTagArray($tagNames);
		
		foreach($tagNames as $tagName) {
			$this->removeTag($tagName);
		}
	}
	
	/**
	 * Replace the tags from this model
	 *
	 * @param $tagName string or array
	 * @param $user_id int
	 * @param $scope int
	 */
	public function retag($tagNames, $user_id = null, $scope = null) {
		$tagNames = TaggingUtil::makeTagArray($tagNames);
		$currentTagNames = $this->tagNames();
		
		$deletions = array_diff($currentTagNames, $tagNames);
		$additions = array_diff($tagNames, $currentTagNames);
		
		foreach($deletions as $tagName) {
			$this->removeTag($tagName, $user_id, $scope );
		}
		foreach($additions as $tagName) {
			$this->addTag($tagName, $user_id, $scope);
		}
	}
	
	/**
	 * Filter model to subset with the given tags
	 *
	 * @param $tagNames array|string
	 */
	public function scopeWithAllTags($query, $tagNames) {
		$tagNames = TaggingUtil::makeTagArray($tagNames);
		
		$tagNames = array_map('\Conner\Tagging\TaggingUtil::slug', $tagNames);

		foreach($tagNames as $tagSlug) {
			$query->whereHas('tagged', function($q) use($tagSlug) {
				$q->where('tag_slug', '=', $tagSlug);
			});
		}
		
		return $query;
	}
		
	/**
	 * Filter model to subset with the given tags
	 *
	 * @param $tagNames array|string
	 */
	public function scopeWithAnyTag($query, $tagNames) {
		$tagNames = TaggingUtil::makeTagArray($tagNames);

		$normalizer = \Config::get('tagging::normalizer');
		$normalizer = empty($normalizer) ? '\Conner\Tagging\TaggingUtil::slug' : $normalizer;
		
		$tagNames = array_map($normalizer, $tagNames);

		return $query->whereHas('tagged', function($q) use($tagNames) {
			$q->whereIn('tag_slug', $tagNames);
		});
	}
	
	/**
	 * Adds a single tag
	 *
	 * @param $tagName string
	 * @param $user_id int
	 * @param $scope int
	 */
	private function addTag($tagName, $user_id = null, $scope = null) {
		$tagName = trim($tagName);
		$tagSlug = TaggingUtil::slug($tagName);
		
		$previousCount = $this->tagged()->where('tag_slug', '=', $tagSlug)->take(1)->count();
		if($previousCount >= 1) { return; }
		
		$displayer = \Config::get('tagging::displayer');
		$displayer = empty($displayer) ? '\Str::title' : $displayer;
		
		$tagged = new Tagged(array(
			'tag_name'=>call_user_func($displayer, $tagName),
			'tag_slug'=>$tagSlug,
		));

		if($user_id && !empty($scope))
		{
			$tagged->user_id = $user_id;
			$tagged->user_scope = $scope;
		}
		
		$this->tagged()->save($tagged);

		TaggingUtil::incrementCount($tagName, $tagSlug, 1);
	}
	
	/**
	 * Removes a single tag
	 *
	 * @param $tagName string
	 * @param $user_id int
	 * @param $scope int
	 */
	private function removeTag($tagName, $user_id = null, $scope = null) {
		$tagName = trim($tagName);
		
		$normalizer = \Config::get('tagging::normalizer');
		$normalizer = empty($normalizer) ? '\Conner\Tagging\TaggingUtil::slug' : $normalizer;
		
		$tagSlug = call_user_func($normalizer, $tagName);
		$query = $this->tagged()->where('tag_slug', '=', $tagSlug);

		# scope of 0 can only delete their own tags
		if($scope === 0){
			$query->where('user_id', '=', $user_id);
		}
		
		if($count = $query->delete()) {
			TaggingUtil::decrementCount($tagName, $tagSlug, $count);
		}
	}
}
