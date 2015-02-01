<?php

class Walti_Credentials
{
	private $key;
	private $secret;

	/**
	 * APIキーをDBに保存する際の暗号化に使用する鍵ファイルが存在するか
	 *
	 * @return bool
	 */
	public static function isEncryptKeyReadable()
	{
		return is_readable( WALTI_PLUGIN_DIR . '/' . WALTI_KEY_FILE );
	}

	/**
	 * 認証情報が正しいか検証する
	 *
	 * @return bool 正しい場合はtrue、そうでない場合はfalseを返す
	 */
	public static function isValid($key, $secret)
	{
		$api = new Walti_Api( new self( $key, $secret ) );
		$result = $api->get( '/v1/me' );
		if ( '404' == $result->getCode() ) {
			return false;
		} else if ( ! $result->isSucceeded() ) {
			throw new Exception( "認証情報の確認に失敗しました code:{$result->getCode()}" );
		}
		return true;
	}

	/**
	 * オプションから認証情報を読み込んでインスタンスを生成する
	 *
	 * @return self
	 */
	public static function loadFromOptions()
	{
		$key = get_option( 'walti_api_key' );
		$secret = get_option( 'walti_api_secret' );
		if ( '' == $key or '' == $secret ) {
			throw new Exception( 'APIキーまたはシークレットが登録されていない' );
		}
		return new self( $key, $secret );
	}

	/**
	 * 認証情報が保存されているか
	 *
	 * @return bool 保存されている場合はtrue、そうでない場合はfalseを返す
	 */
	public static function isStored()
	{
		$key = get_option( 'walti_api_key' );
		$secret = get_option( 'walti_api_secret' );
		return ( '' != $key and '' != $secret );
	}

	/**
	 * 認証情報オブジェクトを生成する
	 *
	 * @param string $key APIキー
	 * @param string $secret APIシークレット
	 */
	public function __construct( $key, $secret )
	{
		$this->key = $key;
		$this->secret = $secret;
	}

	/**
	 * APIキーを取得する
	 *
	 * @return string APIキー
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * APIシークレットを取得する
	 *
	 * @return string APIシークレット
	 */
	public function getSecret()
	{
		return $this->secret;
	}
}