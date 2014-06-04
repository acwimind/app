function init_uploader() {
	
	$("div.input.uploader div.uploader_btn").each(function(){
		
		var allowedExt;
		if ($(this).attr('data-filetypes') != undefined) {
			allowedExt = $(this).attr('data-filetypes').split(',')
		} else {
			allowedExt = ['jpeg', 'jpg', 'png', 'gif'];
		}
		
		$(this).fineUploader({
			request: {
				endpoint: "/uploader/files/upload/token:" + $(this).attr("data-token")
			},
			 failedUploadTextDisplay: {
				 mode: 'custom',
				 maxChars: 60,
				 responseProperty: 'error',
				 enableTooltip: true
			 },
			 multiple: $(this).attr('data-multiple'),
			 validation: {
				 allowedExtensions: allowedExt,
				 sizeLimit: 1024 * 1024 // 1 MB = 1024 * 1024 bytes
			 },
			 text: {
				 uploadButton: 'Upload file'
			 }
		}).on("complete", function(event, id, name, response) {
			if (response.success == true) {
				
				var uploader_files = $("input.uploader_files", $(this).parent("div.input.uploader"));
				
				if ($(this).attr("data-preview") == true) {
					$('ul.qq-upload-list li:not(:last-child)', $(this)).slideUp();
					var preview = $("div.uploader_preview", $(this).parent("div.input.uploader"));
					if (preview.length > 0) {
						var img = '<span><img src="/uploader/files/preview/' + response.filename + "/token:" + $(this).attr("data-token") + '" alt="" /><a href="#" class="del">X</a></span>';
						if ($(this).attr('data-multiple') == true) {
							preview.html( preview.html() + img )
							uploader_files.val( uploader_files.val() + ';' + response.filename);
						} else {
							preview.html(img);
							uploader_files.val(response.filename);
						}
					}
					var preview_default = $("div.uploader_preview_default", $(this).parent("div.input.uploader"));
					if (preview_default.length > 0) {
						preview_default.hide();
					}
				}
				
			}
		});
	});
	$("body").on('click', 'div.input.uploader div.uploader_preview span a.del', function(){
		
		var item = $(this).parent('span');
		var index = item.index();
		
		var uploader_files = $("input.uploader_files", item.closest("div.input.uploader"));
		
		if ($("div.uploader_btn", item.closest("div.input.uploader")).attr('data-multiple') == true) {
			var tmp_data = uploader_files.val().split(';');
			delete tmp_data[ index+1 ];
			uploader_files.val( tmp_data.join(';') );
		} else {
			uploader_files.val( '' );
		}
		
		var preview = $("div.uploader_preview", item.closest("div.input.uploader"));
		item.remove();
		if (preview.length > 0 && preview.html().length == 0) {
			var preview_default = $("div.uploader_preview_default", preview.closest("div.input.uploader"));
			preview_default.show();
		}

		
		
		return false;
		
	});
	
}

$(document).ready(function () {
	init_uploader();
});