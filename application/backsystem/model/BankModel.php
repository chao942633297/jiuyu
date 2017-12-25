<?php
namespace app\backsystem\model;

use think\Model;

class BankModel extends Model{
	protected $table = 'sql_bank';

	public function users()
	{
		return $this->belongsTo('UserModel','from_uid','id');
	}

}