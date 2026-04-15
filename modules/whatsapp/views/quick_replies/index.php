<?php init_head(); ?>
<div id="wrapper">
    <div class="content px-3 py-4">
        <div class="row">
            <div class="col-md-12">
                <!-- Header with emoji -->
                <h4 class="tw-mt-0 tw-font-semibold tw-text-xl tw-text-neutral-700 tw-pb-3">
                    💬 Quick Replies
                </h4>

                <div class="panel_s shadow-sm rounded">
                    <div class="panel-body">
                        <!-- Form -->
                        <form id="quick-reply-form" class="mb-4">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
                                   value="<?= $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="id" id="quick-reply-id">

                            <div class="form-group">
                                <label for="quick-reply-name">Name ✏️</label>
                                <input type="text"
                                       class="form-control"
                                       name="name"
                                       id="quick-reply-name"
                                       placeholder="E.g. Follow-up"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="quick-reply-message">Message 📝</label>
                                <textarea class="form-control"
                                          name="message"
                                          id="quick-reply-message"
                                          rows="2"
                                          placeholder="Type your quick reply…"
                                          required></textarea>
                            </div>

                            <button type="submit"
                                    id="quick-reply-submit"
                                    class="btn btn-primary">
                                💾 Save Reply
                            </button>
                        </form>

                        <hr>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover table-striped dt-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID 🔢</th>
                                        <th>Name 🏷️</th>
                                        <th>Message 📄</th>
                                        <th class="text-center">Actions ⚙️</th>
                                    </tr>
                                </thead>
                                <tbody id="quick-reply-list">
                                    <?php foreach($quick_replies as $reply): ?>
                                        <tr data-id="<?= $reply->id ?>">
                                            <td><?= $reply->id ?></td>
                                            <td><?= htmlspecialchars($reply->name) ?></td>
                                            <td><?= htmlspecialchars($reply->message) ?></td>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-info edit-btn"
                                                        data-id="<?= $reply->id ?>"
                                                        data-name="<?= htmlspecialchars($reply->name) ?>"
                                                        data-message="<?= htmlspecialchars($reply->message) ?>"
                                                        title="Edit ✏️">
                                                    <i class="fa fa-pencil-alt"></i>
                                                </button>

                                                <button type="button"
                                                        class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $reply->id ?>"
                                                        title="Delete 🗑️">
                                                    <i class="fa fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($quick_replies)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                No quick replies yet. 🤔
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.content -->
</div><!-- /#wrapper -->
<?php init_tail(); ?>
<?php init_tail(); ?>

<script>
    $(document).ready(function() {
        $('#quick-reply-form').on('submit', function(e) {
            e.preventDefault();
            let id = $('#quick-reply-id').val();
            let url = id ? 'QuickReplies/update/' + id : 'QuickReplies/store';
            let method = 'POST'; // Use POST for both create and update for simplicity

            $.ajax({
                url: url,
                type: method,
                data: $(this).serialize(), // Serialize the form data, including the CSRF token
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });

        $('.edit-btn').on('click', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let message = $(this).data('message');
            $('#quick-reply-id').val(id);
            $('#quick-reply-name').val(name);
            $('#quick-reply-message').val(message);
            $('#quick-reply-submit').text('Update');
        });

        $('.delete-btn').on('click', function() {
            if (confirm('Are you sure you want to delete this quick reply?')) {
                let id = $(this).data('id');

                $.ajax({
                    url: 'QuickReplies/delete/' + id,
                    type: 'POST', // Use POST for delete for simplicity
                    data: {
                        [csrfData.csrf_token_name]: csrfData.csrf_hash // Include CSRF token in the delete request
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert('Error deleting the quick reply.');
                        }
                    }
                });
            }
        });
    });
</script>
