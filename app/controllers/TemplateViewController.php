<?php

class TemplateViewController extends BaseController {
	
	public $gid;
	public $templates;
	public $fields_view;
	
	private $route_delete;
	private $route_edit;
	
	public function beforeRender() {
		if (!hasGet('gid') && Account::isAdmin()) return;
		
		//routes
		$this->route_delete = Application::$routes->byName(Route::TEMPLATE_DELETE);
		$this->route_edit = Application::$routes->byName(Route::TEMPLATE_EDIT);
		
		if ($this->route_edit) {
			$this->save();
		}
		
		$this->gid = Account::isAdmin() ? get('gid') : Account::getGid();
		$this->templates = Template::getAll($this->gid);
		$this->fields_view = ["name"];
		
		if (count($this->templates)==0) {
			$this->addAlert(View::str('info_templates_null'), 'info');
		}
		
		//CSS/JS
		$this->addJSfile('datetimepicker.min');
		$this->addCSSfile('datetimepicker');
		$this->addCSSfile('selected.table');
		$this->addJSfile('selected.table');
		if ($this->route_delete->isAvailable()) $this->addJSparam('delete_url', $this->route_delete->path);
		if ($this->route_edit->isAvailable()) $this->addJSparam('view_url', $this->route_edit->path);
	}
	
	public function getContent() {
		if (is_null($this->gid) && Account::isAdmin()) {
			$this->pick('system/group-select');
		} else if (!is_null($this->templates)) {
			if (count($this->templates)>0) {
				$this->pick('template/all');
			}
			
			if ($this->route_edit->isAvailable()) {
				$this->pick('template/add');
			}
		}
	}
	
	private function save() {
		if ($this->formValidate(['gid', 'name']) && Template::add(post('gid'), post('name'))) {
			$this->addAlert(View::str('success_save'), 'success');
		}
	}
	
}

?>