// Init single quotation
function init_quotation(id) {
    load_small_table_item(id, '#quotation', 'quotation_id', 'quotations/get_quotation_data_ajax', '.table-quotations');
}

/*
if ($("body").hasClass('quotations-pipeline')) {
    var quotation_id = $('input[name="quotationid"]').val();
    quotation_pipeline_open(quotation_id);
}
*/

function add_quotation_comment() {
    var comment = $('#comment').val();
    if (comment == '') {
        return;
    }
    var data = {};
    data.content = comment;
    data.quotationid = quotation_id;
    $('body').append('<div class="dt-loader"></div>');
    $.post(admin_url + 'quotations/add_quotation_comment', data).done(function (response) {
        response = JSON.parse(response);
        $('body').find('.dt-loader').remove();
        if (response.success == true) {
            $('#comment').val('');
            get_quotation_comments();
        }
    });
}

function get_quotation_comments() {
    if (typeof (quotation_id) == 'undefined') {
        return;
    }
    requestGet('quotations/get_quotation_comments/' + quotation_id).done(function (response) {
        $('body').find('#quotation-comments').html(response);
        update_comments_count('quotation')
    });
}

function remove_quotation_comment(commentid) {
    if (confirm_delete()) {
        requestGetJSON('quotations/remove_comment/' + commentid).done(function (response) {
            if (response.success == true) {
                $('[data-commentid="' + commentid + '"]').remove();
                update_comments_count('quotation')
            }
        });
    }
}

function edit_quotation_comment(id) {
    var content = $('body').find('[data-quotation-comment-edit-textarea="' + id + '"] textarea').val();
    if (content != '') {
        $.post(admin_url + 'quotations/edit_comment/' + id, {
            content: content
        }).done(function (response) {
            response = JSON.parse(response);
            if (response.success == true) {
                alert_float('success', response.message);
                $('body').find('[data-quotation-comment="' + id + '"]').html(nl2br(content));
            }
        });
        toggle_quotation_comment_edit(id);
    }
}

function toggle_quotation_comment_edit(id) {
    $('body').find('[data-quotation-comment="' + id + '"]').toggleClass('hide');
    $('body').find('[data-quotation-comment-edit-textarea="' + id + '"]').toggleClass('hide');
}


function add_quotation_note() {
    var note = $('#note').val();
    if (note == '') {
        return;
    }
    var data = {};
    data.content = note;
    data.quotationid = quotation_id;
    $('body').append('<div class="dt-loader"></div>');
    $.post(admin_url + 'quotations/add_quotation_note', data).done(function (response) {
        response = JSON.parse(response);
        $('body').find('.dt-loader').remove();
        if (response.success == true) {
            $('#note').val('');
            get_quotation_notes();
        }
    });
}


    
function get_quotation_notes() {
    if (typeof (quotation_id) == 'undefined') {
        return;
    }
    requestGet('quotations/get_quotation_notes/' + quotation_id).done(function (response) {
        $('body').find('#quotation-notes').html(response);
        update_notes_count('quotation')
    });
}

function remove_quotation_note(noteid) {
    if (confirm_delete()) {
        requestGetJSON('quotations/remove_note/' + noteid).done(function (response) {
            if (response.success == true) {
                $('[data-noteid="' + noteid + '"]').remove();
                update_notes_count('quotation')
            }
        });
    }
}

function edit_quotation_note(id) {
    var content = $('body').find('[data-quotation-note-edit-textarea="' + id + '"] textarea').val();
    if (content != '') {
        $.post(admin_url + 'quotations/edit_note/' + id, {
            content: content
        }).done(function (response) {
            response = JSON.parse(response);
            if (response.success == true) {
                alert_float('success', response.message);
                $('body').find('[data-quotation-note="' + id + '"]').html(nl2br(content));
            }
        });
        toggle_quotation_note_edit(id);
    }
}

function toggle_quotation_note_edit(id) {
    $('body').find('[data-quotation-note="' + id + '"]').toggleClass('hide');
    $('body').find('[data-quotation-note-edit-textarea="' + id + '"]').toggleClass('hide');
}

function update_notes_count() {
  var count = $(".note-item").length;
  $(".total_notes").text(count);
  if (count === 0) {
    $(".total_notes").addClass("hide");
  } else {
    $(".total_notes").removeClass("hide");
  }
}

