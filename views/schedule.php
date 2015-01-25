<div class="wrap">
  <h2><a href="<?php echo esc_url( WALTI_URL ); ?>"><img src="<?php echo esc_url( plugins_url( 'images/walti_logo.png', dirname( __FILE__ ) ) ); ?>" alt="Waltiスケジュール登録"></a></h2>
  <div>
    <a href="<?php echo admin_url( 'admin.php?page=walti_scan' ); ?>" class="button">戻る</a>
  </div>

  <dl>
    <dt class="walti-scan-target-info">組織</dt><dd><?php echo $organization->getName() ?></dd>
    <dt class="walti-scan-target-info">ターゲット</dt><dd><?php echo $target->getName() ?></dd>
  </dl>
  <p>定期的に実行したいスキャンのスケジュールを登録してください。</p>
  <form method="POST" action="<?php echo admin_url( 'admin.php?page=walti_schedule' ); ?>">
    <?php wp_nonce_field( $nonce_name ); ?>
    <dl class="walti-schedule-list">
      <?php foreach ( $target->getPlugins() as $plugin ) : ?>
      <dt><?php echo $plugin->getName(); ?></dt>
      <dd>
        <select id="<?php echo $plugin->getName(); ?>Schedule" name="schedules[<?php echo $plugin->getName(); ?>]">
          <option value="day" <?php if ( $plugin->getSchedule() == 'day' ) : ?>selected<?php endif; ?>>毎日</option>
          <option value="week" <?php if ( $plugin->getSchedule() == 'week' ) : ?>selected<?php endif; ?>>毎週</option>
          <option value="month" <?php if ( $plugin->getSchedule() == 'month' ) : ?>selected<?php endif; ?>>毎月</option>
          <option value="off" <?php if ( $plugin->getSchedule() == 'off' ) : ?>selected<?php endif; ?>>オフ</option>
        </select>
      </dd>
      <?php endforeach; ?>
    </dl>
    <div id="skipfishConfirmBox" style="display:none;" class="walti-skipfish-confirm">
      <input type="checkbox" id="skipfishConfirmCheck">
      <span><label for="skipfishConfirmCheck">
        skipfishコマンドはデータのPostを行います。<br>
        スキャン対象となるWebアプリケーションにスキャン用のデータが登録・更新される場合がありますが、それでも実行しますか？
      </label></span>
    </div>
    <button id="submitButton" type="submit" class="button button-primary">登録</button>
  </form>
</div>
<script>
jQuery(function($) {
  $('#skipfishSchedule').on('change', function(e) {
    if ($(this).val() != 'off') {
      $('#skipfishConfirmBox').show();
      var confirmed = $('#skipfishConfirmCheck').prop('checked');
      $('#submitButton').prop('disabled', !confirmed);
    } else {
      $('#skipfishConfirmBox').hide();
      $('#skipfishConfirmCheck').prop('checked', false);
      $('#submitButton').prop('disabled', false);
    }
  });
  $('#skipfishConfirmCheck').on('change', function(e) {
    var confirmed = $('#skipfishConfirmCheck').prop('checked');
    $('#submitButton').prop('disabled', !confirmed);
  });
});
</script>
