<?php

class Walti_Organization
{
	private $status;
	private $name;
	private $description;
	private $has_payment_account;

	/**
	 * Waltiから組織情報を取得する
	 *
	 * @param Walti_Api $api
	 * @return self 組織情報
	 */
	static public function fetch( Walti_Api $api )
	{
		$result = $api->get( '/v1/me' );
		if ( ! $result->authSucceeds() ) {
			// APIキー・シークレットが無効
			return false;
		} else if ( ! $result->isSucceeded() ) {
			throw new Exception( "組織情報の取得に失敗しました code:{$result->getCode()}" );
		}
		$body = $result->getDecodedBody();
		$org = new self();
		$org->status = $body->status;
		$org->name = $body->name;
		$org->description = $body->description;
		$org->has_payment_account = $body->has_payment_account;
		return $org;
	}

	/**
	 * クレジットカードの情報が登録されているか
	 *
	 * @return bool 登録されている場合はtrue、そうでない場合はfalseを返す
	 */
	public function hasPaymentAccount()
	{
		return $this->has_payment_account;
	}

	/**
	 * 組織名を取得する
	 *
	 * @return string 組織名
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * ステータスを取得する
	 *
	 * @return int ステータス
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * 説明文を取得する
	 *
	 * @return string 説明文
	 */
	public function getDescription()
	{
		return $this->description;
	}
}