<?php

class Order extends BaseModel {
	
	const INCOMING = 'incoming'; //поступило с сайта
	const CANCELED = 'canceled'; //отменено с сайта
	const ACCEPTED = 'accepted'; //принято в 1с
	const APPROVAL = 'approval'; //1с отправил для подтверждения
	const APPROVED = 'approved'; //подтверждено пользователем
	const DISAPPROVED = 'disapproved'; //не подтверждено пользователем
	const PREPRESS = 'prepress'; //отправлено в дизайн
	const DEPLOY = 'deploy'; //принято в производство
	const FINISHED = 'finished';
	
	const FIELD_COMMENT = 'comment'; //поле комментария
	
	public $id;
	public $uid;
	public $title;
	public $id_1c;
	public $number_1c;
	public $status;
	public $pid;
	public $date_created;
	public $date_changed;
	public $date_due;
	public $urgent;
	
	public $username; //uid->username
	public $gid; //uid->gid
	public $photopolymer_name; //pid->name
	public $photopolymer_id_1c; //pid->id_1c
	
	public static function tableName() {
		return 'orders';
	}
	
	/*
		is...
	*/
	
	public function isIncoming() {
		return $this->status==Order::INCOMING;
	}
	
	public function isCanceled() {
		return $this->status==Order::CANCELED;
	}
	
	public function isAccepted() {
		return $this->status==Order::ACCEPTED;
	}
	
	public function isPrepress() {
		return $this->status==Order::PREPRESS;
	}
	
	public function isFinished() {
		return $this->status==Order::FINISHED;
	}
	
	public function isApproval() {
		return $this->status==Order::APPROVAL;
	}
	
	public function isApproved() {
		return $this->status==Order::APPROVED;
	}
	
	public function isDisapproved() {
		return $this->status==Order::DISAPPROVED;
	}
	
	public function isUrgent() {
		return $this->urgent==1;
	}
	
	/*
		can...
	*/
	
	public function canEdit() {
		return $this->isIncoming();
	}
	
	public function canCancel() {
		return $this->isIncoming();
	}
	
	public function canDelete() {
		return $this->isCanceled();
	}
	
	public function canComment() {
		return $this->isIncoming() || $this->isCanceled() || $this->isAccepted() || $this->isPrepress();
	}
	
	public function canApprove() {
		return $this->isApproval();
	}
	
	public function canRepeat() {
		return $this->isFinished();
	}
	
	public function canDuplicate() {
		return true;
	}
	
	/*
		actions
	*/
	
	public function remove($forced = false) {
		return !is_null($this->id) && ($forced || (!$forced && $this->canDelete())) && self::delete([self::field('id') . ' = ' . $this->id]);
	}
	
	public function cancel() {
		return $this->canCancel() && $this->setState(Order::CANCELED);
	}
	
	public function approve() {
		return $this->canApprove() && $this->setState(Order::APPROVED);
	}
	
	public function disapprove() {
		return $this->canApprove() && $this->setState(Order::DISAPPROVED);
	}
	
	public function setState($state) {
		return !is_null($this->id) && $this->edit(['status'], [$state]);
	}
	
	public function edit($fields, $values) {
		if (is_null($this->id)) return false;
		
		$success = self::editById($this->id, $fields, $values);
		if ($success) {
			foreach ($fields as $i => $field) {
				$this->$field = $values[$i];
				
				//хак, при обновлении pid, меняем photopolymer_name и photopolymer_id_1c
				if ($field=="pid") {
					$photopolymer = Photopolymer::byId($values[$i]);
					if (!is_null($photopolymer)) {
						$this->photopolymer_name = $photopolymer->name;
						$this->photopolymer_id_1c = $photopolymer->id_1c;
					}
				}
			}
		}
		return $success;
	}
	
	/*
		scheme
	*/
	
	private static $statuses = null;
	public static function getStatuses() {
		if (!is_null(self::$statuses)) return self::$statuses;
		$column = self::column('status');
		self::$statuses = !is_null($column) ? $column->enum() : array();
		return self::$statuses;
	}
	
