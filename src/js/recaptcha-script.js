
function RecaptchaScriptSetExecuteFunction() {
    if ( !window.grecaptcha ) { return; }
    if ( !window.wpcf7_recaptcha ) { return; }
    if ( window.wpcf7_recaptcha.execute ) { return; }

    wpcf7_recaptcha.execute = function( action ) {
        grecaptcha.execute(
            wpcf7_recaptcha.sitekey,
            { action: action }
        ).then( function( token ) {
            var event = new CustomEvent( 'wpcf7grecaptchaexecuted', {
                detail: {
                    action: action,
                    token: token,
                },
            } );

            document.dispatchEvent( event );
        } );
    };

    wpcf7_recaptcha.execute_on_homepage = function() {
        wpcf7_recaptcha.execute( wpcf7_recaptcha.actions[ 'homepage' ] );
    };

    wpcf7_recaptcha.execute_on_contactform = function() {
        wpcf7_recaptcha.execute( wpcf7_recaptcha.actions[ 'contactform' ] );
    };

    grecaptcha.ready(
        wpcf7_recaptcha.execute_on_homepage
    );

    document.addEventListener( 'change',
        wpcf7_recaptcha.execute_on_contactform
    );

    document.addEventListener( 'wpcf7submit',
        wpcf7_recaptcha.execute_on_homepage
    );
}

function setTimeOutRecaptchaScript() {
    if  ( window.wpcf7_recaptcha && window.wpcf7_recaptcha.execute ) { return; }
    RecaptchaScriptSetExecuteFunction();
    setTimeout( setTimeOutRecaptchaScript, 500 );
}

setTimeOutRecaptchaScript();