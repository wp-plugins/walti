<div class="wrap">

  <h2><a href="<?php echo esc_url( WALTI_URL ); ?>"><img src="<?php echo esc_url( plugins_url( 'images/walti_logo.png', dirname( __FILE__ ) ) ); ?>" alt="Waltiスキャン"></a></h2>
  <div>
    <a href="<?php echo WALTI_URL . '/targets/' . Walti_Util::getHostName() ; ?>" class="button" target="_blank">Waltiで詳細を確認</a>
    <a href="<?php echo admin_url( 'admin.php?page=walti_schedule' ); ?>" class="button">スキャンスケジュール登録</a>
    <a href="<?php echo admin_url( 'options-general.php?page=walti_config' ); ?>" class="button">設定</a>
    <a href="https://walti.zendesk.com/hc/ja/articles/204288395-Walti-io%E3%81%AEWordPress%E3%83%97%E3%83%A9%E3%82%B0%E3%82%A4%E3%83%B3-Walti-%E3%81%A8%E3%81%AF-" class="button" target="_blank">ヘルプ</a>
  </div>

  <?php if ($scan_enabled) : ?>

    <dl>
      <dt class="walti-scan-target-info">組織</dt><dd><?php echo $organization_name ?></dd>
      <dt class="walti-scan-target-info">ターゲット</dt><dd><?php echo $target_name ?></dd>
    </dl>
    
  <table class="widefat walti-scan-list">
    <thead>
      <tr>
        <th>
          スキャンの種類
          <a href="https://walti.zendesk.com/hc/ja/sections/200177039-%E5%90%84%E3%82%B9%E3%82%AD%E3%83%A3%E3%83%B3%E6%A9%9F%E8%83%BD%E3%81%AB%E3%81%A4%E3%81%84%E3%81%A6" target="_blank">
            <?php if ( $dashicons_enabled ) : ?>
            <span class="dashicons dashicons-editor-help"></span>
            <?php else: ?>
            [?]
            <?php endif; ?>
          </a>
        </th>
        <th>実行</th>
        <th>ステータス</th>
        <th>操作日時</th>
        <th>スキャン概要</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $plugins as $plugin ) : ?>
        <tr id="<?php echo $plugin->getName() ?>">
          <td class="plugin"><?php echo $plugin->getName() ?></td>
          <?php if ( $plugin->isQueued() ) : ?>
          <td id="<?php echo $plugin->getName() ?>ColButton"><button id="<?php echo $plugin->getName() ?>Button" class="button buttonRegister" type="button" disabled>今すぐスキャン</button></td>
          <td id="<?php echo $plugin->getName() ?>ColStatus"><div class="walti-status walti-color-grey">スキャン中...</div></td>
          <td id="<?php echo $plugin->getName() ?>ColDate"><?php echo get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $plugin->getQueuedAt() ) ) ) ?></td>
          <td id="<?php echo $plugin->getName() ?>ColSummary">-</td>
          <?php else : ?>
          <td id="<?php echo $plugin->getName() ?>ColButton"><button id="<?php echo $plugin->getName() ?>Button" class="button buttonRegister" type="button">今すぐスキャン</button></td>
          <?php if ( isset( $scans[$plugin->getName()] ) ) : ?>
              <?php $scan = $scans[$plugin->getName()] ?>
              <td id="<?php echo $plugin->getName() ?>ColStatus">
                  <div class="walti-status walti-color-<?php echo $scan->getStatusColor() ?>"><?php echo strtoupper($scan->getStatus()) ?></div>
              </td>
              <td id="<?php echo $plugin->getName() ?>ColDate"><?php echo get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $scan->getCreatedAt() ) ) ); ?></td>
              <td id="<?php echo $plugin->getName() ?>ColSummary"><?php echo $scan->getMessage() ?></td>
          <?php else: ?>
              <td id="<?php echo $plugin->getName() ?>ColStatus">
                  <div class="walti-status walti-color-grey">-</div>
              </td>
              <td id="<?php echo $plugin->getName() ?>ColDate">-</td>
              <td id="<?php echo $plugin->getName() ?>ColSummary">-</td>
          <?php endif; ?>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<script>
  jQuery(function($){

      var waitForRegistration = function(plugin) {
      $('#' + plugin + 'Button').prop('disabled', true);
      $('#' + plugin + 'ColStatus').html('<div class="walti-status walti-color-grey">登録中...</div>');
      $('#' + plugin + 'ColDate').text('-');
      $('#' + plugin + 'ColSummary').text('-');
    };
    
    function ScanResult(json)
    {
      this.plugin = json.plugin;
      this.status = json.status;
      this.statusColor = json.status_color;
      this.createdAt = json.createdAt;
      this.message = json.message;
    }

    ScanResult.prototype.render = function() {
      $('#' + this.plugin + 'Button').prop('disabled', (this.status == 'queued'));
      $('#' + this.plugin + 'ColStatus').html(this.getStatusHtml());
      $('#' + this.plugin + 'ColDate').text(this.createdAt);
      $('#' + this.plugin + 'ColSummary').text(this.message);
    };

    ScanResult.prototype.getStatusHtml = function() {
      if ('queued' == this.status) {
        return '<div class="walti-status walti-color-grey">スキャン中...</div>';
      } else {
        return '<div class="walti-status walti-color-' + this.statusColor + '">' + this.status.toUpperCase() + '</div>'
      }
    };

    $('.buttonRegister').on('click', function(){
      var plugin = this.id.replace(/Button$/, '');

      if (plugin == 'skipfish') {
        if (!confirm("skipfishコマンドはデータのPostを行います。\nスキャン対象となるWebアプリケーションにスキャン用のデータが登録・更新される場合がありますが、それでも実行しますか？")) {
          return;
        }
      }
      waitForRegistration(plugin);
      clearTimeout(timeoutId);
      $.ajax({
        type: 'POST',
        url: WP.ajaxUrl,
        data: {
          action: WP.scanAction,
          nonce: WP.nonce,
          plugin: plugin
        }
      }).done(function(response) {
        scan = new ScanResult(response);
        scan.render();
        setRefreshTimer();
      }).fail(function() {
        alert('スキャンの登録に失敗しました。');
        setRefreshTimer();
      })
    });

    var timeout = 30000;
    var timeoutId;
    var setRefreshTimer = function() {
      timeoutId = setTimeout(function() {
        $.ajax({
          type: 'GET',
          url: WP.ajaxUrl,
          data: {
            action: WP.resultsAction
          }
        }).done(function(response) {
          response.forEach(function(val) {
            (new ScanResult(val)).render();
          });
        });
        timeoutId = setTimeout(arguments.callee, timeout);
      }, timeout);
    };
    setRefreshTimer();
  });
</script>
