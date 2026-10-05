<?php 
    require_once($_SERVER["DOCUMENT_ROOT"].'/include/_globals.php');
    require_once($_SERVER["DOCUMENT_ROOT"].'/include/queryhandler.php');
    require_once($_SERVER["DOCUMENT_ROOT"].'/include/error_handler.php'); 
    if (extension_loaded('redis')) {
        ini_set('session.save_handler', 'redis');
    } elseif (extension_loaded('memcache')) {
        ini_set('session.save_handler', 'memcache');
    } else {
        ini_set('session.save_handler', 'files');
    }
    ini_set('session.lazy_write','Off');
    if (extension_loaded('memcached')) {
        ini_set('memcached.sess_locking','Off');
    }
    ini_set('session.save_path',SESSION_SAVE_PATH);
    ini_set('session.gc_probability', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');

    $cc_necessary_consent = false;
    $cc_preferences_consent = false;
    $cc_login_message_text = '';
    $cc_csrf_token = '';
    if (isset($_COOKIE['cookieConsent'])) {
        $cc_cookie_value = stripslashes($_COOKIE['cookieConsent']);
        $cc_data = json_decode($cc_cookie_value, true);
        if (is_array($cc_data) && isset($cc_data['categories'])) {
            if (in_array('necessary', $cc_data['categories'])) {
                $cc_necessary_consent = true;
            }
            if (in_array('preferences', $cc_data['categories'])) {
                $cc_preferences_consent = true;
            }
        }
    }

    if ($cc_necessary_consent) {
        session_set_cookie_params(7200,'/');
        if (session_status() === PHP_SESSION_NONE || session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $cc_csrf_token = $_SESSION['csrf_token'];
    } else {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    $domain = preg_replace('/:\d+$/', '', $domain);
    $db = new queryhandler();
    $branding = $db->brand_get_by_domain($domain);
    $brand = (($branding[0]['branded'] ?? 0) == 1) ? ($branding[0]['brand'] ?? null) : (stripos($domain, 'sectarra') !== false ? 'sectarra' : null);

    if($brand) {
        setcookie('brand',$brand);
        $_SESSION['brand'] = $brand;
        $_SESSION[$brand] = true;
    } else {
        if(isset($_SESSION['brand'])){
            unset($_SESSION['brand'], $_SESSION[$brand]);
            unset($_COOKIE['brand']);
            setcookie('brand', '', -1, '/');
        }
    }

    if(isset($_POST['login'])) {
        $db = new queryhandler();
        $email = filter_input(INPUT_POST,'email');
        $token = filter_input(INPUT_POST,'token');
        $user = $db->login_by_token($email, $token);
        $db = new queryhandler();
        $settings = $db->switch_settings_get($user[0]['accountid']);
        $db = new queryhandler();
        $instance = $db->switch_instance_get($user[0]['accountid'],$user[0]['switch_instance_id']);
        if ($instance[0]['branded'] == 1 && !empty($instance[0]['brand_url'])) {
            $brand = $_SESSION['brand'] ?? '';
            if($brand != $instance[0]['brand']) {
                session_unset();
                session_destroy();
                header('Location: https://'.$instance[0]['brand_url'].'/login');
                exit();
            }
        } else {
            if (!empty($_SESSION['brand']) && $_SESSION['brand'] != 'sectarra') {
                session_unset();
                session_destroy();
                header('Location: https://clouddev2.lockt.com/login');
                exit();
            }
        }
        
        // session_set_cookie_params(7200,"/");
        // session_start();

        if(count($user) != 0) {
            session_regenerate_id(true);
            if($user[0]['2fa_required'] == 1) {
                $_SESSION['token'] = $token;
                $_SESSION['email'] = $email;
                header('Location: /login_2fa'); 
                exit();
            } else {
                $_SESSION['token'] = $token;
                $_SESSION['user_id'] = $user[0]['switch_user_id'];
                $_SESSION['user'] = $user;
                $_SESSION['token'] = $token;
                $_SESSION['accountid'] = $user[0]['accountid'];
                $_SESSION['display_name'] = $instance[0]['display_name'];
                $_SESSION['company_name'] = $user[0]['company_name'];
                $_SESSION['site_name'] = $user[0]['site_name'];
                $_SESSION['date_created'] = $user[0]['date_created'];
                $_SESSION['user_name'] = $user[0]['user_name'];
                $_SESSION['user_email'] = $user[0]['user_email'];
                $_SESSION['enable_access_restrictions'] = $instance[0]['enable_access_restrictions'];

                if($instance[0]['enable_access_restrictions'] != 0) {
                    $db = new queryhandler();
                    $restrictions = $db->user_admin_restrictions_list($user[0]['accountid'], $user[0]['switch_user_id']);
                    $_SESSION['top_level'] = $restrictions[0]['top_level'] ?? 0;
                } else {
                    $restrictions = [];
                }
                
                $db = new queryhandler();
                $user_full = $db->users_get($user[0]['accountid'], $user[0]['switch_instance_id'], $user[0]['switch_user_id']);
                $_SESSION['access_restrictions'] = $restrictions;
                $_SESSION['access_has_restrictions'] = $user_full[0]['access_restrictions'];
                $_SESSION['access_top_level'] = $user_full[0]['access_top_level'];
                $_SESSION['access_data_management'] = $user_full[0]['access_data_management'];
                $_SESSION['integrator'] = $user_full[0]['integrator'];
                $_SESSION['user_type'] = $user_full[0]['user_type'];
                $_SESSION['require_contact_update'] = $user_full[0]['require_contact_update'];
                $_SESSION['api_manager'] = $user_full[0]['api_manager'];
                
                if(isset($_COOKIE['brand']) && $_COOKIE['brand'] != '') {
                    $_SESSION['brand'] = $_COOKIE['brand'];
                }

                $db = new queryhandler();
                $accountid = $user[0]['accountid'];
                $switch_instance_id = $user[0]['switch_instance_id'];
                $objects = $db->object_count($accountid, $switch_instance_id);
                $_SESSION['capabilities'] = [
                    'evo'=>$objects[0]['evo'],
                    'axis'=>$objects[0]['axis'],
                    'pdq'=>$objects[0]['pdq'],
                    'nexkey'=>$objects[0]['nexkey'],
                    'nox'=>$objects[0]['nox'],
                    'wiq'=>$objects[0]['wiq'],
                    'azure'=>$objects[0]['azure'],
                    'haven'=>$objects[0]['haven']
                ];

                $file = $_SERVER["DOCUMENT_ROOT"].'/language/'.'available.json';
                $data = file_get_contents($file);
                $languages = json_decode($data, true);

                $_SESSION['languages'] = $languages;
                
                $db = new queryhandler();
                $alertresult = $db->login_alerts_check($_SESSION["accountid"],$_SESSION["user_email"]); 

                if (count($alertresult) != 0) {
                   header('Location: /alerts');      
                   exit();
                }
                
                header('Location: /index'); 
                exit();  
            }
        }

        if(count($user) == 0) {
            session_unset();
            session_destroy();
            header('Location: /login'); 
            exit();
        }
    }
    
    if(isset($_POST['login_mfa'])) {
        
        $db = new queryhandler();
        $email = filter_input(INPUT_POST,'mfa_email');
        $token = filter_input(INPUT_POST,'mfa_token');
        $auth_code = filter_input(INPUT_POST,'mfa_auth_code');
        $user = $db->login_by_token_and_code($email, $token, $auth_code);
        $db = new queryhandler();
        $settings = $db->switch_settings_get($user[0]['accountid']);
        $db = new queryhandler();
        $instance = $db->switch_instance_get($user[0]['accountid'],$user[0]['switch_instance_id']);
        
        if ($instance[0]['branded'] == 1 && !empty($instance[0]['brand_url'])) {
            $brand = $_SESSION['brand'] ?? '';
            if($brand != $instance[0]['brand']) {
                session_unset();
                session_destroy();
                header('Location: https://'.$instance[0]['brand_url'].'/login');
                exit();
            }
        } else {
            if (!empty($_SESSION['brand']) && $_SESSION['brand'] != 'sectarra') {
                session_unset();
                session_destroy();
                header('Location: https://clouddev2.lockt.com/login');
                exit();
            }
        }
        // session_set_cookie_params(7200,"/");
        // session_start();
                
        if(isset($_COOKIE['brand']) && $_COOKIE['brand'] != '') {
            $_SESSION['brand'] = filter_input(INPUT_GET,'brand');
        }
        
        if(count($user) != 0) {
            session_regenerate_id(true);
            $_SESSION['token'] = $token;
            $_SESSION['user_id'] = $user[0]['switch_user_id'];
            $_SESSION['user'] = $user;
            $_SESSION['token'] = $token;
            $_SESSION['accountid'] = $user[0]['accountid'];
            $_SESSION['company_name'] = $user[0]['company_name'];
            $_SESSION['site_name'] = $user[0]['site_name'];
            $_SESSION['date_created'] = $user[0]['date_created'];
            $_SESSION['user_name'] = $user[0]['user_name'];
            $_SESSION['user_email'] = $user[0]['user_email'];
            $_SESSION['enable_access_restrictions'] = $instance[0]['enable_access_restrictions'];
            if($instance[0]['enable_access_restrictions'] != 0) {
                $db = new queryhandler();
                $restrictions = $db->user_admin_restrictions_list($user[0]['accountid'], $user[0]['switch_user_id']);
                $_SESSION['top_level'] = $restrictions[0]['top_level'] ?? 0;
            } else {
                $restrictions = [];
            }
            if(isset($_COOKIE['brand']) && $_COOKIE['brand'] != '') {
                $_SESSION['brand'] = $_COOKIE['brand'];
            }    
            
            $db = new queryhandler();
            $user_full = $db->users_get($user[0]['accountid'], $user[0]['switch_instance_id'], $user[0]['switch_user_id']);
            $_SESSION['access_restrictions'] = $restrictions;
            $_SESSION['access_has_restrictions'] = $user_full[0]['access_restrictions'];
            $_SESSION['access_top_level'] = $user_full[0]['access_top_level'];
            $_SESSION['access_data_management'] = $user_full[0]['access_data_management'];
            $_SESSION['integrator'] = $user_full[0]['integrator'];
            $_SESSION['user_type'] = $user_full[0]['user_type'];
            $_SESSION['require_contact_update'] = $user_full[0]['require_contact_update'];
            $_SESSION['api_manager'] = $user_full[0]['api_manager'];
            
            $db = new queryhandler();
            $accountid = $user[0]['accountid'];
            $switch_instance_id = $user[0]['switch_instance_id'];
            $objects = $db->object_count($accountid, $switch_instance_id);
            $_SESSION['capabilities'] = [
                'evo'=>$objects[0]['evo'],
                'axis'=>$objects[0]['axis'],
                'pdq'=>$objects[0]['pdq'],
                'nexkey'=>$objects[0]['nexkey'],
                'nox'=>$objects[0]['nox'],
                'wiq'=>$objects[0]['wiq'],
                'azure'=>$objects[0]['azure'],
                'haven'=>$objects[0]['haven']
            ];
            
            $file = $_SERVER["DOCUMENT_ROOT"].'/language/'.'available.json';
            $data = file_get_contents($file);
            $languages = json_decode($data, true);

            $_SESSION['languages'] = $languages;

            $db = new queryhandler();
            $alertresult = $db->login_alerts_check($_SESSION["accountid"],$_SESSION["user_email"]); 

            if (count($alertresult) != 0) {
               header('Location: /alerts');      
               exit();
            }
            
            header('Location: /index'); 
            exit();  

        } else {
            session_unset();
            session_destroy();
            header('Location: /login'); 
            exit();
        }
    }
    
    
    if(isset($_POST['login_portal'])) {
        $db = new queryhandler();
        $token = filter_input(INPUT_POST, 'portal_token');
        $portal_role = filter_input(INPUT_POST, 'portal_role');
        $user = $db->portal_login_by_token($token);

        $role_valid = isset($user[0]) && (
            ($portal_role === 'xc_user' && $user[0]['is_xc_user']) ||
            ($portal_role === 'reseller' && $user[0]['is_customer'])
        );

        if(count($user) != 0 && $role_valid) {
            session_regenerate_id(true);
            if(!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            $_SESSION['portal_token'] = $token;
            $_SESSION['portal_role'] = $portal_role;
            $_SESSION['user_id'] = $user[0]['user_id'];
            $_SESSION['accountid'] = $user[0]['accountid'];
            $_SESSION['user_name'] = $user[0]['name'];
            $_SESSION['user_email'] = $user[0]['email'];
            if($portal_role == 'reseller') {
                $db = new queryhandler();
                $group = $db->customer_info_get_by_user_id($user[0]['accountid'], $user[0]['user_id']);
                $_SESSION['display_name'] = !empty($group) ? ($group[0]['customer_group'] ?? 'Reseller Dashboard') : 'Reseller Dashboard';
            } else {
                $_SESSION['display_name'] = 'Sectarra Dashboard';
            }
            $_SESSION['my_referral_code'] = $user[0]['my_referral_code'];
            $_SESSION['dual_identity'] = $user[0]['has_instance_account'] ?? false;
            $_SESSION['enable_access_restrictions'] = 0;
            $_SESSION['access_restrictions'] = 0;
            $_SESSION['require_contact_update'] = 0;
            
            $file = $_SERVER["DOCUMENT_ROOT"].'/language/'.'available.json';
            $data = file_get_contents($file);
            $languages = json_decode($data, true);

            $_SESSION['languages'] = $languages;

            $db = new queryhandler();
            $alertresult = $db->login_alerts_check($_SESSION["accountid"],$_SESSION["user_email"]); 

            if (count($alertresult) != 0) {
               header('Location: /alerts');      
               exit();
            }
            
            header('Location: /dashboard');
            exit();
        }
        session_unset();
        session_destroy();
        header('Location: /login');
        exit();
    }

    $file = $_SERVER["DOCUMENT_ROOT"].'/language/'.'available.json';
    $data = file_get_contents($file);
    $languages = json_decode($data, true);
    
    $db = new queryhandler();
    $maintenance =  $db->maintenance_window_get_current();
    $cardinal_ui = stripos($domain, 'cardinal') !== false || in_array($domain, ['localhost', '127.0.0.1'], true);
?>    

<!DOCTYPE html>
<html lang="en">
    
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php if($cardinal_ui): ?>
    <title>Lockt Cardinal</title>
    <?php elseif(!empty($_SESSION['sectarra'])): ?>
    <title>Sectarra</title>
    <?php else: ?>
    <title>Lockt CloudAccess</title>
    <?php endif; ?>
    <?php require_once($_SERVER["DOCUMENT_ROOT"].($cardinal_ui ? '/include/_login_css_cardinal.php' : '/include/_login_css.php')); ?>

</head>

<?php if($cardinal_ui): ?>
<body class="cardinal-body">
    <?php require_once($_SERVER["DOCUMENT_ROOT"].'/include/_login_cardinal.php'); ?>
<?php else: ?>
<body class="animsition">
    <div class="wrapper fadeInDown">
        <div id="formContent">
            <!-- Tabs Titles -->
            <div>&nbsp;</div>

            <!-- Icon -->
            <div class="fadeIn first">
                <?php require_once($_SERVER["DOCUMENT_ROOT"].'/include/_loginlogo.php'); ?>
            </div>

            <div>&nbsp;</div>
            
            <?php if(count($maintenance) > 0): ?>
            <div class="form-group">
                <div class="lockt_maintenance" style="text-align: left;">
                    <?php if(!$maintenance[0]['notification_only']): ?>
                    <i class="fas fa-exclamation-circle"></i>
                    <?php else: ?>
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php endif; ?>
                    <strong> <?php echo $maintenance[0]['title']; ?></strong><br/>
                    <?php echo $maintenance[0]['notice']; ?><br />
                    <?php if(!$maintenance[0]['notification_only']): ?>
                    From <strong><?php echo $maintenance[0]['start_date']; ?></strong> to <strong><?php echo $maintenance[0]['end_date']; ?></strong>
                    <?php else: ?>
                    Posted: <strong><?php echo $maintenance[0]['start_date']; ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="post" id="login_form" action="login" class="<?php echo !$cc_necessary_consent ? 'cc-form-disabled' : ''; ?>">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-6">
                            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                            <div class="lockt-login-message" id="login_message" ><span id="login_message_text"></span></div>
                            <div class="form-group mb-3">
                                <input type="text" id="email" class="fadeIn second" name="email" placeholder="email" caption_key="login.email" aria-label="Email address" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group mb-3">
                                <input type="password" id="password" class="fadeIn third" name="password" placeholder="password" caption_key="login.password" aria-label="Password" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group mb-3">
                                <select name="ui_language" id="ui_language" class="fadeIn fifth text-center" caption_key="login.language" aria-label="Select language" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                                    <?php foreach($languages as $l): ?>
                                    <option value="<?php echo $l['key'] ?>"><?php echo $l['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <button type="button" class="fadeIn fourth" caption_key="login.login" id="ac_account_check" aria-label="Log in" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>Log In</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <form method="post" id="login_account_form" action="login" class="form-inline <?php echo !$cc_necessary_consent ? 'cc-form-disabled' : ''; ?>" style="display: none;">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-6">
                            <input type="hidden" name="csrf_token" id="csrf_token_login" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                            <div class="lockt-mfa-message" id="login_account_message" ><span id='login_account_text' caption_key="login.account.prompt"></span></div>
                            <br />
                            <div class="lockt_multi_login_container">
                                <div id="m_accounts" class="lockt_multi_login"></div>
                            </div>
                            <div class="form-group">
                                <button type="button" id='ac_login' caption_key="login.continue" aria-label="Continue" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>Continue</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <form method="post" id="login_mfa_form" action="login" class="form-inline <?php echo !$cc_necessary_consent ? 'cc-form-disabled' : ''; ?>" style="display: none;">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-6">
                            <input type="hidden" name="csrf_token" id="csrf_token_mfa" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                            <div class="lockt-mfa-message" id="login_mfa_message" ><span id='mfa_text' caption_key="login.2fa.prompt"></span></div>
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <input type="text" id="2fa_code1" required="" data-index="1" class="lockt-2fabox" name="2fa_code1" maxlength="1" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit 1" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                                <input type="text" id="2fa_code2" required="" data-index="2" class="lockt-2fabox" name="2fa_code2" maxlength="1" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit 2" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                                <input type="text" id="2fa_code3" required="" data-index="3" class="lockt-2fabox" name="2fa_code3" maxlength="1" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit 3" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                                <input type="text" id="2fa_code4" required="" data-index="4" class="lockt-2fabox" name="2fa_code4" maxlength="1" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit 4" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                                <input type="text" id="2fa_code5" required="" data-index="5" class="lockt-2fabox" name="2fa_code5" maxlength="1" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit 5" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                                <input type="text" id="2fa_code6" required="" data-index="6" class="lockt-2fabox" name="2fa_code6" maxlength="1" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit 6" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <button type="button" id='mfa_login' caption_key="login.login" aria-label="Log in" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>Log In</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <form method="post" id="reset_form" action="login" style="display: none;" class="<?php echo !$cc_necessary_consent ? 'cc-form-disabled' : ''; ?>">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-6">
                            <input type="hidden" name="csrf_token" id="csrf_token_reset" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                            <div class="lockt-reset-message mb-3" id="reset_message"><span caption_key="login.password_reset"></span></div>
                            <div class="lockt-reset-message mb-3" id="reset_confirm"><span caption_key="login.password_reset_confirm"></span></div>
                            <div class="form-group mb-3">
                                <input type="text" id="reset_email" class="form-control" name="reset_email" placeholder="email" caption_key="login.email" aria-label="Email address for password reset" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <button type="button" class="" caption_key="login.password_reset_send" id="reset_send" aria-label="Send reset link" <?php echo !$cc_necessary_consent ? 'disabled' : ''; ?>>Send Reset Link</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Remind Password -->
            <div id="loginFooter">
              <a class="underlineHover" id='forgot_pass' caption_key="login.forgot_password" role="button">Forgot Password?</a>
            </div>
            
            <!-- Remind Password -->
            <div id="mfaFooter" style='display: none;'>
              <a class="underlineHover" href="#" caption_key="login.2fa.new_code" role="button">Resend Code?</a>
            </div>
            <?php if(empty($_SESSION['sectarra'])): ?>
            <!-- <div id="loginFooter">
              <a class="underlineHover"  href="/sso" id='' ><span caption_key="login.sso">Continue with SSO </span> <i class="fas fa-arrow-right"></i></a> 
            </div> -->
            <?php else: ?>
                <div id="loginFooter">
              <a class="underlineHover"  href="/register" id='' ><span caption_key="login.register">Register Account </span> <i class="fas fa-arrow-right"></i></a> 
            </div>
            <?php endif; ?>
            
            <div class="copyright">
                <?php if(empty($_SESSION['sectarra'])): ?>
                <span caption_key="footer.portal_runs">This portal runs on the</span> <a href="https://www.lockt.com" target="_blank" aria-label="Lockt Application Platform (opens in a new tab)"> Lockt Application Platform</a>.
                <br />
                <?php endif; ?>
            <a href="https://www.lockt.com/terms" target="_blank" caption_key="footer.terms_of_use" aria-label="Terms of Use (opens in a new tab)">Terms of Use</a> | <a href="https://www.lockt.com/privacy" target="_blank" caption_key="footer.privacy_policy" aria-label="Privacy Policy (opens in a new tab)">Privacy Policy</a> | <a href="<?php echo empty($_SESSION['sectarra']) ? 'https://developer.lockt.com/' : 'https://developer.sectarra.com'; ?>" caption_key="footer.developer_tools" target="_blank" aria-label="Developer Tools (opens in a new tab)">Developer Tools</a> | <a href="#" role="button" data-cc="show-preferencesModal" caption_key="footer.cookie_settings" aria-label="Cookie Settings">Cookie Settings</a> | <a href="https://www.lockt.com/syshealth" target="_blank" caption_key="footer.system_status" aria-label="System Status (opens in a new tab)">System Status</a>
            <br>
            <span caption_key="footer.copyright">Copyright</span> &copy; 2018 - <?php echo date("Y"); ?> Lockt, LLC
            </div>
        </div>
    </div>
    
    <?php endif; ?>

    <?php require_once('./include/_js_login.php');  ?>

    </body>
</html>