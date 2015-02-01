<?php

// TODO i18n

class Walti_Admin
{
	/**
	 * 初期化処理
	 *
	 */
	public static function init()
	{
		add_action( 'admin_init', array( 'Walti_Admin', 'init_options' ) );
		add_action( 'admin_menu', array( 'Walti_Admin', 'add_menu' ) );
		add_action( 'wp_ajax_walti_register_scan', array( 'Walti_Admin', 'ajax_register_scan' ) );
		add_action( 'wp_ajax_walti_get_results', array( 'Walti_Admin', 'ajax_get_results' ) );
	}

	/**
	 * オプション情報初期化
	 *
	 */
	public static function init_options()
	{
		register_setting( 'walti_api_options', 'walti_api_key', array( 'Walti_Admin', 'validate_api_credentials' ) );
		register_setting( 'walti_api_options', 'walti_api_secret' );

		add_settings_section( 'walti_setting_organization_section', __( '組織の設定' ), array( 'Walti_Admin', 'render_organization_section' ), 'walti_config' );
		add_settings_field( 'walti_api_key', __('APIキー'), array( 'Walti_Admin', 'render_option_input_field' ), 'walti_config', 'walti_setting_organization_section', array( 'name' => 'walti_api_key' ) );
		add_settings_field( 'walti_api_secret', __( 'APIシークレット' ), array( 'Walti_Admin', 'render_option_input_field'), 'walti_config', 'walti_setting_organization_section', array( 'name' => 'walti_api_secret') );

		add_filter( 'pre_update_option_walti_api_key', array('Walti_Admin', 'encrypt' ) );
		add_filter( 'pre_update_option_walti_api_secret', array('Walti_Admin', 'encrypt' ) );
		add_filter( 'option_walti_api_key', array('Walti_Admin', 'decrypt' ) );
		add_filter( 'option_walti_api_secret', array('Walti_Admin', 'decrypt' ) );
	}

	/**
	 * メニュー項目の追加
	 *
	 */
	public static function add_menu()
	{
		$option_suffix = add_options_page( 'Walti設定', 'Walti設定', 'manage_options', 'walti_config', array( 'Walti_Admin', 'render_option_page' ) );
		$scan_suffix = add_menu_page( 'Waltiスキャン', 'Waltiスキャン', 'manage_options', 'walti_scan', array( 'Walti_Admin', 'render_scan_page' ), 'dashicons-shield' );
		$schedule_suffix = add_submenu_page( null , 'Waltiスケジュール登録', 'Waltiスケジュール登録', 'manage_options', 'walti_schedule', array( 'Walti_Admin', 'render_schedule_page' ) );
		add_action( "load-{$option_suffix}" , array( 'Walti_Admin', 'option_page_load' ) );
		add_action( "load-{$scan_suffix}" , array( 'Walti_Admin', 'scan_page_load' ) );
		add_action( "load-{$schedule_suffix}" , array( 'Walti_Admin', 'schedule_page_load' ) );
		add_action( "admin_print_styles-{$option_suffix}", array( 'Walti_Admin', 'enqueue_css' ) );
		add_action( "admin_print_styles-{$scan_suffix}", array( 'Walti_Admin', 'enqueue_css' ) );
		add_action( "admin_print_styles-{$schedule_suffix}", array( 'Walti_Admin', 'enqueue_css' ) );
	}

	/**
	 * 設定ページロード時の処理
	 *
	 */
	public static function option_page_load()
	{
		add_action( 'admin_notices', array( 'Walti_Flash', 'display' ) );
		if ( isset( $_POST['operation'] ) ) {
			check_admin_referer( self::get_nonce_name( 'config_target' ) );
			$credentials = Walti_Credentials::loadFromOptions();
			$api = new Walti_Api( $credentials );

			switch ( $_POST['operation'] ) {
			case 'register':
				Walti_Target::register( $api, Walti_Util::getHostName() );
				Walti_Flash::success( 'ホストを登録しました。' );
				wp_redirect( self::getSettingPageUrl() );
				exit;
			case 'activate':
				$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
				$target->activate( $_SERVER['DOCUMENT_ROOT'] );
				Walti_Flash::success( 'ホストをアクティベートしました。' );
				wp_redirect( admin_url( '/admin.php?page=walti_scan' ) );
				exit;
			case 'deleteOwnership':
				$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
				$target->deleteOwnershipFile( $_SERVER['DOCUMENT_ROOT'] );
				Walti_Flash::success( '認証ファイルを削除しました。' );
				wp_redirect( self::getSettingPageUrl() );
				exit;
			}
		}
	}

