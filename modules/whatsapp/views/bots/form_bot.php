<?php defined('BASEPATH') || exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php echo form_open_multipart(admin_url('whatsapp/bots/saveBot'), ['id' => 'whatsapp_bot_form'], ['id' => $bot['id'] ?? '']); ?>
        <!-- General Bot Settings Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo isset($bot) ? _l('edit_bot') . ' #' . $bot['name'] : _l('create_bot'); ?>
                </h4>
            </div>
        </div>

        <div class="row">
            <!-- General Bot Settings -->
            <div class="col-md-3">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo _l('general_settings'); ?></h4>
                        <?php
                            echo render_input('name', 'bot_name', $bot['name'] ?? '', '', ['placeholder' => _l('enter_name'), 'autocomplete' => 'off'], [], 'tw-w-full tw-mb-4');
                            echo render_select('bot_type', get_whatsapp_bot_types(), ['id', 'label', 'description'], 'bot_type', $bot['bot_type'] ?? '', [], [], 'tw-w-full tw-mb-4');
                            echo render_select('template_id', $templates, ['id', 'template_name', 'language'], 'template', $bot['template_id'] ?? '', [], [], 'template-bot-section hide');
                            echo render_select('rel_type', whatsapp_get_rel_type(), ['key', 'name'], 'relation_type', $bot['rel_type'] ?? '', [], [], 'tw-w-full tw-mb-4');
                            echo render_select('reply_type', get_whatsapp_reply_triggers(), ['id', 'label', 'example'], 'reply_type', $bot['reply_type'] ?? '', [], [], 'tw-w-full tw-mb-4');
                            echo render_input('trigger', 'trigger', $bot['trigger'] ?? '', '', ['placeholder' => _l('enter_bot_reply_trigger')], [], 'tw-w-full tw-mb-4');
                        ?>
                    </div>
                </div>
            </div>

            <!-- Message Bot Settings -->
            <div class="col-md-3 message-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('message_bot'); ?>
                        </h4>
                        <div class="tw-mb-4 hdft hide">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('header'); ?></label>
                            <?php echo render_input('bot_header', 'header', $bot['bot_header'] ?? '', '', ['placeholder' => _l('enter_header')], [], 'tw-w-full'); ?>
                        </div>
                        <div class="tw-mb-4">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('body'); ?></label>
                            <?php echo render_textarea('reply_text', 'body', $bot['reply_text'] ?? '', ['rows' => '10'], [], 'tw-w-full'); ?>
                        </div>
                        <div class="tw-mb-4 hdft hide">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('footer'); ?></label>
                            <?php echo render_input('bot_footer', 'footer_bot', $bot['bot_footer'] ?? '', '', ['placeholder' => _l('enter_footer'), 'maxlength' => '60'], [], 'tw-w-full'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Bot Settings -->
            <div class="col-md-4 template-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('variables'); ?>
                        </h4>
                        <div class="variableDetails row">
                            <div class="tw-flex tw-justify-between tw-items-center">
                                <span class="text-muted"><?php echo _l('merge_field_note'); ?></span>
                            </div>
                            <div class="clearfix"></div>
                            <hr class="hr-panel-separator">
                            <div class="variables"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 template-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <div class="row">
                            <div class="row" id="preview_message">
                                <div class="col-md-12">
                                  <h4 class="tw-mt-0 tw-font-semibold tw-text-neutral-700 no-margin"><?php echo _l('preview'); ?></h4>
                                            <div class="clearfix"></div>
                                            <hr class="hr-panel-separator">
                                            <div class="padding"
                                                style='background: url(" <?php echo module_dir_url(WHATSAPP_MODULE, 'assets/images/bg.jpg'); ?>");'>
                                                <div class="wtc_panel previewImage">
                                                </div>
                                                <div class="panel_s no-margin">
                                                    <div class="panel-body previewmsg"></div>
                                                </div>
                                                <div class="previewBtn">
                                                </div>
                                            </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media Bot Settings -->
            <div class="col-md-4 media-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('media_bot'); ?></h4>
                        <div class="tw-mb-4">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('media_type'); ?></label>
                            <?php
                            echo render_select('media_type', whatsapp_get_media_types(), ['id', 'label'], 'media_type', $bot['media_type'] ?? '', [], [], 'tw-w-full tw-mb-4');
                            ?>
                        </div>
                        <div class="tw-mb-4">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('media_file'); ?></label>
                            <input type="file" name="bot_file" id="filename"
                                class="tw-w-full tw-bg-gray-50 tw-border tw-rounded-md tw-p-2"
                                onchange="previewMedia()">
                        </div>
                        <!-- Media Preview -->
                        <div id="media-preview" class="tw-mt-4"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 interactive-buttons-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo _l('interactive_buttons_bot'); ?>
                        </h4>
            
                        <!-- ========== BUTTON #1 ========== -->
                        <h5 class="tw-font-semibold tw-mt-3">
                            <?php echo _l('button'); ?> #1
                        </h5>
                        <div class="tw-mb-4">
                            <?php
                            // 1) Visible text for Button #1
                            echo render_input(
                                'btn1_name',             // name attribute
                                'btn1_name',             // label (lang key) or use custom text
                                $bot['btn1_name'] ?? '', // existing value
                                '',
                                ['placeholder' => _l('enter_button_text')],
                                [],
                                'tw-w-full'
                            );
            
                            // 2) Type (reply, url, phone)
                            ?>
                            <label class="control-label" for="btn1_type">
                                <?php echo _l('button_type'); ?>
                            </label>
                            <select name="btn1_type" id="btn1_type" class="form-control tw-mb-2">
                                <option value="reply" <?php echo ($bot['btn1_type'] ?? '') === 'reply' ? 'selected' : ''; ?>>
                                    <?php echo _l('reply'); ?>
                                </option>
                                <option value="cta_url"   <?php echo ($bot['btn1_type'] ?? '') === 'cta_url'   ? 'selected' : ''; ?>>
                                    <?php echo _l('url'); ?>
                                </option>
                            </select>
            
                            <!-- If type=URL => show link field -->
                            <div class="tw-mb-2" id="btn1_link_group">
                                <?php
                                echo render_input(
                                    'btn1_link',
                                    'button_link',
                                    $bot['btn1_link'] ?? '',
                                    '',
                                    ['placeholder' => _l('enter_button_url')],
                                    [],
                                    'tw-w-full'
                                );
                                ?>
                            </div>
            
                            <!-- If type=phone => show phone field -->
                            <div class="tw-mb-2" id="btn1_number_group">
                                <?php
                                echo render_input(
                                    'btn1_number',
                                    'phone_number',
                                    $bot['btn1_number'] ?? '',
                                    '',
                                    ['placeholder' => _l('enter_button_phone')],
                                    [],
                                    'tw-w-full'
                                );
                                ?>
                            </div>
                        </div>
                        <hr/>
            
                        <!-- ========== BUTTON #2 ========== -->
                        <h5 class="tw-font-semibold tw-mt-3">
                            <?php echo _l('button'); ?> #2
                        </h5>
                        <div class="tw-mb-4">
                            <?php
                            // Name
                            echo render_input(
                                'btn2_name',
                                'btn2_name',
                                $bot['btn2_name'] ?? '',
                                '',
                                ['placeholder' => _l('enter_button_text')],
                                [],
                                'tw-w-full'
                            );
                            ?>
                            <label class="control-label" for="btn2_type">
                                <?php echo _l('button_type'); ?>
                            </label>
                            <select name="btn2_type" id="btn2_type" class="form-control tw-mb-2">
                                <option value="reply" <?php echo ($bot['btn2_type'] ?? '') === 'reply' ? 'selected' : ''; ?>>
                                    <?php echo _l('reply'); ?>
                                </option>
                                <option value="cta_url"   <?php echo ($bot['btn2_type'] ?? '') === 'cta_url'   ? 'selected' : ''; ?>>
                                    <?php echo _l('url'); ?>
                                </option>
                            </select>
            
                            <!-- If type=URL => show link field -->
                            <div class="tw-mb-2" id="btn2_link_group">
                                <?php
                                echo render_input(
                                    'btn2_link',
                                    'button_link',
                                    $bot['btn2_link'] ?? '',
                                    '',
                                    ['placeholder' => _l('enter_button_url')],
                                    [],
                                    'tw-w-full'
                                );
                                ?>
                            </div>
            
                            <!-- If type=phone => show phone field -->
                            <div class="tw-mb-2" id="btn2_number_group">
                                <?php
                                echo render_input(
                                    'btn2_number',
                                    'phone_number',
                                    $bot['btn2_number'] ?? '',
                                    '',
                                    ['placeholder' => _l('enter_button_phone')],
                                    [],
                                    'tw-w-full'
                                );
                                ?>
                            </div>
                        </div>
                        <hr/>
            
                        <!-- ========== BUTTON #3 ========== -->
                        <h5 class="tw-font-semibold tw-mt-3">
                            <?php echo _l('button'); ?> #3
                        </h5>
                        <div class="tw-mb-4">
                            <?php
                            // Name
                            echo render_input(
                                'btn3_name',
                                'btn3_name',
                                $bot['btn3_name'] ?? '',
                                '',
                                ['placeholder' => _l('enter_button_text')],
                                [],
                                'tw-w-full'
                            );
                            ?>
                            <label class="control-label" for="btn3_type">
                                <?php echo _l('button_type'); ?>
                            </label>
                            <select name="btn3_type" id="btn3_type" class="form-control tw-mb-2">
                                <option value="reply" <?php echo ($bot['btn3_type'] ?? '') === 'reply' ? 'selected' : ''; ?>>
                                    <?php echo _l('reply'); ?>
                                </option>
                                <option value="cta_url"   <?php echo ($bot['btn3_type'] ?? '') === 'cta_url'   ? 'selected' : ''; ?>>
                                    <?php echo _l('url'); ?>
                                </option>
                            </select>
            
                            <!-- If type=URL => show link field -->
                            <div class="tw-mb-2" id="btn3_link_group">
                                <?php
                                echo render_input(
                                    'btn3_link',
                                    'button_link',
                                    $bot['btn3_link'] ?? '',
                                    '',
                                    ['placeholder' => _l('enter_button_url')],
                                    [],
                                    'tw-w-full'
                                );
                                ?>
                            </div>
            
                            <!-- If type=phone => show phone field -->
                            <div class="tw-mb-2" id="btn3_number_group">
                                <?php
                                echo render_input(
                                    'btn3_number',
                                    'phone_number',
                                    $bot['btn3_number'] ?? '',
                                    '',
                                    ['placeholder' => _l('enter_button_phone')],
                                    [],
                                    'tw-w-full'
                                );
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Location Bot Settings -->
            <div class="col-md-4 location-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('location_bot'); ?></h4>

                        <?php
                        echo render_input(
                            'location_name',
                            'location_name',
                            $bot['location_name'] ?? '',
                            '',
                            ['placeholder' => _l('enter_location_name')],
                            [],
                            'tw-w-full tw-mb-4'
                        );
                        echo render_input(
                            'latitude',
                            'latitude',
                            $bot['latitude'] ?? '',
                            'number',
                            ['placeholder' => _l('enter_latitude'), 'step' => 'any'],
                            [],
                            'tw-w-full tw-mb-4'
                        );
                        echo render_input(
                            'longitude',
                            'longitude',
                            $bot['longitude'] ?? '',
                            'number',
                            ['placeholder' => _l('enter_longitude'), 'step' => 'any'],
                            [],
                            'tw-w-full tw-mb-4'
                        );
                        echo render_input(
                            'location_address',
                            'location_address',
                            $bot['location_address'] ?? '',
                            '',
                            ['placeholder' => _l('enter_location_address')],
                            [],
                            'tw-w-full tw-mb-4'
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4 location-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('location_bot'); ?></h4>
                        <!-- Map container -->
                        <div id="location-map" style="height: 300px; border: 1px solid #ccc; border-radius: 4px;"></div>
                    </div>
                </div>
            </div>

            <!-- List and Quick Reply Bots -->
            <div class="col-md-4 list-bot-section hide quick-reply-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo _l('list_or_quick_reply_bot'); ?></h4>
                        <div class="tw-mb-4">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('list_items'); ?></label>
                            <?php echo render_textarea('bot_list', 'list_items', $bot['bot_list'] ?? '', ['rows' => '5'], [], 'tw-w-full'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticker Bot Settings -->
            <div class="col-md-4 sticker-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('sticker_bot'); ?>
                        </h4>
                        <div class="tw-mb-4">
                            <label class="tw-block tw-font-semibold tw-mb-2"><?php echo _l('sticker_file'); ?></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Bot Settings -->
            <div class="col-md-4 contact-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('contact_bot'); ?>
                        </h4>
                        <?php
                        echo render_input('contact_name', 'contact_name', $bot['contact_name'] ?? '', '', ['placeholder' => _l('enter_contact_name')], [], 'tw-w-full tw-mb-4');
                        echo render_input('contact_first_name', 'contact_first_name', $bot['contact_first_name'] ?? '', '', ['placeholder' => _l('enter_contact_first_name')], [], 'tw-w-full tw-mb-4');
                        echo render_input('contact_last_name', 'contact_last_name', $bot['contact_last_name'] ?? '', '', ['placeholder' => _l('enter_contact_last_name')], [], 'tw-w-full tw-mb-4');
                        echo render_input('contact_number', 'contact_number', $bot['contact_number'] ?? '', '', ['placeholder' => _l('enter_contact_number')], [], 'tw-w-full tw-mb-4');
                        echo render_input('contact_email', 'contact_email', $bot['contact_email'] ?? '', '', ['placeholder' => _l('enter_contact_email')], [], 'tw-w-full tw-mb-4');
                        ?>
                    </div>
                </div>
            </div>
            <!-- Menu Bot Section with Collapsible Submenu -->
            <div class="col-md-6 menu-bot-section hide">
                <div class="panel_s tw-shadow-lg tw-rounded-md tw-bg-white tw-p-6">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('menu_bot'); ?></h4>

                        <!-- Main Menu Container (Collapsible Tree Structure) -->
                        <div id="menu-container" class="tw-p-6 tw-bg-gray-100 tw-rounded-lg tw-shadow-lg">
                            <ul class="menu-tree">
                                <?php if (!empty($bot['menu_items'])): ?>

                                <?php else: ?>
                                    <!-- When there are no menu items, the container should be empty but the Add Menu Item button should still work -->
                                    <li class="menu-item tw-mb-6">
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <!-- Button to add new menu item, should always be visible -->
                        <button type="button"
                            class="btn btn-primary tw-bg-blue-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 add-menu-item">Add
                            Menu Item</button>
                    </div>
                </div>
            </div>
        </div> <!-- Save Button -->
        <div class="row">
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary"><?php echo _l('save_bot'); ?></button>
            </div>
        </div>

    <?php echo form_close(); ?></div>
</div>
<?php init_tail(); ?>
<script src="<?= module_dir_url('whatsapp', 'assets/js/flowbot.js') ?>"></script>
<!-- 2) Load Google Maps JavaScript API once, pointing to our callback -->
<script
  src="https://maps.googleapis.com/maps/api/js?key=<?= get_option('google_api_key'); ?>&libraries=places&callback=initLocationMap"
  async
  defer
></script>
<!-- 1) Define initLocationMap exactly once, in the global scope -->
<!-- 3) All other inline scripts (buttons, menu tree, etc.) -->
<script>
  // Globals so both initLocationMap and jQuery can see them
  var map;
  var marker;
  var geocoder;
  var autocomplete;

  function initLocationMap() {
    // 1) Pull any saved coordinates (or "" if none)
    var savedLat = "<?= isset($bot['latitude']) ? $bot['latitude'] : ''; ?>";
    var savedLng = "<?= isset($bot['longitude']) ? $bot['longitude'] : ''; ?>";
    var hasSaved = savedLat !== "" && savedLng !== "";

    // 2) Create a Geocoder for reverse geocoding
    geocoder = new google.maps.Geocoder();

    // 3) Instantiate the map, either centered on saved or world view
    if (hasSaved) {
      var latFloat = parseFloat(savedLat);
      var lngFloat = parseFloat(savedLng);
      var initialCenter = { lat: latFloat, lng: lngFloat };

      map = new google.maps.Map(document.getElementById("location-map"), {
        center: initialCenter,
        zoom: 13
      });

      // Drop a draggable marker at the saved location
      marker = new google.maps.Marker({
        position: initialCenter,
        map: map,
        draggable: true
      });

      // Pre-fill form fields
      document.getElementById("latitude").value = latFloat;
      document.getElementById("longitude").value = lngFloat;
      document.getElementById("location_address").value = "<?= addslashes($bot['location_address'] ?? ''); ?>";

      // If user drags the marker, update lat/lng/address
      marker.addListener("dragend", function(e) {
        var newPos = { lat: e.latLng.lat(), lng: e.latLng.lng() };
        updateLatLngFields(newPos);
        reverseGeocodeAndFill(newPos);
      });

    } else {
      // No saved coords → start in world view
      map = new google.maps.Map(document.getElementById("location-map"), {
        center: { lat: 0, lng: 0 },
        zoom: 2
      });

      // Try browser geolocation
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function(position) {
            var userPos = {
              lat: position.coords.latitude,
              lng: position.coords.longitude
            };
            map.setCenter(userPos);
            map.setZoom(13);

            // Place a draggable marker at user’s location
            marker = new google.maps.Marker({
              position: userPos,
              map: map,
              draggable: true
            });

            updateLatLngFields(userPos);
            reverseGeocodeAndFill(userPos);

            marker.addListener("dragend", function(e) {
              var newPos = { lat: e.latLng.lat(), lng: e.latLng.lng() };
              updateLatLngFields(newPos);
              reverseGeocodeAndFill(newPos);
            });
          },
          function() {
            // Geolocation failed/denied → remain in world view
          }
        );
      }
    }

    // 4) When you click on the map, move or create the marker
    map.addListener("click", function(event) {
      var clickedPos = { lat: event.latLng.lat(), lng: event.latLng.lng() };
      if (!marker) {
        marker = new google.maps.Marker({
          position: clickedPos,
          map: map,
          draggable: true
        });
        marker.addListener("dragend", function(e) {
          var newPos = { lat: e.latLng.lat(), lng: e.latLng.lng() };
          updateLatLngFields(newPos);
          reverseGeocodeAndFill(newPos);
        });
      } else {
        marker.setPosition(clickedPos);
      }
      updateLatLngFields(clickedPos);
      reverseGeocodeAndFill(clickedPos);
      map.setCenter(clickedPos);
      map.setZoom(13);
    });

    // 5) Wire up autocomplete on the “location_address” input
    var input = document.getElementById("location_address");
    autocomplete = new google.maps.places.Autocomplete(input, {
      fields: ["geometry", "formatted_address"]
    });
    autocomplete.bindTo("bounds", map);
    autocomplete.addListener("place_changed", function() {
      var place = autocomplete.getPlace();
      if (!place.geometry || !place.geometry.location) {
        return;
      }
      var loc = {
        lat: place.geometry.location.lat(),
        lng: place.geometry.location.lng()
      };
      map.setCenter(loc);
      map.setZoom(13);

      if (!marker) {
        marker = new google.maps.Marker({
          position: loc,
          map: map,
          draggable: true
        });
        marker.addListener("dragend", function(e) {
          var newPos = { lat: e.latLng.lat(), lng: e.latLng.lng() };
          updateLatLngFields(newPos);
          reverseGeocodeAndFill(newPos);
        });
      } else {
        marker.setPosition(loc);
      }

      updateLatLngFields(loc);
      // The input already has the formatted_address from the autocomplete
      // But just in case, rewrite it:
      input.value = place.formatted_address || input.value;
    });
  }

  // Helper: update #latitude and #longitude fields
  function updateLatLngFields(latlng) {
    document.getElementById("latitude").value = latlng.lat;
    document.getElementById("longitude").value = latlng.lng;
  }

  // Helper: reverse geocode latlng → formatted address → #location_address
  function reverseGeocodeAndFill(latlng) {
    geocoder.geocode({ location: latlng }, function(results, status) {
      if (status === "OK" && results[0]) {
        document.getElementById("location_address").value = results[0].formatted_address;
      }
    });
  }