function quotation_convert_template(invoker) {
    var template = $(invoker).data('template');
    var html_helper_selector;
    if (template == 'quotation') {
        html_helper_selector = 'quotation';
    } else if (template == 'invoice') {
        html_helper_selector = 'invoice';
    } else {
        return false;
    }

    requestGet('quotations/get_' + html_helper_selector + '_convert_data/' + quotation_id).done(function (data) {
        if ($('.quotation-pipeline-modal').is(':visible')) {
            $('.quotation-pipeline-modal').modal('hide');
        }
        $('#convert_helper').html(data);
        $('#convert_to_' + html_helper_selector).modal({
            show: true,
            backdrop: 'static'
        });
        reorder_items();
    });

}

function save_quotation_content(manual) {
    var editor = tinyMCE.activeEditor;
    var data = {};
    data.quotation_id = quotation_id;
    data.content = editor.getContent();
    $.post(admin_url + 'quotations/save_quotation_data', data).done(function (response) {
        response = JSON.parse(response);
        if (typeof (manual) != 'undefined') {
            // Show some message to the user if saved via CTRL + S
            alert_float('success', response.message);
        }
        // Invokes to set dirty to false
        editor.save();
    }).fail(function (error) {
        var response = JSON.parse(error.responseText);
        alert_float('danger', response.message);
    });
}

// Proposal sync data in case eq mail is changed, shown for lead and customers.
function sync_quotations_data(rel_id, rel_type) {
    var data = {};
    var modal_sync = $('#sync_data_quotation_data');
    data.country = modal_sync.find('select[name="country"]').val();
    data.zip = modal_sync.find('input[name="zip"]').val();
    data.state = modal_sync.find('input[name="state"]').val();
    data.city = modal_sync.find('input[name="city"]').val();
    data.address = modal_sync.find('textarea[name="address"]').val();
    data.phone = modal_sync.find('input[name="phone"]').val();
    data.rel_id = rel_id;
    data.rel_type = rel_type;
    $.post(admin_url + 'quotations/sync_data', data).done(function (response) {
        response = JSON.parse(response);
        alert_float('success', response.message);
        modal_sync.modal('hide');
    });
}


// Delete quotation attachment
function delete_quotation_attachment(id) {
    if (confirm_delete()) {
        requestGet('quotations/delete_attachment/' + id).done(function (success) {
            if (success == 1) {
                var rel_id = $("body").find('input[name="_attachment_sale_id"]').val();
                $("body").find('[data-attachment-id="' + id + '"]').remove();
                $("body").hasClass('quotations-pipeline') ? quotation_pipeline_open(rel_id) : init_quotation(rel_id);
            }
        }).fail(function (error) {
            alert_float('danger', error.responseText);
        });
    }
}

// Used when quotation is updated from pipeline. eq changed order or moved to another status
function quotations_pipeline_update(ui, object) {
    if (object === ui.item.parent()[0]) {
        var data = {
            quotationid: $(ui.item).attr('data-quotation-id'),
            status: $(ui.item.parent()[0]).attr('data-status-id'),
            order: [],
        };

        $.each($(ui.item).parents('.pipeline-status').find('li'), function (idx, el) {
            var id = $(el).attr('data-quotation-id');
            if(id){
                data.order.push([id, idx+1]);
            }
        });

        check_kanban_empty_col('[data-quotation-id]');

        setTimeout(function () {
             $.post(admin_url + 'quotations/update_pipeline', data).done(function (response) {
                update_kan_ban_total_when_moving(ui,data.status);
                quotation_pipeline();
            });
        }, 200);
    }
}

// Used when quotation is updated from pipeline. eq changed order or moved to another status
function quotations_pipeline_update(ui, object) {
    if (object === ui.item.parent()[0]) {
        var data = {
            order: [],
            status: $(ui.item.parent()[0]).attr('data-status-id'),
            quotationid: $(ui.item).attr('data-quotation-id'),
        };

        $.each($(ui.item).parents('.pipeline-status').find('li'), function (idx, el) {
            var id = $(el).attr('data-quotation-id');
            if(id){
                data.order.push([id, idx+1]);
            }
        });

        check_kanban_empty_col('[data-quotation-id]');

        setTimeout(function () {
            $.post(admin_url + 'quotations/update_pipeline', data).done(function (response) {
                update_kan_ban_total_when_moving(ui,data.status);
                quotations_pipeline();
            });
        }, 200);
    }
}

