<?php

include_once '../app/controllers/BaseFieldController.php';
include_once '../core/interfaces/IRedirect.php';

class FieldAddController extends BaseFieldController implements IRedirect {
	
	public $types;
	
	public function beforeRender() {
		if (!$this->hasPage()) return;
		
		if ($this->add()) {
			$this->view = 'system/redirect';
			return;
		}
		
		$this->types = Field::getTypes();
		$this->view = 'admin/field/add';
	}
	
	//IRedirect
	public function getRedirect() {
		return new Redirect(View::str('field_successfuly'), Application::$routes->byName(Route::FIELD_PAGE)->path . '?page=' . get('page'));
	}
	
	private function add() {
		if ($this->formValidate(['name', 'type', 'weight'])) {
			if (Field::add(get('page'), post('type'), post('name'), post('value'), checkbox2bool(post('mandatory')), int(post('weight')))) {
				return true;
			} else {
				$this->addAlert(sprintf(View::str('error_field_add'), post('name')), 'danger');
			}
		}
		return false;
	}
	
}

?>