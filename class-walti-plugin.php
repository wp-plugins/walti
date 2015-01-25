<?php

class Walti_Plugin
{
	private $name;
	private $schedule;
	private $queued;
	private $queued_at;

	static private $schedule_options = array( 'day', 'week', 'month', 'off' );

	static public function createFromApiResult($result)
	{
		$plugin = new self();
		$plugin->name = $result->name;
		$plugin->schedule = $result->schedule;
		$plugin->queued = $result->queued;
		$plugin->queued_at = $result->queued_at;
		return $plugin;
	}

	/**
	 * このプラグインのスキャンがキューに存在するか
	 *
	 * @return bool
	 */
	public function isQueued()
	{
		return $this->queued;
	}

	/**
	 * プラグイン名を取得する
	 *
	 * @return string プラグイン名
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * キューが登録された日時を返す
	 *
	 * @return int
	 */
	public function getQueuedAt()
	{
		return $this->queued_at;
	}

	/**
	 * スケジュールを取得する
	 *
	 * @return string day,week,month,offのいずれかの値
	 */
	public function getSchedule()
	{
		return $this->schedule;
	}

	/**
	 * スキャンスケジュールをセットする
	 *
	 * @param string $schedule スケジュール
	 */
	public function setSchedule($schedule)
	{
		if ( ! in_array( $schedule, self::$schedule_options ) ) {
			throw new Exception( '不正なスケジュールが指定された' );
		}
		$this->schedule = $schedule;
	}
}