// Init quotations pipeline
function quotations_pipeline() {
    init_kanban('quotations/get_pipeline', quotations_pipeline_update, '.pipeline-status', 347, 360);
}

// Open single quotation in pipeline
function quotation_pipeline_open(id) {
    if (id === '') {
        return;
    }
    requestGet('quotations/pipeline_open/' + id).done(function (response) {
        var visible = $('.quotation-pipeline-modal:visible').length > 0;
        $('#quotation').html(response);
        if (!visible) {
            $('.quotation-pipeline-modal').modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
        } else {
            $('#quotation').find('.modal.quotation-pipeline-modal')
                .removeClass('fade')
                .addClass('in')
                .css('display', 'block');
        }
    });
}

// Sort quotations in the pipeline view / switching sort type by click
function quotation_pipeline_sort(type) {
    kan_ban_sort(type, quotations_pipeline);
}

// Validates quotation add/edit form
function validate_quotation_form(selector) {

    selector = typeof (selector) == 'undefined' ? '#quotation-form' : selector;

    appValidateForm($(selector), {
        rel_id: {
            required: {
                depends: function () {
                    var customerRemoved = $('select#rel_type').hasClass('customer-removed');
                    return !customerRemoved;
                }
            }
        },
        date: 'required',
        number: {
            required: true
        }
    });

    $("body").find('input[name="number"]').rules('add', {
        remote: {
            url: admin_url + "quotations/validate_quotation_number",
            type: 'post',
            data: {
                number: function () {
                    return $('input[name="number"]').val();
                },
                isedit: function () {
                    return $('input[name="number"]').data('isedit');
                },
                original_number: function () {
                    return $('input[name="number"]').data('original-number');
                },
                date: function () {
                    return $('body').find('.quotation input[name="date"]').val();
                },
            }
        },
        messages: {
            remote: app.lang.quotation_number_exists,
        }
    });

}


// Get the preview main values
function get_quotation_item_preview_values() {
    var response = {};
    response.description = $('.main textarea[name="description"]').val();
    response.long_description = $('.main textarea[name="long_description"]').val();
    response.qty = $('.main input[name="quantity"]').val();
    return response;
}

// Append the added items to the preview to the table as items
function add_quotation_item_to_table(data, itemid){

  // If not custom data passed get from the preview
  data = typeof (data) == 'undefined' || data == 'undefined' ? get_quotation_item_preview_values() : data;
  if (data.description === "" && data.long_description === "") {
     return;
  }

  var table_row = '';
  var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('tbody .item').length + 1;
  lastAddedItemKey = item_key;

  table_row += '<tr class="sortable item">';

  table_row += '<td class="dragger">';

  // Check if quantity is number
  if (isNaN(data.qty)) {
     data.qty = 1;
  }

  $("body").append('<div class="dt-loader"></div>');
  var regex = /<br[^>]*>/gi;

     table_row += '<input type="hidden" class="order" name="newitems[' + item_key + '][order]">';

     table_row += '</td>';

     table_row += '<td class="bold description"><textarea name="newitems[' + item_key + '][description]" class="form-control" rows="5">' + data.description + '</textarea></td>';

     table_row += '<td><textarea name="newitems[' + item_key + '][long_description]" class="form-control item_long_description" rows="5">' + data.long_description.replace(regex, "\n") + '</textarea></td>';
   //table_row += '<td><textarea name="newitems[' + item_key + '][long_description]" class="form-control item_long_description" rows="5">' + data.long_description + '</textarea></td>';


     table_row += '<td><input type="number" min="0" onblur="calculate_total();" onchange="calculate_total();" data-quantity name="newitems[' + item_key + '][qty]" value="' + data.qty + '" class="form-control">';

     if (!data.unit || typeof (data.unit) == 'undefined') {
        data.unit = '';
     }

     table_row += '<input type="text" placeholder="' + app.lang.unit + '" name="newitems[' + item_key + '][unit]" class="form-control input-transparent text-right" value="' + data.unit + '">';

     table_row += '</td>';


     table_row += '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' + itemid + '); return false;"><i class="fa fa-trash"></i></a></td>';

     table_row += '</tr>';

     $('table.items tbody').append(table_row);

     $(document).trigger({
        type: "item-added-to-table",
        data: data,
        row: table_row
     });


     clear_item_preview_values();
     reorder_items();

     $('body').find('#items-warning').remove();
     $("body").find('.dt-loader').remove();

  return false;
}


