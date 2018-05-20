<?php
    error_reporting(E_ALL);
    ini_set("display_errors", 1);

    require_once('./config.php');
    require_once('./db.php');

    $currentpath = dirname($_SERVER['SCRIPT_NAME']);
    if($currentpath == '/'){
        $currentpath = '';
    }

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Kappelt gBridge</title>
    
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>
    </head>
    <body>

<?php

    //See https://developers.google.com/actions/identity/oauth2-implicit-flow for auth info
    //check parameters provided by Google
    //1. check the provided google clientid:
    $client_id = isset($_GET['client_id']) ? $_GET['client_id']:'';
    if($client_id != $google_clientid){
        error_msg('Request Error: Invalid Client ID has been provided!');
    }
    //2. Check response type, should always be token
    $response_type = isset($_GET['response_type']) ? $_GET['response_type']:'';
    if($response_type != "token"){
        error_msg('ReEquest Error: Unknown Response Type requested!');
    }
    //3. Check redirect_uri. It is always https://oauth-redirect.googleusercontent.com/r/YOUR_PROJECT_ID
    $redirect_uri = isset($_GET['redirect_uri']) ? $_GET['redirect_uri']:'';
    if($redirect_uri != ('https://oauth-redirect.googleusercontent.com/r/' . $google_projectid)){
        error_msg('Request Error: Invalid Redirect-Request!');
    }
    //4. check if a state is defined
    $state = isset($_GET['state']) ? $_GET['state']:'';
    if($state == ''){
        error_msg('Request Error: No State given!');
    }

    if(isset($_POST['email']) && isset($_POST['accesspwd'])){
        //accesspwd and email are defined. That means, that the user already pressed the "Login" button
        
        $db = db_init();
        if($db['error']){
            error_msg('Internal Database Error!', true, 'Back to Login', $_SERVER['SCRIPT_NAME'] . "?client_id=$client_id&response_type=$response_type&redirect_uri=$redirect_uri&state=$state");
        }

        $result = db_checkEmailAndAccessPassword($db, $_POST['email'], $_POST['accesspwd']);
        if($result['error']){
            error_msg($result['msg'], true, 'Back to Login', $_SERVER['SCRIPT_NAME'] . "?client_id=$client_id&response_type=$response_type&redirect_uri=$redirect_uri&state=$state");
        }

        $update = db_markAccessPasswordAsUsed($db, $result['accesskey_id']);
        if($update['error']){
            error_msg('Internal Database Error!', true, 'Back to Login', $_SERVER['SCRIPT_NAME'] . "?client_id=$client_id&response_type=$response_type&redirect_uri=$redirect_uri&state=$state");
        }

        header("Location: $redirect_uri#access_token=" . $result['google_key'] . "&token_type=bearer&state=$state");

        /*if($_POST['accesskey'] === $accesskey){
            $_SESSION['logintime'] = time();

            $sessionid = session_id();
            header("Location: $redirect_uri#access_token=$sessionid&token_type=bearer&state=$state");
        }else{
            error_msg('Access Key is wrong!', true, 'Back to Login', $currentpath . "?client_id=$client_id&response_type=$response_type&redirect_uri=$redirect_uri&state=$state");
        }*/
    }

?>

<div class="container">
    <div class="row">
        <form class="col s12" method="POST" action="#">
            <div class="card blue darken-1">
                <div class="card-content white-text">
                    <span class="card-title">Login</span>
                    <div class="row">
                        <div class="input-field col s12">
                            <input id="email" name="email" placeholder="Email" type="email" class="validate">
                            <label class="white-text" for="email">Email</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <input id="accesspwd" name="accesspwd" placeholder="Access Password" type="text" class="validate tooltipped" data-tooltip="This is not your account's password!<br>You can create a temporary password in your account dashboard">
                            <label class="white-text" for="accesspwd">Access Password</label>
                        </div>
                    </div>
                </div>
                <div class="card-action">
                    <div class="row">
                        <button class="btn waves-effect waves-light right" type="submit">Authenticate <i class="material-icons right">vpn_key</i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.tooltipped');
    var instances = M.Tooltip.init(elems);
});
</script>

<?php
    footer();
?>



<?php
/***
 * UTILITY-FUNCTIONS
 */
?>

<?php 
function footer(){
    echo <<<'FOOT'

        <footer class="page-footer blue darken-2">
            <div class="footer-copyright blue darken-1">
                <div class="container">
                    &copy; 2018 Peter Kappelt | Kappelt gBridge
                </div>
            </div>
        </footer>
    </body>
</html>
FOOT;
}
?>

<?php
    function error_msg($error, $button = false, $button_text = "", $button_link = ""){
        ?>
<div class="container">
    <div class="row">
        <form class="col s12">
            <div class="card red">
                <div class="card-content white-text">
                    <span class="card-title">Error</span>
                    <p><?php echo($error); ?></p>
                </div>

                <?php if($button){ ?>
                <div class="card-action">
                    <div class="row">
                        <a class="btn waves-effect waves-light right" href="<?php echo $button_link ?>"><?php echo $button_text ?></a>
                    </div>
                </div>
                <?php } ?>

            </div>
        </form>
    </div>
</div>
        <?php

        footer();
        exit();
    }
?>