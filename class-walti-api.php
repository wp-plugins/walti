<?php

class Walti_Api
{
	private $credentials;

	/**
	 * APIオブジェクトを生成する
	 *
	 * @param Walti_Credentials $credentials 認証情報
	 * @return self
	 */
	public function __construct( Walti_Credentials $credentials )
	{
		$this->credentials = $credentials;
	}

	/**
	 * 指定されたエンドポイントに対してGETリクエストを実行する
	 *
	 * @param string $path エンドポイント
	 * @return Walti_ApiResult リクエスト結果
	 */
	public function get( $path )
	{
		$args['headers'] = $this->makeHeaders();
		$res = wp_remote_get( API_URL . $path, $args );
		if ( is_wp_error( $res ) ) {
			throw new Exception( "GETリクエストでエラーが発生しました code:{$res->get_error_code()} message:{$res->get_error_message()}" );
		}
		return new Walti_ApiResult( $res );
	}

	/**
	 * 指定されたエンドポイントに対してPOSTリクエストを実行する
	 *
	 * @param string $path エンドポイント
	 * @param array $params APIに渡すパラメータの連想配列
	 * @return Walti_ApiRequest リクエスト結果
	 */
	public function post( $path, $params = array() )
	{
		$args['headers'] = $this->makeHeaders();
		$args['body'] = http_build_query( $params );
		$res = wp_remote_post( API_URL . $path, $args );
		if ( is_wp_error( $res ) ) {
			throw new Exception( "POSTリクエストでエラーが発生しました code:{$res->get_error_code()} message:{$res->get_error_message()}" );
		}
		return new Walti_ApiResult( $res );
	}

	/**
	 * 指定されたエンドポイントに対してPUTリクエストを実行する
	 *
	 * @param string $path エンドポイント
	 * @param array $params APIに渡すパラメータの連想配列
	 * @return Walti_ApiRequest リクエスト結果
	 */
	public function put( $path, $params = array() )
	{
		$args['headers'] = $this->makeHeaders();
		$args['method'] = 'PUT';
		$args['body'] = http_build_query( $params );
		$res = wp_remote_request( API_URL . $path, $args );
		if ( is_wp_error( $res ) ) {
			throw new Exception( "PUTリクエストでエラーが発生しました code:{$res->get_error_code()} message:{$res->get_error_message()}" );
		}
		return new Walti_ApiResult( $res );
	}

	/**
	 * APIへのリクエストの際に付与するヘッダを生成する
	 *
	 * @return array
	 */
	protected function makeHeaders()
	{
		$headers = array( 'Api-Key' => $this->credentials->getKey(), 'Api-Secret' => $this->credentials->getSecret() );
		return $headers;
	}
}
