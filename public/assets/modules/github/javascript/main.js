$().ready(function() {
  (function() {
    $('#repository_history .commit_history_table tr')
      .each(function(count, row) {
        var row = $(row);
        var commit_id = $(row).attr('data-pk'),
            toggle = $(row).find('.file_toggle > a'),
            files_div = $(row).find('.commit_files');
        
        files_div.hide();
        
        if(commit_id != '') {
          toggle
            .click(function() {
              if(toggle.text() == 'Show Changes') {
                files_div.show();
                toggle.text('Hide Changes');
                if(files_div.text() == '') {
                  files_div.append('<img src="' + App.data.indicator_url + '" />');
                  $.getJSON(App.data.commit_filepath_url,
                            {commit_id:commit_id},
                            function(data, status, xhr) {
                                var mod_html, add_html, del_html, err_html = '';
                                
                                files_div.find('img').remove();
                                
                                if(data.errors != undefined) {
                                  err_html = '<p><strong>Errors Occurred:</strong></p><ul>'
                                  $.each(data.errors, function(_count, error) {
                                    err_html += '<li>' + error + '</li>';
                                  });
                                  err_html += '</ul>';
                                  file_div.append(err_html)
                                } else {
                                
                                  if(data.modified != undefined && data.modified.length > 0) {
                                    mod_html = '<ul class="action_group"><li><span class="action modified">modified</span><ul>';
                                    $.each(data.modified, function(_count, path) {
                                      mod_html += '<li>' + path + '</li>';
                                    });
                                    mod_html += '</ul></li></ul>';
                                  }
                                  if(data.added != undefined && data.added.length > 0) {
                                    add_html = '<ul class="action_group"><li><span class="action added">added</span><ul>';
                                    $.each(data.added, function(_count, path) {
                                      add_html += '<li>' + path + '</li>';
                                    });
                                    add_html += '</ul></li></ul>';
                                  }
                                  if(data.removed != undefined && data.removed.length > 0) {
                                    del_html = '<ul class="action_group"><li><span class="action removed">removed</span><ul>';
                                    $.each(data.removed, function(_count, path) {
                                      del_html += '<li>' + path + '</li>';
                                    });
                                    del_html += '</ul></li></ul>';
                                  }
                                  
                                  files_div.append(mod_html);
                                  files_div.append(add_html);
                                  files_div.append(del_html);
                                }
                            });
                  }
                } else {
                  files_div.hide();
                  toggle.text('Show Changes');
                }
            });
        }
      });
  })();
})