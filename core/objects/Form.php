<?php

class Form {
	
	protected $fields = [];
	
	public function loadFields($route, $gid = null) {
		if (is_null($gid)) $gid = Account::getGid();
		$this->fields = array_merge(Field::getAll($route, false), GroupField::getAll($route, $gid));
		$this->parsePost();
	}
	
	public function fieldsMandatory() {
		$mandatory = array();
		foreach($this->fields as $field) {
			if ($field->isMandatory()) {
				$mandatory[] = $field->name;
			}
		}
		return $mandatory;
	}
	
	public function fieldsAll() {
		$all = array();
		foreach($this->fields as $field) {
			if (!$field->canUse()) continue;
			$all[] = $field->name;
		}
		return $all;
	}
	
	public function fieldsAllID() {
		$all = array();
		foreach($this->fields as $field) {
			if (!$field->canUse()) continue;
			$all[] = $field->id;
		}
		return $all;
	}
	
	public function render() {
		$html = View::formValidate();
		foreach($this->fields as $field) {
			$method = 'field_' . $field->name;
			if (method_exists($this, $method)) {
				$html .= $this->{$method}($field);
			} else {
				$html .= $this->view($field);
			}
		}
		return $html;
	}
	
	public function view($field) {
		switch ($field->type) {
			case 'hidden':
				return View::input($field->name, $field->type, $field->getValue());
			case 'select':
			case 'multiple':
				return View::formNormal($field->name, $field->type, $field->getValue(), false, true, $field->session);
			case 'checkbox':
				return View::formOffset($field->name, $field->type, $field->session);
			case 'file':
			case 'files':
			case 'submit':
				return View::formOffset($field->name, $field->type, $field->getValue());
			default:
				return View::formNormal($field->name, $field->type, $field->session);
		}
	}
	
	public function setValue($array) {
		foreach ($this->fields as $field) {
			if (array_key_exists($field->name, $array)) {
				$field->value = $array[$field->name];
			}
		}
	}
	
	public function setSession($array) {
		foreach ($this->fields as $field) {
			if (array_key_exists($field->name, $array)) {
				$field->session = $array[$field->name];
			}
		}
	}
	
	private function parsePost() {
		foreach ($this->fields as $field) {
			if (!hasPost($field->name)) continue;
			switch ($field->type) {
				case 'checkbox':
					$_POST[$field->name] = checkbox2bool(post($field->name));
				break;
			}
			$field->session = post($field->name);
		}
	}
	
}

?>