	/*
		static
	*/
	
	public static function getCountTotal($filter, $gid = null) {
		return self::getCount(self::createWhere($filter, $gid), self::createJoin());
	}
	
	public static function getAll($fields, $filter, $gid = null, $order_by = null, $range = '0, 10') {
		$fields[] = self::field('uid');
		$fields[] = self::field('id', null, 'id');
		$fields[] = self::field('username', User::tableName(), 'username');
		$fields[] = self::field('name', Photopolymer::tableName(), 'photopolymer_name');
		return self::selectRows($fields, self::createWhere($filter, $gid), self::createJoin(), $order_by, $range);
	}
	
	private static function createWhere($filter, $gid = null) {
		$where = array();
		if (!is_null($gid)) 
			$where[] = self::field('gid', User::tableName()) . ' = ' . $gid;
		if (!is_null($filter->status) && count($filter->status)>0) {
			$statuses = array();
			foreach ($filter->status as $status) {
				$statuses[] = self::field('status') . ' = "' . $status . '"';
			}
			$where[] = self::where($statuses, 'or');
		}
		
		if (!is_null($filter->search))				$where[] = self::field('title') . ' REGEXP "' . $filter->searchRegexp() . '"';
		
		$date_due_equal = is_null($filter->date_due_start) || is_null($filter->date_due_end);
		$date_created_equal = is_null($filter->date_created_start) || is_null($filter->date_created_end);
		
		if (!is_null($filter->date_due_start))		$where[] = self::field('date_due') . ' ' . ($date_due_equal ? '=' : '>=') . ' "' . $filter->date_due_start . '"';
		if (!is_null($filter->date_due_end))		$where[] = self::field('date_due') . ' ' . ($date_due_equal ? '=' : '<=') . ' "' . $filter->date_due_end . '"';
		if (!is_null($filter->date_created_start))	$where[] = self::field('date_created') . ' ' . ($date_created_equal ? '=' : '>=') . ' "' . $filter->date_created_start . '"';
		if (!is_null($filter->date_created_end))	$where[] = self::field('date_created') . ' ' . ($date_created_equal ? '=' : '<=') . ' "' . $filter->date_created_end . '"';
		if (!is_null($filter->username) && $filter->username!=0) $where[] = self::field('uid') . ' = ' . $filter->username;
		return $where;
	}
	
	private static function createJoin() {
		$join = array();
		$join[] = self::inner('id', User::tableName(), 'uid');
		$join[] = self::inner('id', Photopolymer::tableName(), 'pid');
		return $join;
	}
	
	public static function byId($id, $gid = null) {
		$fields = array();
		$fields[] = self::field('*');
		$fields[] = self::field('username', User::tableName(), 'username');
		$fields[] = self::field('gid', User::tableName(), 'gid');
		$fields[] = self::field('name', Photopolymer::tableName(), 'photopolymer_name');
		$fields[] = self::field('id_1c', Photopolymer::tableName(), 'photopolymer_id_1c');
		
		$join = array();
		$join[] = self::inner('id', User::tableName(), 'uid');
		$join[] = self::inner('id', Photopolymer::tableName(), 'pid');
		
		$where = array();
		$where[] = self::field('id') . ' = ' .$id;
		if (!is_null($gid))
			$where[] = self::field('gid', User::tableName()) . ' = ' . $gid;
		
		return self::selectRow($fields, $where, $join);
	}
	
	public static function add($fields, $values) {
		$fields[] = 'uid';
		$values[] = Account::getId();
			
		$fields[] = 'date_created';
		$values[] = date('Y-m-d H:i:s');
		
		$fields[] = 'date_changed';
		$values[] = date('Y-m-d H:i:s');
		
		return self::insertRow($fields, $values);
	}
	
	public static function editById($id, $fields, $values) {
		$fields[] = 'date_changed';
		$values[] = date('Y-m-d H:i:s');
			
		$where = array();
		$where[] = self::field('id') . ' = ' . $id;
		return self::update($fields, $values, $where);
	}
	
}

?>