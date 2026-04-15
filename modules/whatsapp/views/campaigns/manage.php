<?php defined('BASEPATH') || exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-justify-between tw-items-center">
                            <h4 class="tw-my-0 tw-font-semibold">
                                <i class="fa-brands fa-whatsapp tw-text-green-500"></i>
                                <?php echo _l('campaigns'); ?>
                            </h4>
                            <?php if (staff_can('create', 'whatsapp_campaign')) { ?>
                                <a href="<?php echo admin_url('whatsapp/campaigns/campaign'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> <?php echo _l('send_new_campaign'); ?>
                                </a>
                            <?php } ?>
                        </div>

                        <hr class="hr-panel-separator">

                        <div class="panel-table-full tw-mt-4">
                            <?php echo render_datatable([
                                _l('the_number_sign'),              // Campaign ID
                                _l('campaign_name'),               // Campaign Name
                                _l('template'),                    // Template Name
                                _l('relation_type'),               // Leads/Contacts
                                _l('whatsapp_number'),             // ✅ Added Number Column
                                _l('total'),                       // Total Recipients
                                _l('delivered_to'),                // Delivered
                                _l('read_by'),                     // Read
                                _l('actions'),                     // Edit/Delete
                            ], 'campaigns_table'); ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<script>
    "use strict";
    // Initialize campaigns datatable
    initDataTable('.table-campaigns_table', admin_url + 'whatsapp/campaigns/get_table_data/campaigns', [], [], 'undefined', [0, 'desc']);
</script>
