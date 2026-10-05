<?php
/* Lockt Cardinal sign-in markup. Element ids and classes used by lockt.js / lockt_login.js are unchanged. */
$cc_disabled = '';
$cc_form_class = '';
?>
<main class="cardinal">
    <section class="cardinal-hero">
        <svg class="cardinal-hero-mark" viewBox="0 0 48 48" width="760" height="760" aria-hidden="true">
            <path d="M6 42 L22 6 L29 19 L42 16 L34 28 L42 42 Z" fill="none" stroke="#FFFFFF" stroke-width="1.2" stroke-linejoin="round"></path>
        </svg>

        <div class="cardinal-brand">
            <img class="cardinal-brand-lockt" src="/assets/images/cardinal/Lockt.png" alt="Lockt" width="136" height="42">
            <span class="cardinal-brand-divider" aria-hidden="true"></span>
            <span class="cardinal-brand-product">
                <svg viewBox="0 0 48 48" width="34" height="34" aria-hidden="true">
                    <path d="M6 42 L22 6 L29 19 L42 16 L34 28 L42 42 Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linejoin="round"></path>
                    <circle cx="27" cy="27" r="3" fill="#C81D1D"></circle>
                </svg>
                <span class="cardinal-brand-name">CARDINAL</span>
            </span>
        </div>

        <div class="cardinal-hero-copy">
            <div class="cardinal-eyebrow">LOCKT RED</div>
            <h1 class="cardinal-headline">Every door.<br>One platform.</h1>
            <p class="cardinal-tagline">Cloud access control built for Dormakaba hardware.</p>
        </div>

        <div class="cardinal-hero-footer">
            <span>cardinal.lockt.com</span>
            <span>&copy; <?php echo date("Y"); ?> Lockt</span>
        </div>
    </section>

    <section class="cardinal-panel">
        <div class="cardinal-panel-inner">
            <div class="cardinal-intro">
                <h2 class="cardinal-title">Sign in</h2>
                <p class="cardinal-subtitle">Use your Cardinal account.</p>
            </div>

            <?php if(count($maintenance) > 0): ?>
            <div class="lockt_maintenance">
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
            <?php endif; ?>

            <!-- Login Form -->
            <form method="post" id="login_form" action="login" class="cardinal-form <?php echo $cc_form_class; ?>">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                <div class="lockt-login-message" id="login_message" role="alert"><span id="login_message_text"></span></div>
                <div class="cardinal-field">
                    <label for="email" caption_key="login.email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@company.com" autocomplete="username" aria-label="Email address" <?php echo $cc_disabled; ?>>
                </div>
                <div class="cardinal-field">
                    <div class="cardinal-field-row">
                        <label for="password" caption_key="login.password">Password</label>
                        <button type="button" class="cardinal-link" id="forgot_pass" data-hover="Password reset is temporarily unavailable">Forgot?</button>
                    </div>
                    <input type="password" id="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="current-password" aria-label="Password" <?php echo $cc_disabled; ?>>
                </div>
                <div class="cardinal-check">
                    <input type="checkbox" id="remember" <?php echo $cc_disabled; ?>>
                    <label for="remember">Keep me signed in</label>
                </div>
                <button type="button" class="cardinal-btn cardinal-btn-primary" id="ac_account_check" data-hover="Sign in securely" caption_key="login.login" aria-label="Sign in" <?php echo $cc_disabled; ?>>Sign in</button>
            </form>

            <form method="post" id="login_account_form" action="login" class="cardinal-form <?php echo $cc_form_class; ?>" style="display: none;">
                <input type="hidden" name="csrf_token" id="csrf_token_login" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                <div class="cardinal-note" id="login_account_message"><span id="login_account_text" caption_key="login.account.prompt"></span></div>
                <div class="cardinal-accounts">
                    <div id="m_accounts" class="lockt_multi_login"></div>
                </div>
                <button type="button" class="cardinal-btn cardinal-btn-primary" id="ac_login" data-hover="Continue" caption_key="login.continue" aria-label="Continue" <?php echo $cc_disabled; ?>>Continue</button>
            </form>

            <form method="post" id="login_mfa_form" action="login" class="cardinal-form <?php echo $cc_form_class; ?>" style="display: none;">
                <input type="hidden" name="csrf_token" id="csrf_token_mfa" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                <div class="cardinal-note" id="login_mfa_message"><span id="mfa_text" caption_key="login.2fa.prompt"></span></div>
                <div class="cardinal-code">
                    <?php for($i = 1; $i <= 6; $i++): ?>
                    <input type="text" id="2fa_code<?php echo $i; ?>" required="" data-index="<?php echo $i; ?>" class="lockt-2fabox" name="2fa_code<?php echo $i; ?>" maxlength="1" inputmode="numeric" autocomplete="one-time-code" oninput="this.value=this.value.replace(/[^0-9]/g,'');" aria-label="2FA code digit <?php echo $i; ?>" <?php echo $cc_disabled; ?>>
                    <?php endfor; ?>
                </div>
                <button type="button" class="cardinal-btn cardinal-btn-primary" id="mfa_login" data-hover="Verify and sign in" caption_key="login.login" aria-label="Sign in" <?php echo $cc_disabled; ?>>Sign in</button>
            </form>

            <form method="post" id="reset_form" action="login" class="cardinal-form <?php echo $cc_form_class; ?>" style="display: none;">
                <input type="hidden" name="csrf_token" id="csrf_token_reset" value="<?php echo htmlspecialchars($cc_csrf_token); ?>" data-preserve readonly />
                <div class="cardinal-note lockt-reset-message" id="reset_message"><span caption_key="login.password_reset"></span></div>
                <div class="cardinal-note lockt-reset-message" id="reset_confirm"><span caption_key="login.password_reset_confirm"></span></div>
                <div class="cardinal-field">
                    <label for="reset_email" caption_key="login.email">Email</label>
                    <input type="email" id="reset_email" name="reset_email" placeholder="you@company.com" aria-label="Email address for password reset" <?php echo $cc_disabled; ?>>
                </div>
                <button type="button" class="cardinal-btn cardinal-btn-primary" id="reset_send" data-hover="Send reset link" caption_key="login.password_reset_send" aria-label="Send reset link" <?php echo $cc_disabled; ?>>Send Reset Link</button>
            </form>

            <div id="mfaFooter" class="cardinal-aside" style="display: none;">
                <a class="cardinal-text-link" href="#" caption_key="login.2fa.new_code" role="button">Resend Code</a>
            </div>

            <div id="loginFooter" class="cardinal-alt">
                <div class="cardinal-or" aria-hidden="true"><span>OR</span></div>
                <a class="cardinal-btn cardinal-btn-outline" href="/sso" data-hover="Continue with single sign-on"><span caption_key="login.sso">Continue with SSO</span></a>
                <p class="cardinal-help">Need access? Contact your site administrator.</p>
            </div>

            <div class="cardinal-powered">
                <span class="cardinal-powered-label">POWERED BY</span>
                <img class="cardinal-powered-logo" src="/assets/images/cardinal/dormakaba.png" alt="dormakaba" width="2170" height="725">
            </div>
        </div>
    </section>
</main>
