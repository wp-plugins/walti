<?php

class Walti_Util
{
	/**
	 * 現在の処理を中止して遷移元画面にメッセージを表示する
	 *
	 * @param string $slug
	 * @param string $code
	 * @param string $message
	 */
	public static function abort( $slug, $code, $message )
	{
		add_settings_error( $slug, esc_attr( $code ), $message, 'error' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		$goback = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
		wp_redirect( $goback );
		exit;
	}

	/**
	 * 環境変数からホスト名を取得する
	 *
	 * @return string ホスト名
	 */
	public static function getHostName()
	{
		if ( WP_DEBUG && defined( 'WALTI_TARGET_HOST' ) ) {
			return WALTI_TARGET_HOST;
		}

		if ( '' != $_SERVER['HTTP_HOST'] ) {
			return $_SERVER['HTTP_HOST'];
		}
		return $_SERVER['SERVER_NAME'];
	}

	/**
	 * テンプレートの内容を出力する
	 *
	 * @param string $template テンプレート名
	 * @param array $params テンプレートに渡す変数
	 */
	public static function render( $template, Array $params = array() )
	{
		$view_file = WALTI_PLUGIN_DIR . 'views/' . $template . '.php';

		if ( ! is_readable( $view_file ) ) {
			throw new Exception( "テンプレートを読み込めませんでした template:{$template}" );
		}
		extract( $params );
		include( $view_file );
	}

	/**
	 * ランダムな英数字からなる文字列を生成する
	 *
	 * @param int $length 生成する文字列の長さ
	 * @return string
	 */
	public static function makeRandomString( $length )
	{
		static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
		$str = '';
		for ( $i = 0; $i < $length; $i++) {
			$str .= $chars[mt_rand(0, 61)];
		}
		return $str;
	}

	/**
	 * 平文をXOR暗号化
	 *
	 * @param string $plaintext 暗号化するテキスト
	 * @param string $key 鍵文字列
	 * @return string XOR暗号化したものをbase64エンコードした文字列
	 */
	public static function encrypt( $plaintext, $key )
	{
		$len = strlen( $plaintext );

		if ( strlen( $key ) < $len ) {
			// 鍵長さチェック
			throw new InvalidArgumentException( '鍵の長さが平文より短い' );
		}
		$enc = "";
		for( $i = 0; $i < $len; $i++ ){
			$asciin = ord( $plaintext[$i] );
			$enc .= chr( $asciin ^ ord($key[$i]) );
		}
		$enc = base64_encode($enc);
		return $enc;
	}

	/**
	 * XOR暗号化された文字列を平文に
	 *
	 * @param string $encrypted_text 暗号化された文字列
	 * @param string $key 鍵文字列
	 * @return string 解読された平文
	 */
	public static function decrypt( $encrypted_text, $key )
	{
		$enc = base64_decode( $encrypted_text );
		$plaintext = "";
		$len = strlen($enc);
		for($i = 0; $i < $len; $i++){
				$asciin = ord($enc[$i]);
				$plaintext .= chr($asciin ^ ord($key[$i]));
		}
		return $plaintext;
	}
}
