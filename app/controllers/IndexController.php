<?php

class IndexController extends BaseController {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function getContent() {
		$this->pick('index/index');
	}
	
}

?>