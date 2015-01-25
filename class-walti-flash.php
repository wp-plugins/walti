<?php

class Walti_Flash
{
	const TYPE_SUCCESS = 10;
	const TYPE_INFO = 20;
	const TYPE_WARNING = 30;
	const TYPE_DANGER = 40;

	private static $VALID_TYPES = array(
		self::TYPE_SUCCESS,
		self::TYPE_INFO,
		self::TYPE_WARNING,
		self::TYPE_DANGER,
	);

	private static $TYPE_CSS_CLASSES = array(
		self::TYPE_SUCCESS => 'walti-success',
		self::TYPE_INFO => 'walti-info',
		self::TYPE_WARNING => 'walti-warning',
		self::TYPE_DANGER => 'walti-danger',
	);

	/**
	 * メッセージを登録する
	 *
	 * @param string $message メッセージ
	 */
	public static function success( $message )
	{
		return self::add( self::TYPE_SUCCESS, $message );
	}

	/**
	 * メッセージを登録する
	 *
	 * @param string $message メッセージ
	 */
	public static function info( $message )
	{
		return self::add( self::TYPE_INFO, $message );
	}

	/**
	 * メッセージを登録する
	 *
	 * @param string $message メッセージ
	 */
	public static function warning( $message )
	{
		return self::add( self::TYPE_WARNING, $message );
	}

	/**
	 * メッセージを登録する
	 *
	 * @param string $message メッセージ
	 */
	public static function danger( $message )
	{
		return self::add( self::TYPE_DANGER, $message );
	}

	/**
	 * フラッシュメッセージを追加する
	 *
	 * @param int $type メッセージの種類
	 * @param string $message メッセージ
	 */
	private static function add( $type, $message )
	{
		if ( ! self::isValidType($type) ) {
			throw new InvalidArgument('不正なtypeが指定された');
		}

		$messages = get_transient( 'walti_flash_messages' );
		if ( false === $messages ) {
			$messages = array();
		}
		$messages[$type][] = $message;
		set_transient( 'walti_flash_messages', $messages, 60 );
	}

	/**
	 * フラッシュメッセージを取得する
	 *
	 * メッセージは取得後に消去される
	 *
	 * @return string[] フラッシュメッセージの配列
	 */
	public static function flash()
	{
		$messages = get_transient( 'walti_flash_messages' );
		if ( false === $messages ) {
			return array();
		}
		delete_transient( 'walti_flash_messages' );
		return $messages;
	}

	/**
	 * フラッシュメッセージを表示する
	 *
	 */
	public static function display()
	{
		$all_messages = self::flash();
		krsort($all_messages, SORT_NUMERIC);
		foreach ($all_messages as $type => $messages) {
			$css_class = self::$TYPE_CSS_CLASSES[$type];
			foreach ($messages as $message) {
				echo "<div class=\"walti-message\"><div class=\"{$css_class}\">{$message}</div></div>";
			}
		}
	}

	/**
	 * フラッシュメッセージの種類が妥当かどうか
	 *
	 * @param int $type フラッシュメッセージの種類
	 * @return bool 
	 */
	private function isValidType($type)
	{
		return in_array($type, self::$VALID_TYPES);
	}
}