// From quotation table mark as
function quotation_mark_as(status_id, quotation_id) {
    var data = {};
    data.status = status_id;
    data.quotationid = quotation_id;
    $.post(admin_url + 'quotations/update_quotation_status', data).done(function (response) {
        //table_quotations.DataTable().ajax.reload(null, false);
        reload_quotations_tables();
    });
}


// From quotation table mark as
function quotation_status_mark_as(status_id, quotation_id) {
    var data = {};
    data.status = status_id;
    data.quotationid = quotation_id;
    $.post(admin_url + 'quotations/update_quotation_status', data).done(function (response) {
        //table_quotations.DataTable().ajax.reload(null, false);
        reload_quotations_tables();
    });
    init_quotation(quotation_id);
}


// Reload all quotations possible table where the table data needs to be refreshed after an action is performed on task.
function reload_quotations_tables() {
    var av_quotations_tables = ['.table-quotations', '.table-rel-quotations'];
    $.each(av_quotations_tables, function (i, selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    });
}

function init_quotations_attach_file(){

  $("#quotations_attach_file").on("hidden.bs.modal", function (e) {
    $("#sales_uploaded_files_preview").empty();
    $(".dz-file-preview").empty();
  });

  if (typeof Dropbox != "undefined") {
    if ($("#dropbox-chooser-sales").length > 0) {
      document.getElementById("dropbox-chooser-sales").appendChild(
        Dropbox.createChooseButton({
          success: function (files) {
            salesExtenalFileUpload(files, "dropbox");
          },
          linkType: "preview",
          extensions: app.options.allowed_files.split(","),
        })
      );
    }
  }
  /*
  if ($("#sales-upload").length > 0) {
    new Dropzone(
      "#sales-upload",
      appCreateDropzoneOptions({
        sending: function (file, xhr, formData) {
          formData.append(
            "rel_id",
            $("body").find('input[name="_attachment_sale_id"]').val()
          );
          formData.append(
            "type",
            $("body").find('input[name="_attachment_sale_type"]').val()
          );
        },
        success: function (files, response) {
          response = JSON.parse(response);
          var type = $("body")
            .find('input[name="_attachment_sale_type"]')
            .val();
          var dl_url, delete_function;
          dl_url = "download/file/sales_attachment/";
          delete_function = "delete_" + type + "_attachment";
          if (type == "estimate") {
            $("body").hasClass("estimates-pipeline")
              ? estimate_pipeline_open(response.rel_id)
              : init_estimate(response.rel_id);
          } else if (type == "proposal") {
            $("body").hasClass("proposals-pipeline")
              ? proposal_pipeline_open(response.rel_id)
              : init_proposal(response.rel_id);
          } else {
            if (typeof window["init_" + type] == "function") {
              window["init_" + type](response.rel_id);
            }
          }
          var data = "";
          if (response.success === true || response.success == "true") {
            data +=
              '<div class="display-block sales-attach-file-preview" data-attachment-id="' +
              response.attachment_id +
              '">';
            data += '<div class="col-md-10">';
            data +=
              '<div class="pull-left"><i class="attachment-icon-preview fa-regular fa-file"></i></div>';
            data +=
              '<a href="' +
              site_url +
              dl_url +
              response.key +
              '" target="_blank">' +
              response.file_name +
              "</a>";
            data += '<p class="text-muted">' + response.filetype + "</p>";
            data += "</div>";
            data += '<div class="col-md-2 text-right">';
            data +=
              '<a href="#" class="text-danger" onclick="' +
              delete_function +
              "(" +
              response.attachment_id +
              '); return false;"><i class="fa fa-times"></i></a>';
            data += "</div>";
            data += '<div class="clearfix"></div><hr/>';
            data += "</div>";
            $("#sales_uploaded_files_preview").append(data);
          }
        },
      })
    );
  }
  */
}