(function($){

	window.Q.SWFUpload = {
		file_queued_handler: function(file){
			try {
				var file=new SUFile(this, file);
				file.toggleCancel(true);
			} catch (ex) {
				this.debug(ex);
			}
		},
		file_queue_error_handler: function(file, errorCode, message){
			try {
				if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
					$.dialog("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
					return;
				}

				var file=new SUFile(this, file);
				
				file.setError();
				file.toggleCancel(false);
				
				switch (errorCode) {
				case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
					file.setStatus("File is too big.");
					break;
				case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
					file.setStatus("Cannot upload Zero Byte files.");
					break;
				case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
					file.setStatus("Invalid SUFile Type.");
					break;
				default:
					if (file !== null) {
						file.setStatus("Unhandled Error");
					}
				}

			} catch (ex) {
				this.debug(ex);
			}
		},
		
		file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {
			try {
				if (numFilesSelected > 0 && numFilesQueued>0) {
					$(['#',this.customSettings.cancelButton].join('')).show();
					//$(['#', this.customSettings.fileContainer].join('')).empty();
					/* I want auto start the upload and I can do that here */
					this.startUpload();
				}
				
			} catch (ex)  {
				this.debug(ex);
			}
		},
		
		upload_start_handler: function(file) {
			try {
				/* I don't want to do any file validation or anything,  I'll just update the UI and
				return true to indicate that the upload should start.
				It's important to update the UI here because in Linux no uploadProgress events are called. The best
				we can do is say we are uploading.
				 */
				var file = new SUFile(this, file);
				file.setStatus("Starting...");
				file.toggleCancel(true);
			}
			catch (ex) {}
			
			return true;
		},
		upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
			try {
				var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
				var file = new SUFile(this, file);
				file.setProgress(percent);
				file.setStatus("Uploading...");
			} catch (ex) {
				this.debug(ex);
			}
		},
		upload_success_handler: function(file, data) {
			try {
				var file = new SUFile(this, file);
				file.setStatus("Complete.");
				file.toggleCancel(false);
				file.setComplete(file, data);		
			} catch (ex) {
				this.debug(ex);
			}
		},
		upload_error_handler: function(file, errorCode, message) {
			try {
				var file = new SUFile(this, file);
				file.setError();
				file.toggleCancel(false);
		
				switch (errorCode) {
				case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
					file.setStatus("Upload Error: " + message);
					break;
				case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
					file.setStatus("Upload Failed.");
					break;
				case SWFUpload.UPLOAD_ERROR.IO_ERROR:
					file.setStatus("Server (IO) Error");
					break;
				case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
					file.setStatus("Security Error");
					break;
				case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
					file.setStatus("Upload limit exceeded.");
					break;
				case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
					file.setStatus("Failed Validation.  Upload skipped.");
					break;
				case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
					// If there aren't any files left (they were all cancelled) disable the cancel button
					if (this.getStats().files_queued === 0) {
						$(['#',this.customSettings.cancelButton].join('')).hide();
					}
					file.setStatus("Cancelled");
					file.setCancelled();
					break;
				case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
					file.setStatus("Stopped");
					break;
				default:
					file.setStatus("Unhandled Error: " + errorCode);
					this.debug("Error Code: " + errorCode + ", SUFile name: " + file.name + ", SUFile size: " + file.size + ", Message: " + message);
					break;
				}
			} catch (ex) {
				this.debug(ex);
			}
		},
		
		upload_complete_handler: function (file) {
			if (this.getStats().files_queued === 0) {
				//last one?
					$(['#',this.customSettings.cancelButton].join('')).hide();
			}
		},
		
		// This event comes from the Queue Plugin
		queue_complete_handler: function(numFilesUploaded) {
			//TODO: everything completed.
			$(['#',this.customSettings.cancelButton].join('')).hide();
		}
		
	};

	var SUFile = function(handle, file){
		this.id=file.id;
		this.handle=handle;
		this.parent=$(['#', handle.customSettings.fileContainer].join(''));
		this.element=$(['#', file.id].join(''));
		this.progress=this.element.find('.progress_bar').eq(0);

		if(this.element.length<1){
			//create a new element
			this.element=$(['#', handle.customSettings.fileTemplate].join('')).clone();
			this.element.attr('id', file.id).appendTo(this.parent);
			this.element.find('.filename').html(file.name);
		}
		
	};

	SUFile.prototype.setStatus=function(status){
			this.element.find('.status').html(status);
		};
				
	SUFile.prototype.setProgress = function (percentage) {
		this.progress.children('span').animate({width:this.progress.width()*percentage/100});
	};

	SUFile.prototype.setComplete = function (file, data) {
		this.progress.addClass('progress_complete');
		this.progress.children('span').animate({width:this.progress.width()});
		this.handle.customSettings.fileUploaded(file, data);
	};

	SUFile.prototype.setError = function () {
		this.progress.addClass('progress_error');
	};

	SUFile.prototype.setCancelled = function () {
		this.progress.addClass('progress_disabled');
		var el=this.element;
		window.setTimeout(function(){
				el.fadeOut('slow', function(){
					$(this).remove();
					});
				}, 3000);
	};

	// Show/Hide the cancel button
	SUFile.prototype.toggleCancel = function(status) {
		//this.handle.cancelUpload(this.id);
	};

	window['SUFile'] = SUFile;

})(jQuery);
