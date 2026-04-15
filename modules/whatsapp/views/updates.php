<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
  .version-list {
    max-height: 350px;
    overflow-y: auto;
    padding-right: 10px;
  }
  .version-list .list-group-item {
    border: none;
    border-bottom: 1px solid #eee;
  }
</style>
<div id="wrapper">
  <div class="content">
    <div class="panel_s">
      <div class="panel-body">

        <h4 class="bold mb-3">
          <i class="fa fa-refresh text-info"></i> <?php echo _l('module_updates'); ?>
        </h4>

        <!-- Update status area with animation -->
        <div id="update-status" class="mb-3"></div>

        <!-- Display error if any -->
        <?php if (!empty($error)) : ?>
          <div class="alert alert-warning fadeIn">
            <i class="fa fa-exclamation-triangle"></i> <?php echo $error; ?>
          </div>
        <?php elseif (!empty($module_info)) : ?>
          <?php
            $mod = $module_info['module'];
            $current_version = $current_version ?? ($mod['current_version'] ?? '0.0.0');
          ?>

          <!-- Module info header -->
          <div class="mb-4">
            <h5 class="bold"><?php echo $mod['name']; ?></h5>
            <p><strong><?php echo _l('current_version'); ?>:</strong>
              <span class="label label-default"><?php echo htmlspecialchars($current_version); ?></span>
            </p>
          </div>

          <!-- Display if no updates available -->
          <?php if (empty($updates)): ?>
            <div class="alert alert-success fadeIn">
              <i class="fa fa-check-circle"></i>
              <?php echo _l('you_are_using_latest_version') ?: 'You are already using the latest version.'; ?>
            </div>
          <?php endif; ?>

          <!-- License Details (Always Displayed) -->
          <div class="card mb-4">
            <div class="card-header">
              <h5>License Details:</h5>
            </div>
            <div class="card-body">
              <?php if (!empty($license_data)) : ?>
                <ul>
                  <li><strong>License Type:</strong> <?php echo $license_data['license_type']; ?></li>
                  <li><strong>Buyer Name:</strong> <?php echo $license_data['buyer_name']; ?></li>
                  <li><strong>Purchase Date:</strong> <?php echo _dt($license_data['purchase_date']); ?></li>
                  <li><strong>Support Until:</strong> <?php echo _dt($license_data['support_until']); ?></li>
                </ul>
              <?php else: ?>
                <p class="text-muted"><?php echo _l('no_license_data'); ?></p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Update Preview Section -->
          <?php if (!empty($preview_version)) : ?>
            <div class="card mb-4">
              <div class="card-header">
                <h5>Preview Update: <?php echo htmlspecialchars($preview_version); ?></h5>
              </div>
              <div class="card-body">
                <p><strong>Changelog:</strong> <?php echo htmlspecialchars($preview_changelog); ?></p>
                <p><strong>Release Date:</strong> <?php echo _dt($preview_release_date); ?></p>
                <p><strong>Download Link:</strong> <a href="<?php echo $preview_download_link; ?>" target="_blank">Download</a></p>
              </div>
            </div>
          <?php endif; ?>



<!-- Versions List -->
<div class="row">
  <?php foreach (['stable' => 'success', 'beta' => 'warning'] as $type => $color): ?>
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="text-<?php echo $color; ?>"><?php echo _l($type . '_versions'); ?></h5>
        </div>
        <div class="card-body p-0">
          <?php 
            $versions = $module_info['versions'][$type] ?? [];
            if (count($versions)): 
          ?>
            <div class="version-list">
              <ul class="list-group m-0">
                <?php foreach ($versions as $index => $v): 
                  $is_update = version_compare($v['version'], $current_version, '>');
                ?>
                  <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start flex-column flex-md-row">
                      <div>
                        <strong><?php echo $v['version']; ?></strong>
                        <?php if (!empty($v['is_latest'])): ?>
                          <span class="badge badge-primary ml-1"><?php echo _l('latest'); ?></span>
                        <?php endif; ?>
                        <?php if ($is_update): ?>
                          <span class="badge badge-success ml-1"><?php echo _l('update_available'); ?></span>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                          <?php echo _l('released_on') . ': ' . _dt($v['release_at']); ?>
                        </div>
                      </div>

                      <!-- Only on the first version item show the button -->
                      <?php if ($index === 0 && !empty($v['download']) && $is_update): ?>
                        <div class="mt-2 mt-md-0">
                          <a href="<?php echo admin_url('whatsapp/updates?apply=' . $v['version']); ?>"
                             class="btn btn-xs btn-<?php echo $color; ?>">
                            <i class="fa fa-cloud-download"></i> <?php echo _l('update_now'); ?>
                          </a>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Changelog Details -->
                    <?php if (!empty($v['changelog'])): ?>
                      <div class="mt-2 text-muted" style="white-space: pre-wrap;">
                        <?php echo nl2br(htmlspecialchars($v['changelog'])); ?>
                      </div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php else: ?>
            <p class="text-muted m-3"><?php echo _l('no_' . $type . '_versions_available'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

        <?php else : ?>
          <p class="text-muted"><?php echo _l('no_update_data_loaded'); ?></p>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
