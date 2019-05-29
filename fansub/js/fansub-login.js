

jQuery(document).ready(function($) {
    (function() {
        var logo_url = fansub.login_logo_url;
        if(fansub.isImageUrl(logo_url)) {
            $('.login #login > h1 a').html(fansub.createImageHTML({src: logo_url}));
        }
        $('form .submit .button').attr('class', 'btn btn-warning');
    })();

    (function(){
        $('#nav').find('a').each(function(){
            var that = $(this),
                action = fansub.getParamByName(that.attr('href'), 'action');
            that.addClass(action);
        });
    })();

    (function() {
        fansub.iconChangeCaptchaExecute();
    })();
});