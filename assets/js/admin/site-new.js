jQuery(document).ready(function($){
    var mediaUploader;
    $('#poc-site-new #poc-upload-avatar').click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#poc-user-avatar').val(attachment.url);
            $('#poc-user-avatar-preview').attr('src', attachment.url);
            $('#poc-user-avatar-preview').css('display', 'block');
        });
        mediaUploader.open();
    });
});