</script>



<script>
  (function($){
    "use strict";

    $(document).ready(function() {
      // ======================
      // Button show/hide logic
      // ======================
      function toggleButtonFields(buttonIndex) {
        var typeSelect  = document.getElementById('btn' + buttonIndex + '_type');
        var linkGroup   = document.getElementById('btn' + buttonIndex + '_link_group');
        var numberGroup = document.getElementById('btn' + buttonIndex + '_number_group');
        if (!typeSelect || !linkGroup || !numberGroup) return;

        var chosenType = typeSelect.value;
        if (chosenType === 'cta_url') {
          linkGroup.style.display   = 'block';
          numberGroup.style.display = 'none';
        } else if (chosenType === 'phone') {
          linkGroup.style.display   = 'none';
          numberGroup.style.display = 'block';
        } else {
          linkGroup.style.display   = 'none';
          numberGroup.style.display = 'none';
        }
      }

      [1,2,3].forEach(function(btnIdx) {
        var typeSelect = document.getElementById('btn' + btnIdx + '_type');
        if (typeSelect) {
          typeSelect.addEventListener('change', function() {
            toggleButtonFields(btnIdx);
          });
          toggleButtonFields(btnIdx);
        }
      });

      // Prevent default form submit if needed
      $('#workflow-form').on('submit', function(e) {
        e.preventDefault();
      });
      $('#save_btn').on('click', function(e) {
        e.preventDefault();
        if ($('input[name="is_validate"]').val() === '1') {
          $.post(admin_url + 'whatsbot/bot_flow/save', {
            id: $('input[name="id"]').val(),
            flow_data: $('input[name="flow_data"]').val()
          }, 'json').done(function(res) {
            alert_float(res.type, res.message);
            setTimeout(function() {
              location.href = admin_url + 'whatsbot/bot_flow';
            }, 1000);
          });
        }
      });

      // =====================
      // Menu‐tree logic below
      // =====================
      var itemIdCounter = 1;
      function generateMenuHTML(menuItems, parentId) {
        parentId = parentId || 0;
        var html = '<ul class="menu-tree tw-ml-4 tw-border-l tw-border-gray-300">';
        if (menuItems && Object.keys(menuItems).length) {
          Object.keys(menuItems).forEach(function(key) {
            var menuItem = menuItems[key];
            if (menuItem.menu_item_parent_id == parentId) {
              itemIdCounter = Math.max(itemIdCounter, parseInt(menuItem.menu_item_id));
              html += ''
                + '<li class="menu-item tw-relative tw-mb-6 tw-pl-6 '
                +   'before:tw-absolute before:tw-top-1/2 before:tw-left-0 '
                +   'before:tw-w-4 before:tw-h-0.5 before:tw-bg-gray-300">'
                +   '<label class="tw-block tw-font-semibold tw-mb-2">Menu Item</label>'
                +   '<input type="text" '
                +     'name="menu_items[' + menuItem.menu_item_id + '][menu_item]" '
                +     'class="form-control tw-w-full tw-mb-2 tw-rounded-md tw-border-gray-300" '
                +     'value="' + menuItem.menu_item + '" '
                +     'placeholder="Enter Menu Item"'
                +   '>'
                +   '<label class="tw-block tw-font-semibold tw-mb-2">Message</label>'
                +   '<input type="text" '
                +     'name="menu_items[' + menuItem.menu_item_id + '][message]" '
                +     'class="form-control tw-w-full tw-rounded-md tw-border-gray-300" '
                +     'placeholder="Enter Menu Message" '
                +     'maxlength="70" '
                +     'value="' + menuItem.message + '"'
                +   '>'
                +   '<input type="hidden" '
                +     'name="menu_items[' + menuItem.menu_item_id + '][menu_item_id]" '
                +     'value="' + menuItem.menu_item_id + '" readonly>'
                +   '<input type="hidden" '
                +     'name="menu_items[' + menuItem.menu_item_id + '][menu_item_parent_id]" '
                +     'value="' + menuItem.menu_item_parent_id + '" readonly>'
                +   '<button type="button" '
                +     'class="btn btn-primary tw-bg-blue-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 add-submenu" '
                +     'data-item-id="' + menuItem.menu_item_id + '">Add Submenu</button>'
                +   '<button type="button" '
                +     'class="btn btn-danger tw-bg-red-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 delete-menu-item" '
                +     'data-item-id="' + menuItem.menu_item_id + '">Delete</button>'
                +   '<ul class="submenu-container tw-ml-6 tw-border-l tw-border-gray-300">'
                +     generateMenuHTML(menuItems, menuItem.menu_item_id)
                +   '</ul>'
                + '</li>';
            }
          });
        }
        html += '</ul>';
        return html;
      }

      // Render any existing menu items from PHP
      var menuItems = <?= json_encode(json_decode($bot['menu_items'] ?? '[]', true)); ?>;
      $('#menu-container').html(generateMenuHTML(menuItems));

      // Add a new top‐level menu item
      $('.add-menu-item').on('click', function() {
        var newId = ++itemIdCounter;
        var newMenuItem = ''
          + '<li class="menu-item tw-relative tw-mb-6 tw-pl-6 '
          +   'before:tw-absolute before:tw-top-1/2 before:tw-left-0 '
          +   'before:tw-w-4 before:tw-h-0.5 before:tw-bg-gray-300">'
          +   '<label class="tw-block tw-font-semibold tw-mb-2">Menu Item</label>'
          +   '<input type="text" '
          +     'name="menu_items[' + newId + '][menu_item]" '
          +     'class="tw-w-full tw-mb-2 tw-rounded-md tw-border-gray-300" '
          +     'placeholder="Enter Menu Item">'
          +   '<label class="tw-block tw-font-semibold tw-mb-2">Message</label>'
          +   '<textarea name="menu_items[' + newId + '][message]" '
          +     'class="tw-w-full tw-mb-2 tw-rounded-md tw-border-gray-300" '
          +     'placeholder="Enter Menu Message"></textarea>'
          +   '<input type="hidden" '
          +     'name="menu_items[' + newId + '][menu_item_id]" '
          +     'value="' + newId + '" readonly>'
          +   '<input type="hidden" '
          +     'name="menu_items[' + newId + '][menu_item_parent_id]" '
          +     'value="0" readonly>'
          +   '<button type="button" '
          +     'class="btn btn-primary tw-bg-blue-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 add-submenu" '
          +     'data-item-id="' + newId + '">Add Submenu</button>'
          +   '<button type="button" '
          +     'class="btn btn-danger tw-bg-red-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 delete-menu-item" '
          +     'data-item-id="' + newId + '">Delete</button>'
          +   '<ul class="submenu-container tw-ml-6 tw-border-l tw-border-gray-300"></ul>'
          + '</li>';
        $('.menu-tree').append(newMenuItem);
      });

      // Add a submenu under any existing menu item
      $(document).on('click', '.add-submenu', function() {
        var parentId = $(this).data('item-id');
        var newId = ++itemIdCounter;
        var newSubmenuItem = ''
          + '<li class="submenu-item tw-relative tw-ml-6 tw-mb-4 tw-pl-6 '
          +   'before:tw-absolute before:tw-top-1/2 before:tw-left-0 '
          +   'before:tw-w-4 before:tw-h-0.5 before:tw-bg-gray-300">'
          +   '<label class="tw-block tw-font-semibold tw-mb-2">Submenu Item</label>'
          +   '<input type="text" '
          +     'name="menu_items[' + newId + '][menu_item]" '
          +     'class="tw-w-full tw-mb-2 tw-rounded-md tw-border-gray-300" '
          +     'placeholder="Enter Submenu Item">'
          +   '<label class="tw-block tw-font-semibold tw-mb-2">Message</label>'
          +   '<textarea name="menu_items[' + newId + '][message]" '
          +     'class="tw-w-full tw-mb-2 tw-rounded-md tw-border-gray-300" '
          +     'placeholder="Enter Submenu Message"></textarea>'
          +   '<input type="hidden" '
          +     'name="menu_items[' + newId + '][menu_item_id]" '
          +     'value="' + newId + '" readonly>'
          +   '<input type="hidden" '
          +     'name="menu_items[' + newId + '][menu_item_parent_id]" '
          +     'value="' + parentId + '" readonly>'
          +   '<button type="button" '
          +     'class="btn btn-primary tw-bg-blue-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 add-submenu" '
          +     'data-item-id="' + newId + '">Add Submenu</button>'
          +   '<button type="button" '
          +     'class="btn btn-danger tw-bg-red-500 tw-text-white tw-rounded-md tw-py-1 tw-px-4 delete-menu-item" '
          +     'data-item-id="' + newId + '">Delete</button>'
          +   '<ul class="submenu-container tw-ml-6 tw-border-l tw-border-gray-300"></ul>'
          + '</li>';
        $(this).siblings('.submenu-container').append(newSubmenuItem);
      });

      // Delete either a menu item or submenu item
      $(document).on('click', '.delete-menu-item', function() {
        $(this).closest('li.menu-item, li.submenu-item').remove();
            });
});



    function previewMedia() {
        const fileInput = document.getElementById('filename');
        const previewContainer = document.getElementById('media-preview');
        const file = fileInput.files[0];

        // Clear previous preview
        previewContainer.innerHTML = '';

        if (file) {
            const fileType = file.type;
            const reader = new FileReader();

            reader.onload = function(e) {
                let mediaElement;

                // Create appropriate media element based on file type
                if (fileType.startsWith('image/')) {
                    mediaElement = document.createElement('img');
                    mediaElement.src = e.target.result;
                    mediaElement.style.maxWidth = '100%';
                    mediaElement.style.borderRadius = '8px';
                } else if (fileType.startsWith('video/')) {
                    mediaElement = document.createElement('video');
                    mediaElement.src = e.target.result;
                    mediaElement.controls = true;
                    mediaElement.style.maxWidth = '100%';
                    mediaElement.style.borderRadius = '8px';
                } else if (fileType.startsWith('audio/')) {
                    mediaElement = document.createElement('audio');
                    mediaElement.src = e.target.result;
                    mediaElement.controls = true;
                    mediaElement.style.width = '100%';
                } else {
                    mediaElement = document.createElement('p');
                    mediaElement.textContent = 'File preview not available for this type.';
                }

                previewContainer.appendChild(mediaElement);
            };

            reader.readAsDataURL(file);
        }
    }

    // Function to toggle bot sections based on the selected bot type
    function handleBotTypeChange() {
        var botType = $('#bot_type').val();
        // Hide all sections
        $('.message-bot-section, .template-bot-section, .media-bot-section, .interactive-buttons-bot-section, .location-bot-section, .list-bot-section, .quick-reply-bot-section, .sticker-bot-section, .contact-bot-section, .poll-bot-section, .menu-bot-section, .flow-bot-section')
            .addClass('hide');

        switch (botType) {
            case '1':
                $('.message-bot-section').removeClass('hide');
                break;
            case '2':
                $('.template-bot-section').removeClass('hide');
                break;
            case '3':
                $('.menu-bot-section').removeClass('hide');
                $('.message-bot-section').removeClass('hide');
                $('.hdft').removeClass('hide');
                break;
            case '4':
                $('.flow-bot-section').removeClass('hide');
                break;
            case '5':
                $('.media-bot-section').removeClass('hide');
                break;
            case '6':
                $('.location-bot-section').removeClass('hide');
                break;
            case '7':
                $('.message-bot-section').removeClass('hide');
                $('.interactive-buttons-bot-section').removeClass('hide');
                $('.hdft').removeClass('hide');
                break;
            case '8':
                $('.list-bot-section').removeClass('hide');
                break;
            case '9':
                $('.quick-reply-bot-section').removeClass('hide');
                break;
            case '10':
                $('.sticker-bot-section').removeClass('hide');
                break;
            case '11':
                $('.contact-bot-section').removeClass('hide');
                break;
            case '12':
                $('.poll-bot-section').removeClass('hide');
                break;
            default:
                // fallback
                break;
        }

    }

    $(document).ready(function() {
        handleBotTypeChange();
        $('#template_id').trigger('change');

        // Toggle sections on bot type change
        $('#bot_type').change(function() {
            handleBotTypeChange();
        });

        // Example 1: Load templates dynamically based on the bot type

        // Example 2: Preview media file before upload for Media Bots
        $('#media_file').change(function() {
            var file = this.files[0];
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.previewImage').html('<img src="' + e.target.result +
                    '" class="tw-max-w-full tw-h-auto" />');
            };
            reader.readAsDataURL(file);
        });

        // Example 3: Limit the number of poll options for Poll Bots
        $('.poll-bot-section').on('input', '.poll_option', function() {
            var filledOptions = $('.poll_option').filter(function() {
                return this.value.trim() !== "";
            }).length;

            if (filledOptions >= 5) {
                $('.poll_option').not(':filled').attr('disabled', true);
            } else {
                $('.poll_option').removeAttr('disabled');
            }
        });

        // Example 5: Auto-generate button IDs for Interactive Buttons Bots
        $('.interactive-buttons-bot-section').on('input', '.button', function() {
            var buttonIndex = $(this).closest('.tw-w-1/3').index() + 1;
            var buttonText = $(this).val().toLowerCase().replace(/\s+/g, '_');
            $('#button' + buttonIndex + '_id').val('btn_' + buttonText);
        });

        // Validation rules
        appValidateForm($('#whatsapp_bot_form'), {
            name: "required",
            trigger: "required",
      });
    });
  })(jQuery);
</script>