	/**
	 * スキャンページロード時の処理
	 *
	 */
	public static function scan_page_load()
	{
		add_action( 'admin_notices', array( 'Walti_Flash', 'display' ) );
		add_action( 'admin_enqueue_scripts', array( 'Walti_Admin', 'enqueue_scan_scripts' ) );

		// TODO チェック部分を別メソッドに
		if ( ! Walti_Credentials::isStored() ) {
			Walti_Flash::danger( sprintf( "組織の設定が完了していません。<br><a href=\"%s\">設定ページ</a>からAPIキーとシークレットを登録してください。", self::getSettingPageUrl() ) );
			return;
		}
		$api = new Walti_Api( Walti_Credentials::loadFromOptions() );
		$organization = Walti_Organization::fetch( $api );
		if ( ! $organization ) {
			Walti_Flash::danger( sprintf( "APIキーが正しくありません。<br><a href=\"%s\">設定ページ</a>からAPIキー/シークレットを登録しなおしてください。", self::getSettingPageUrl() ) );
			return;
		}

		$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );

		if ( ! $target ) {
			Walti_Flash::danger( sprintf( "このホストはターゲットとして登録されていません。<br><a href=\"%s\">設定ページ</a>からターゲットの登録を行ってください。", self::getSettingPageUrl() ) );
			return;
		}

		if ( $target->isArchived() ) {
			Walti_Flash::danger( sprintf( 'このホストはアーカイブされています。<br>スキャンを実行する場合は<a href="%s" target="_blank">Walti</a>のサイトから再アクティベートしてください。', WALTI_URL . '/targets/' . Walti_Util::getHostName() ) );
			return;
		}

		if ( ! $target->isActivated() ) {
			Walti_Flash::warning( sprintf( "このホストの所有確認が完了していません。<br><a href=\"%s\">設定ページ</a>の手順に従って所有確認を完了してください。", self::getSettingPageUrl() ) );
			return;
		}

		$organization= Walti_Organization::fetch( $api );
		if ( ! $organization->hasPaymentAccount() ) {
			Walti_Flash::warning( sprintf( "組織にクレジットカードが登録されていません。<br><a href=\"%s\">Waltiのサイト</a>からクレジットカードを登録してください。", WALTI_URL) );
			return;
		}

