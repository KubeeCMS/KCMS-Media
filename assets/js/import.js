jQuery( document ).ready(function() {
  //import from old version
  jQuery('.njt_fbv_import_from_old_now').click(function(){
    var $this = jQuery(this)
    $this.addClass('updating-message')

    get_folders(function(res) {
      if(res.success) {
        insert_folder(res.data.folders, 0, function(){
          $this.removeClass('updating-message')
        }, function(){
          $this.removeClass('updating-message')
        })
      }
    }, function(){
      $this.removeClass('updating-message')
      alert('Please try again.')
    })

    function get_folders(onDone, onFail) {
      jQuery.ajax({
        url: fbv_data.json_url + '/fb-get-old-data',
        method: 'POST',
        beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
        }
      })
      .done(function(res){
        onDone(res)
      })
      .fail(function(res){
        onFail(res)
      })
    }
    function insert_folder(folders, index, onDone, onFail) {
      if(typeof folders[index] != 'undefined') {
        jQuery.ajax({
          url: fbv_data.json_url + '/fb-insert-old-data',
          method: 'POST',
          beforeSend: function ( xhr ) {
            xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
          },
          data: {
            folder: folders[index]
          }
        })
        .done(function(res){
          insert_folder(folders, index + 1, onDone, onFail)
        })
        .fail(function(res){
          onFail();
          alert('Please try again.')
        })
      } else {
        onDone()
      }
    }
  })
  //wipe old data
  jQuery('.njt_fbv_wipe_old_data').click(function(){
    if(!confirm(fbv_data.i18n.are_you_sure)) return false;

    var $this = jQuery(this)
    $this.addClass('updating-message')
    jQuery.ajax({
        url: fbv_data.json_url + '/fb-wipe-old-data',
        method: 'POST',
        beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
        }
    })
    .done(function(res){
      $this.removeClass('updating-message')
      alert(res.data.mess);
    })
    .fail(function(res){
        $this.removeClass('updating-message')
        alert(res.data.mess);
    })
  })
  //clear all data
  jQuery('.njt_fbv_clear_all_data').click(function(){
    if(!confirm(fbv_data.i18n.are_you_sure)) return false;

    var $this = jQuery(this)
    $this.addClass('updating-message')
    jQuery.ajax({
      url: fbv_data.json_url + '/fb-wipe-clear-all-data',
      method: 'POST',
      beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
      }
    })
    .done(function(res){
      $this.removeClass('updating-message')
      alert(res.data.mess);
    })
    .fail(function(res){
        $this.removeClass('updating-message')
        alert(res.data.mess);
    })
  })
  //no thanks btn
  jQuery('.njt_fb_no_thanks_btn').click(function(){
    var $this = jQuery(this);
    $this.addClass('updating-message')
    jQuery.ajax({
      type: "post",
      url: fbv_data.json_url + '/fb-no-thanks',
      beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce );
      },
      data: {
        nonce: fbv_data.nonce,
        site: $this.data('site')
      },
      success: function (res) {
        $this.removeClass('updating-message');
        jQuery('.njt.notice.notice-warning.' + $this.data('site')).hide()
      }
    })
    .fail(function(res){
        $this.removeClass('updating-message');
        alert('Please try again later')
      });
  })
  jQuery('.njt-fb-import').click(function(){
    var $this = jQuery(this)
    $this.addClass('updating-message')
      jQuery.ajax({
        url: fbv_data.json_url + '/fb-import',
        method: 'POST',
        beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
        },
        data: {
            site: $this.data('site'),
            count: $this.data('count')
        }
    })
    .done(function(res){
      if(res.data.folders) {
        var folders = res.data.folders
        var site = res.data.site
        import_site(folders, site, 0, function(res){
          if(res.success) {
            $this.removeClass('updating-message')
            var html_notice = '<div class="njt-success-notice notice notice-warning is-dismissible"><p>'+res.data.mess+'</p><button type="button" class="notice-dismiss" onClick="jQuery(\'.njt-success-notice\').remove()"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            jQuery(html_notice).insertBefore('form#post');
          }
        })
      } else {
        $this.removeClass('updating-message')
        alert(res.data.mess)
      }
        
    })
    .fail(function(res){
        $this.removeClass('updating-message')
        alert('Please try again later');
    })
    
  })
  function import_site(folders, site, index, on_done) {
    if(typeof folders[index] != 'undefined') {
      jQuery.ajax({
        url: fbv_data.json_url + '/fb-import-insert-folder',
        method: 'POST',
        beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
        },
        data: {
            site: site,
            folders: folders[index]
        }
      })
      .done(function(res){
        import_site(folders, site, index + 1, on_done)
      })
    } else {
      jQuery.ajax({
        url: fbv_data.json_url + '/fb-import-after-inserting',
        method: 'POST',
        beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce )
        },
        data: {
            site: site,
        }
      })
      .done(function(res){
        on_done(res)
      })
    } 
  }


  //generate API key
  jQuery('.fbv_generate_api_key_now').click(function(){
    if(!confirm(fbv_data.i18n.are_you_sure)) return false;
    var $this = jQuery(this);
    $this.addClass('updating-message')
    jQuery.ajax({
      type: "post",
      url: fbv_data.json_url + '/fbv-api',
      beforeSend: function ( xhr ) {
        xhr.setRequestHeader( 'X-WP-Nonce', fbv_data.rest_nonce );
      },
      data: {
        act: 'generate-key'
      },
      success: function (res) {
        $this.removeClass('updating-message');
        if(res.success) {
          var key = res.data.key
          jQuery('#fbv_rest_api_key').removeClass('hidden');
          jQuery('#fbv_rest_api_key').val(key)
        } else {
          alert(res.data.mess)
        }
      }
    })
    .fail(function(res){
        $this.removeClass('updating-message');
        alert('Please try again later')
      });
  })
})