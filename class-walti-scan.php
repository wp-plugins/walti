<?php

class Walti_Scan
{
	private $plugin_name;
	private $status = '-';
	private $status_color = 'grey';
	private $result_status = '-';
	private $result_env;
	private $result_body = '-';
	private $message = '';
	private $created_at = '-';

	private $is_empty = false;

	/**
	 * 空のスキャンオブジェクトを返す
	 *
	 * @param string $plugin_name プラグイン名
	 * @return self
	 */
	public static function createEmpty($plugin_name)
	{
		$scan = new self($plugin_name);
		$scan->is_empty = true;
		return $scan;
	}

	/**
	 * スキャン結果APIのレスポンスから結果オブジェクトを生成して返す
	 *
	 * @param array $result スキャン結果APIの結果
	 * @return array
	 */
	public static function createFromApiResult($result)
	{
		$scan = new self($result->plugin);
		$scan->status = $result->status;
		$scan->status_color = $result->status_color;
		$scan->result_status = $result->result_status;
		$scan->result_env = $result->result_env;
		$scan->result_body = $result->result_body;
		$scan->message = $result->message;
		$scan->created_at = $result->created_at;
		return $scan;
	}

	/**
	 * コンストラクタ
	 *
	 * @param string $plugin_name プラグイン名
	 */
	public function __construct($plugin_name)
	{
		$this->plugin_name = $plugin_name;
	}

	/**
	 * 空のスキャン結果かどうか
	 *
	 */
	public function isEmpty()
	{
		return $this->is_empty;
	}

	/**
	 * プラグイン名を取得する
	 *
	 * @return string プラグイン名
	 */
	public function getPluginName()
	{
		return $this->plugin_name;
	}

	/**
	 * ステータスを取得する
	 *
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * ステータスの背景色を取得する
	 *
	 * @return string
	 */
	public function getStatusColor()
	{
		return $this->status_color;
	}

	/**
	 * スキャン登録時刻を取得する
	 *
	 * @return int
	 */
	public function getCreatedAt()
	{
		return $this->created_at;
	}

	/**
	 * スキャンの概要を取得する
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * スキャンオブジェクトの内容を配列に変換する
	 *
	 * @return array JSON文字列
	 */
	public function toArray()
	{
		$arr = array(
			'plugin' => $this->getPluginName(),
			'status' => $this->getStatus(),
			'status_color' => $this->getStatusColor(),
			'createdAt' => ( '-' == $this->getCreatedAt() ) ? '-' : get_date_from_gmt( date('Y-m-d H:i:s', strtotime( $this->getCreatedAt() ) ) ),
			'message' => $this->getMessage(),
		);
		return $arr;
	}
}