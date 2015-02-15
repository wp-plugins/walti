<div class="wrap">
  <h2><a href="<?php echo esc_url( WALTI_URL ); ?>"><img src="<?php echo esc_url( plugins_url( 'images/walti_logo.png', dirname( __FILE__ ) ) ); ?>" alt="Walti設定"></a></h2>

  <?php if ( ! $initialized ) : ?>
    <h3>ようこそ</h3>
    <p>APIキーを設定することで、このサイトへのスキャンの実行・結果の確認などがWordPressの管理画面から簡単に行えるようになります。</p>

    <h4>まだWaltiをご利用いただいていない方</h4>
    <p><a href="<?php echo WALTI_URL ?>" target="_blank">Walti</a>にサインアップして、「組織設定」＞「API」の画面に表示されるAPIキーとAPIシークレットを入力してください。</p>

    <h4>すでにWaltiをご利用いただいている方</h4>
    <p>Waltiの「組織設定」＞「API」の画面に表示されるAPIキーとAPIシークレットを入力してください。</p>

    <hr>
  <?php endif; ?>

  <form action="<?php echo $destination ?>" method="post">
    <?php settings_fields( 'walti_api_options' ); ?>
    <?php do_settings_sections( 'walti_config' ); ?>
    <?php submit_button(); ?>
  </form>

  <?php if ( $initialized && $is_authenticated ) : ?>

    <hr>

    <h3>ターゲット情報</h3>
    <table class="form-table">
      <tr>
        <th scope="row">ターゲット</th>
        <td><?php echo $hostname ?></td>
      </tr>
      <tr>
        <th scope="row">ステータス</th>
        <td><?php echo $status ?></td>
      </tr>
      <tr>
        <th scope="row">認証ファイル名</th>
        <td><?php echo $ownership_filename ?></td>
      </tr>
    </table>
    <form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>?page=walti_config" method="POST">
      <?php wp_nonce_field( $nonce_name ) ?>
      <?php if ( $target_registered ) : ?>
        <?php if ( $target_activated ) : ?>
          <?php if ( $exists_owner_file && $owner_file_writable ) : ?>
            <button name="operation" value="deleteOwnership" type="submit" class="button">認証ファイルを削除</button>
          <?php endif; ?>
        <?php else : ?>
          <?php if ( $is_activation_queued || $is_archived ) : ?>
            <!-- 何も表示しない -->
          <?php elseif ( $exists_owner_file || $owner_file_writable ) : ?>
            <button name="operation" value="activate" type="submit" class="button button-primary">所有確認を実行</button>
          <?php else : ?>
            <button class="button button-primary" disabled>所有確認を実行</button>
            <div style="color:red;">認証ファイルを作成する権限がありません。<a href="<?php echo $ownership_url ?>">ここ</a>からファイルをダウンロードして、ドキュメントルートに設置してください</div>
          <?php endif; ?>
        <?php endif; ?>
      <?php else : ?>
        <button name="operation" value="register" type="submit" class="button button-primary">このホストをターゲットとして登録</button>
        <?php if ( ! $owner_file_writable) : ?>
          <div>※ドキュメントルートに書込権限がありません。ターゲット登録後、手動で認証ファイルを設置する必要があります。</div>
        <?php endif; ?>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</div>
