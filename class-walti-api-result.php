<?php

/**
 * APIコール結果
 *
 */
class Walti_ApiResult
{
	private $code;
	private $headers;
	private $body;

	/**
	 * API結果オブジェクトを生成する
	 *
	 * @param array $res APIをコールした結果配列
	 */
	public function __construct( $res )
	{
		$this->code = wp_remote_retrieve_response_code( $res );
		$this->body = wp_remote_retrieve_body( $res );
		$this->headers = wp_remote_retrieve_headers( $res );
	}

	/**
	 * APIコールのHTTPステータスコードを返す
	 *
	 * @return string ステータスコード
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * 成功レスポンスかどうか
	 *
	 * @return bool 成功レスポンスの場合はtrue、そうでない場合はfalseを返す
	 */
	public function isSucceeded()
	{
		$str_code = (string)($this->code);
		return '2' == $str_code[0];
	}

	/**
	 * API結果の本文をそのまま返す
	 *
	 * @return string API結果の本文
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * API結果のJSONをデコードしたオブジェクトを返す
	 *
	 * @return stdClass 結果JSONをデコードしたオブジェクト
	 */
	public function getDecodedBody()
	{
		$decoded = json_decode( $this->body );
		if ( is_null( $decoded ) ) {
			throw new Exception( 'JSONのデコードに失敗しました' );
		}
		return $decoded;
	}

	/**
	 * APIキーによる認証が成功したかどうか
	 *
	 * @return bool
	 */
	public function authSucceeds()
	{
		if ( '404' == $this->getCode() && 'NOT ALLOWED' == $this->getBody() ) {
			return false;
		}
		return true;
	}
}