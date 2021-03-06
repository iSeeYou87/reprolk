<?php

Util::inc('controllers', 'order/BaseOrderController.php');
Util::inc('interfaces', 'IRedirect.php');

class OrderDisapprovedController extends BaseOrderController implements IRedirect {
	
	public function beforeRender() {
		if (!$this->loadOrder()) return;
		
		if ($this->disapprove()) {
			$this->setTemplate('empty');
			$this->view = 'system/redirect';
			return;
		}
		
		$this->addAlert(View::str('warning_order_approval'), 'warning');
	}
	
	//IRedirect
	public function getRedirect() {
		return new Redirect(View::str('order_disapproved_successfuly'), Application::$routes->byName(Route::ORDER_ALL)->path);
	}
	
	public function disapprove() {
		if ($this->order->canApprove() && $this->formValidate('comment')) {
			$order_changed = $this->order->disapprove();
			$cid = Comment::add($this->order->id, post('comment'), $this->order->status);
			return !is_null($cid) && $order_changed && $this->save($_FILES['files'], $cid);
		}
		return false;
	}
	
}

?>