/**
 * Admin JavaScript
 *
 * @package Backorder_Flow
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Make status table sortable.
        if ($('#wc-cbs-statuses-tbody').length) {
            $('#wc-cbs-statuses-tbody').sortable({
                handle: '.wc-cbs-col-sort',
                placeholder: 'wc-cbs-status-row ui-state-highlight',
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function() {
                    updateStatusOrder();
                }
            });
        }

        // Save default message.
        $(document).on('click', '.wc-cbs-save-default-message', function(e) {
            e.preventDefault();
            saveDefaultMessage();
        });

        // Add new status button - use event delegation.
        $(document).on('click', '.wc-cbs-add-status', function(e) {
            e.preventDefault();
            openStatusModal();
        });

        // Edit status button.
        $(document).on('click', '.wc-cbs-edit-status', function(e) {
            e.preventDefault();
            var statusData = $(this).data('status');
            openStatusModal(statusData);
        });

        // Delete status button.
        $(document).on('click', '.wc-cbs-delete-status', function(e) {
            e.preventDefault();
            if (confirm(wmbfAdmin.i18n.confirmDelete)) {
                var statusId = $(this).data('status-id');
                deleteStatus(statusId);
            }
        });

        // Modal close.
        $(document).on('click', '.wc-cbs-modal-close, .wc-cbs-modal-cancel', function(e) {
            e.preventDefault();
            closeStatusModal();
        });

        // Close modal on outside click.
        $(document).on('click', '.wc-cbs-modal', function(e) {
            if ($(e.target).hasClass('wc-cbs-modal')) {
                closeStatusModal();
            }
        });

        // Save status button click.
        $(document).on('click', '.wc-cbs-save-status-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            saveStatus();
            return false;
        });

        // Export statuses.
        $(document).on('click', '.wc-cbs-export-statuses', function(e) {
            e.preventDefault();
            exportStatuses();
        });

        // Import statuses button.
        $(document).on('click', '.wc-cbs-import-statuses-btn', function(e) {
            e.preventDefault();
            $('#wc-cbs-import-file').click();
        });

        // Import file selected.
        $('#wc-cbs-import-file').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var data = JSON.parse(e.target.result);
                        importStatuses(data);
                    } catch (err) {
                        alert(wmbfAdmin.i18n.error);
                    }
                };
                reader.readAsText(file);
            }
        });

        // Auto-select status ID when clicked for easy copying.
        $(document).on('click', '#wc-cbs-status-id-value', function() {
            this.select();
        });

        /**
         * Initialize color picker.
         */
        function initColorPicker() {
            if (!$.fn.wpColorPicker) {
                return;
            }

            var $colorInput = $('#wc-cbs-status-color');

            if (!$colorInput.length) {
                return;
            }

            // Destroy existing color picker if it exists.
            if ($colorInput.hasClass('wp-color-picker')) {
                var $wrapper = $colorInput.closest('.wp-picker-container');
                if ($wrapper.length) {
                    $wrapper.after($colorInput);
                    $wrapper.remove();
                }
            }

            // Initialize color picker.
            $colorInput.wpColorPicker({
                defaultColor: '#ff9800',
                change: function() {},
                clear: function() {}
            });
        }

        /**
         * Save default message.
         */
        function saveDefaultMessage() {
            var message = $('#wc-cbs-default-message').val();
            var $button = $('.wc-cbs-save-default-message');

            $button.prop('disabled', true).text(wmbfAdmin.i18n.saving || 'Saving...');

            $.post(wmbfAdmin.ajaxUrl, {
                action: 'wmbf_save_default_message',
                nonce: wmbfAdmin.nonce,
                message: message
            }, function(response) {
                $button.prop('disabled', false).text(wmbfAdmin.i18n.saveDefaultMessage || 'Save Default Message');

                if (response.success) {
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message || wmbfAdmin.i18n.error);
                }
            });
        }

        /**
         * Show admin notice.
         *
         * @param {string} type    Notice type (success, error).
         * @param {string} message Notice message.
         */
        function showNotice(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wc-cbs-settings-wrap').prepend($notice);

            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }

        /**
         * Open status modal.
         *
         * @param {Object} statusData Status data for edit mode.
         */
        function openStatusModal(statusData) {
            var $modal = $('#wc-cbs-status-modal');
            if (!$modal.length) {
                return;
            }

            // Show modal to make elements accessible.
            $modal.show();

            if (statusData) {
                // Edit mode.
                $('.wc-cbs-modal-title').text(wmbfAdmin.i18n.editStatus || 'Edit Status');
                $('#wc-cbs-status-id').val(statusData.id);
                $('#wc-cbs-status-name').val(statusData.name);
                $('#wc-cbs-status-description').val(statusData.description);
                $('#wc-cbs-status-color').val(statusData.color || '#ff9800');
                $('#wc-cbs-status-priority').val(statusData.priority);
                $('#wc-cbs-status-enabled').prop('checked', statusData.enabled);

                // Show and populate status ID field.
                $('#wc-cbs-status-id-display').show();
                $('#wc-cbs-status-id-value').val(statusData.id);
            } else {
                // Add mode.
                $('.wc-cbs-modal-title').text(wmbfAdmin.i18n.addStatus || 'Add New Status');

                // Clear all fields manually.
                $('#wc-cbs-status-name').val('');
                $('#wc-cbs-status-description').val('');
                $('#wc-cbs-status-id').val('');
                $('#wc-cbs-status-id-display').hide();
                $('#wc-cbs-status-color').val('#ff9800');
                $('#wc-cbs-status-priority').val('1');
                $('#wc-cbs-status-enabled').prop('checked', true);
            }

            // Fade in modal.
            $modal.fadeIn(300);

            // Initialize color picker after modal is visible.
            setTimeout(function() {
                initColorPicker();
            }, 350);
        }

        /**
         * Close status modal.
         */
        function closeStatusModal() {
            $('#wc-cbs-status-modal').fadeOut(200);
        }

        /**
         * Save status.
         */
        function saveStatus() {
            var statusName = $('#wc-cbs-status-name').val();

            if (!statusName || statusName.trim() === '') {
                alert(wmbfAdmin.i18n.enterName || 'Please enter a status name');
                return false;
            }

            // Get the color value.
            var colorValue = $('#wc-cbs-status-color').val();

            // If wpColorPicker is initialized, try to get value from it.
            if ($('#wc-cbs-status-color').hasClass('wp-color-picker')) {
                try {
                    var pickerColor = $('#wc-cbs-status-color').wpColorPicker('color');
                    if (pickerColor) {
                        colorValue = pickerColor;
                    }
                } catch(e) {
                    // Use fallback value.
                }
            }

            // Fallback to default color if empty.
            if (!colorValue || colorValue === '') {
                colorValue = '#ff9800';
            }

            var formData = {
                action: 'wmbf_add_status',
                nonce: wmbfAdmin.nonce,
                status_id: $('#wc-cbs-status-id').val(),
                name: statusName.trim(),
                description: $('#wc-cbs-status-description').val(),
                color: colorValue,
                priority: $('#wc-cbs-status-priority').val() || '1',
                enabled: $('#wc-cbs-status-enabled').is(':checked') ? 'true' : 'false'
            };

            // Disable submit button.
            var $submitBtn = $('.wc-cbs-save-status-btn');
            $submitBtn.prop('disabled', true).text(wmbfAdmin.i18n.saving || 'Saving...');

            $.ajax({
                url: wmbfAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        closeStatusModal();

                        // Disable the "unsaved changes" warning before reloading.
                        window.onbeforeunload = null;

                        setTimeout(function() {
                            location.reload(true);
                        }, 500);
                    } else {
                        alert(response.data.message || wmbfAdmin.i18n.error);
                        $submitBtn.prop('disabled', false).text(wmbfAdmin.i18n.saveStatus || 'Save Status');
                    }
                },
                error: function() {
                    alert(wmbfAdmin.i18n.error);
                    $submitBtn.prop('disabled', false).text(wmbfAdmin.i18n.saveStatus || 'Save Status');
                }
            });

            return false;
        }

        /**
         * Delete status.
         *
         * @param {string} statusId Status ID.
         */
        function deleteStatus(statusId) {
            $.post(wmbfAdmin.ajaxUrl, {
                action: 'wmbf_delete_status',
                nonce: wmbfAdmin.nonce,
                status_id: statusId
            }, function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);

                    // Disable the "unsaved changes" warning before reloading.
                    window.onbeforeunload = null;

                    setTimeout(function() {
                        location.reload(true);
                    }, 500);
                } else {
                    alert(response.data.message || wmbfAdmin.i18n.error);
                }
            });
        }

        /**
         * Update status order.
         */
        function updateStatusOrder() {
            var order = [];
            $('#wc-cbs-statuses-tbody tr').each(function() {
                order.push($(this).data('status-id'));
            });

            $.post(wmbfAdmin.ajaxUrl, {
                action: 'wmbf_update_status_order',
                nonce: wmbfAdmin.nonce,
                order: order
            });
        }

        /**
         * Export statuses.
         */
        function exportStatuses() {
            $.post(wmbfAdmin.ajaxUrl, {
                action: 'wmbf_export_statuses',
                nonce: wmbfAdmin.nonce
            }, function(response) {
                if (response.success) {
                    var dataStr = JSON.stringify(response.data.data, null, 2);
                    var dataBlob = new Blob([dataStr], {type: 'application/json'});
                    var url = URL.createObjectURL(dataBlob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'backorder-statuses-export.json';
                    link.click();
                }
            });
        }

        /**
         * Import statuses.
         *
         * @param {Object} data Statuses data.
         */
        function importStatuses(data) {
            if (confirm(wmbfAdmin.i18n.confirmImport || 'This will replace all existing statuses. Are you sure?')) {
                $.post(wmbfAdmin.ajaxUrl, {
                    action: 'wmbf_import_statuses',
                    nonce: wmbfAdmin.nonce,
                    data: JSON.stringify(data)
                }, function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);

                        // Disable the "unsaved changes" warning before reloading.
                        window.onbeforeunload = null;

                        setTimeout(function() {
                            location.reload(true);
                        }, 500);
                    } else {
                        alert(response.data.message || wmbfAdmin.i18n.error);
                    }
                });
            }
        }
    });

})(jQuery);