		if ( ! isset( $_GET['action'] ) or ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}
	}

	/**
	 * スケジュールページロード時の処理
	 *
	 */
	public function schedule_page_load()
	{
		add_action( 'admin_notices', array( 'Walti_Flash', 'display' ) );
		if ( isset( $_POST[ 'schedules' ] ) ) {
			check_admin_referer( self::get_nonce_name( 'update_schedule' ) );
			$credentials = Walti_Credentials::loadFromOptions();
			$api = new Walti_Api( $credentials );
			$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );

			foreach ($_POST['schedules'] as $plugin_name => $schedule) {
				$target->getPlugin($plugin_name)->setSchedule($schedule);
			}
			$target->updateSchedules();
			Walti_Flash::success( 'スケジュールを登録しました。' );
			wp_redirect( admin_url( '/admin.php?page=walti_scan' ) );
			exit;
		}
	}

	/**
	 * CSSをロードさせる
	 *
	 */
	public static function enqueue_css()
	{
		wp_enqueue_style( 'walti', plugins_url( 'inc/css/walti.css', __FILE__) );
	}

	/**
	 * Walti設定ページのURLを取得する
	 *
	 * @return string 設定ページのURL
	 */
	private static function getSettingPageUrl()
	{
		return admin_url( '/options-general.php?page=walti_config' );
	}

	/**
	 * APIキー/シークレットのバリデーション
	 *
	 * 入力が不正な場合はリダイレクトして警告を表示する
	 *
	 * @return string APIキー
	 */
	public static function validate_api_credentials( $value )
	{
		if ( isset( $_POST['walti_api_key'] ) && '' == $_POST['walti_api_key'] && isset( $_POST['walti_api_secret'] ) && '' == $_POST['walti_api_secret'] ) {
			// どちらも空欄の場合はクリア
			delete_option( 'walti_api_key' );
			delete_option( 'walti_api_secret' );
			return $value;
		}

		if ( ! isset( $_POST['walti_api_key'] ) || '' == $_POST['walti_api_key'] ) {
			Walti_Util::abort( 'walti_config', 'invalid-credentials', __( 'APIキーを入力してください。' ) );
		}
		if ( ! isset( $_POST['walti_api_secret'] ) || '' == $_POST['walti_api_secret'] ) {
			Walti_Util::abort( 'walti_config', 'invalid-credentials', __( 'APIシークレットを入力してください。' ) );
		}

		if ( ! Walti_Credentials::isValid( $_POST['walti_api_key'], $_POST['walti_api_secret'] ) ) {
			Walti_Util::abort( 'walti_config', 'invalid-credentials', __( 'APIキーまたはシークレットが正しくありません。' ) );
		}

		return $value;
	}

	/**
	 * 設定画面の組織セクション用出力
	 *
	 */
	public static function render_organization_section()
	{
		if ( ! Walti_Credentials::isStored() ) {
			$organization_name = '(未登録)';
			$payment_account = '-';
		} else {
			$api = new Walti_Api( Walti_Credentials::loadFromOptions() );
			$organization = Walti_Organization::fetch( $api );
			if ( $organization ) {
				$organization_name = $organization->getName();
				$payment_account = $organization->hasPaymentAccount() ? 'あり' : 'なし';
			} else {
				$organization_name = '(不明な組織)';
				$payment_account = '-';
			}
		}

		echo <<<EOS
<table class="form-table">
  <tr>
    <th scope="row">現在の組織</th>
    <td>{$organization_name}</td>
  </tr>
  <tr>
    <th scope="row">クレジットカード登録</th>
    <td>{$payment_account}</td>
  </tr>
</table>
EOS;
	}

	/**
	 * 設定画面の入力項目出力
	 *
	 * @param array $args
	 */
	public static function render_option_input_field( $args )
	{
		_e( sprintf( '<input name="%s" type="text" value="%s">', $args['name'], get_option( $args['name'] ) ) );
	}

	/**
	 * 設定画面描画
	 *
	 */
	public static function render_option_page()
	{
		$args['initialized'] = false;
		if ( Walti_Credentials::isStored() ) {
			$args['initialized'] = true;
			$args['nonce_name'] = self::get_nonce_name( 'config_target' );

			$api = new Walti_Api( Walti_Credentials::loadFromOptions() );
			try {
				$args['is_authenticated'] = true;
				$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );

				if ( $target ) {
					$args['hostname'] = $target->getName();
					$args['status'] = $target->getStatusString();
					$args['ownership_filename'] = $target->getOwnershipFileName();
					$args['ownership_url'] = $target->getOwnershipUrl();
					$args['target_registered'] = true;
					$args['target_activated'] = $target->isActivated();
					$args['owner_file_writable'] = is_writable( $_SERVER['DOCUMENT_ROOT'] );
					$args['exists_owner_file'] = $target->existsOwnerFile( $_SERVER['DOCUMENT_ROOT'] );
					$args['is_activation_queued'] = ( Walti_Target::OWNERSHIP_QUEUED == $target->getOwnershipStatus() );
					$args['is_archived'] = $target->isArchived();
				} else {
					$args['hostname'] = Walti_Util::getHostName();
					$args['status'] = '未登録';
					$args['ownership_filename'] = '-';
					$args['owner_file_writable'] = is_writable( $_SERVER['DOCUMENT_ROOT'] );
					$args['target_registered'] = false;
					$args['target_activated'] = false;
				}
			} catch (WaltiAuthException $e) {
				$args['is_authenticated'] = false;
			}
		}

		$args['destination'] = 'options.php';
		Walti_Util::render( 'config', $args );
	}

	/**
	 * スキャン画面描画
	 *
	 */
	public static function render_scan_page()
	{
		if ( ! Walti_Credentials::isStored() ) {
			Walti_Util::render( 'scan', array( 'scan_enabled' => false ) );
			return;
		}

		$api = new Walti_Api( Walti_Credentials::loadFromOptions() );

		$organization = Walti_Organization::fetch( $api );
		if ( ! $organization ) {
			 Walti_Util::render( 'scan', array( 'scan_enabled' => false ) );
			 return;
		}

		$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
		if ( ! $target || ! $target->isActivated() ) {
			 Walti_Util::render( 'scan', array( 'scan_enabled' => false ) );
			 return;
		}

		$args = array(
			'scan_enabled' => true,
			'organization_name' => $organization->getName(),
			'plugins' => $target->getPlugins(),
			'scans' => $target->fetchScans()->getLatestScans(),
			'target_name' => $target->getName(),
		);
		Walti_Util::render( 'scan', $args );
	}

	/**
	 * スケジュール画面描画
	 *
	 */
	public function render_schedule_page()
	{
		$api = new Walti_Api( Walti_Credentials::loadFromOptions() );

		$organization = Walti_Organization::fetch( $api );
		if ( ! $organization ) {
			throw new Exception( '組織情報の取得に失敗' );
		}

		$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
		if ( ! $target ) {
			throw new Exception( 'ターゲット情報の取得に失敗' );
		}

		$args = array(
			'organization' => $organization,
			'target' => $target,
			'nonce_name' => self::get_nonce_name( 'update_schedule' ),
		);
		Walti_Util::render( 'schedule', $args );
	}

	/**
	 * 平文をXOR暗号化
	 *
	 * @param string $plaintext 暗号化する文字列
	 * @return string 暗号化された文字列
	 */
	public static function encrypt( $plaintext )
	{
		$key = hash('sha256', AUTH_KEY . AUTH_SALT . SECURE_AUTH_KEY . SECURE_AUTH_SALT);
		return Walti_Util::encrypt( $plaintext, $key );
	}

	/**
	 * 暗号化された文字列を解読する
	 *
	 * @param string $encrypted_text 暗号化された文字列
	 * @return string 解読された平文
	 */
	public static function decrypt( $encrypted_text )
	{
		$key = hash('sha256', AUTH_KEY . AUTH_SALT . SECURE_AUTH_KEY . SECURE_AUTH_SALT);
		// 暗号化時点からkeyが変化した場合、正常に解読できずバイナリを返す場合があるので文字にエスケープする
		return rawurlencode( Walti_Util::decrypt( $encrypted_text, $key ) );
	}

	/**
	 * nonce名を取得する
	 *
	 * @param string $action アクション
	 * @param array $args nonce名に含める拡張情報
	 */
	private static function get_nonce_name( $action, $args = array() )
	{
		$name = 'walti_' . $action;
		if ( ! is_array( $args ) ) {
			$args = array( $args );
		}
		foreach ($args as $arg) {
			$name .= '_' . $arg;
		}
		return $name;
	}

	/**
	 * スキャンページ用のJSロード
	 *
	 */
	public static function enqueue_scan_scripts()
	{
		wp_enqueue_script( 'jquery' );
		// JSから参照するパラメータ
		wp_localize_script( 'jquery', 'WP', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'resultsAction' => 'walti_get_results',
			'scanAction' => 'walti_register_scan',
			'nonce' => wp_create_nonce( self::get_nonce_name( 'scan' ) ),
		) );
	}

	/**
	 * スキャンを登録する
	 *
	 * ajaxで呼び出される
	 */
	public static function ajax_register_scan()
	{
		$plugin_name = $_POST['plugin'];
		check_admin_referer( self::get_nonce_name( 'scan' ), 'nonce' );

		$api = new Walti_Api( Walti_Credentials::loadFromOptions() );
		$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
		$api_result = $target->queueScan( $plugin_name );

		$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
		$plugins = $target->getPlugins();
		if ( '402' == $api_result->getCode() ) {
			$json_array = array(
				'plugin' => $plugin_name,
				'status' => '要カード登録',
				'status_color' => 'orange',
				'createdAt' => '-',
				'message' => '-',
			);
		} elseif ( $plugins[$plugin_name]->isQueued() ) {
			$json_array = array(
				'plugin' => $plugin_name,
				'status' => 'queued',
				'createdAt' => get_date_from_gmt( date('Y-m-d H:i:s', strtotime( $plugins[$plugin_name]->getQueuedAt() ) ) ),
				'message' => '-',
			);
		} else {
			$scan = $target->fetchScan( $plugin_name )->getLatestScan( $plugin_name );
			$json_array = $scan->toArray();
		}
		header( 'Content-Type: application/json; charset=UTF-8');
		echo json_encode($json_array);
		// admin_ajax.phpが"0"を出力してしまうのでここでdieする
		die();
	}

	/**
	 * スキャン結果を取得する
	 *
	 * ajaxで読み込まれる
	 */
	static public function ajax_get_results()
	{
		$api = new Walti_Api( Walti_Credentials::loadFromOptions() );
		$target = Walti_Target::fetch( $api, Walti_Util::getHostName() );
		$plugins = $target->getPlugins();
		$results = array();
		foreach ( $plugins as $plugin ) {
			if ( $plugin->isQueued() ) {
				$results[] = array(
					'plugin' => $plugin->getName(),
					'status' => 'queued',
					'status_color' => 'grey',
					'createdAt' => get_date_from_gmt( date('Y-m-d H:i:s', strtotime( $plugin->getQueuedAt() ) ) ),
					'message' => '-',
				);
			} else {
				$scan = $target->fetchScans()->getLatestScan( $plugin->getName() );
				$results[] = $scan->toArray();
			}
		}

		header( 'Content-Type: application/json; charset=UTF-8');
		echo json_encode($results);
		// admin_ajax.phpが"0"を出力してしまうのでここでdieする
		die();
	}
}