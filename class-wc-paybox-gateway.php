<?php
/**
 * Plugin Name: WooCommerce Paybox Payment Gateway
 * Plugin URI: http://www.openboutique.fr/
 * Description: Gateway e-commerce pour Paybox.
 * Version: 0.3.4
 * Author: SWO (Open Boutique)
 * Author URI: http://www.openboutique.fr/
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package WordPress
 * @author SWO (Open Boutique)
 * @since 0.1.0
 */
add_action('plugins_loaded', 'woocommerce_paybox_init', 0);

function woocommerce_paybox_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    };

    DEFINE('PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));

    /*
     * Paybox Commerce Gateway Class
     */

    class WC_Paybox extends WC_Payment_Gateway {

        function __construct() {
            global $woocommerce;
            $this->id = 'paybox';
            $this->icon = PLUGIN_DIR . '/images/paybox.png';
            $this->has_fields = false;
            $this->method_title = __('PayBox', 'woocommerce');
            // Load the form fields
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();
            // Get setting values
            foreach ($this->settings as $key => $val)
                $this->$key = $val;
            // Logs
            if ($woocommerce->debug == 'yes')
                $this->log = $woocommerce->logger();

            // Ajout des Hooks
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            //add_action('woocommerce_thankyou_paybox', array(&$this, 'thankyou_page'));
        }

        /**
         * Retour Paybox
         *
         * @access public
         * @param array $posted
         * @return void

          function thankyou_page($posted) {
          global $woocommerce;
          //error_log('thankyou_page');
          //Pour le moment on ne fait rien
          }
         *
         */
        /*
         * Admin tools.
         */
        public function admin_options() {
            ?>
            <h3><?php _e('OpenBoutique PayBox Gateway', 'woocommerce'); ?></h3>
            <div id="wc-ob-pbx-admin">
                <div id="ob-paybox_baseline">PayBox Gateway is an <a href="http://www.openboutique.fr/?wcpbx=0.3.3" target="_blank">OpenBoutique</a> technology</div>
                <div>
                    <?php
                    wp_enqueue_style('custom_openboutique_paybox_css', PLUGIN_DIR . '/css/style.css', false, '0.3.4');
                    wp_enqueue_script('custom_openboutique_paybox_js', PLUGIN_DIR . '/js/script.js', false, '0.3.4');
                    $install_url = '';
                    if (!get_option('woocommerce_pbx_order_received_page_id')) {
                        $install_url .= '&install_pbx_received_page=true';
                    }
                    if (!get_option('woocommerce_pbx_order_refused_page_id')) {
                        $install_url .= '&install_pbx_refused_page=true';
                    }
                    if (!get_option('woocommerce_pbx_order_canceled_page_id')) {
                        $install_url .= '&install_pbx_canceled_page=true';
                    }
                    if ($install_url != '' && empty($_GET['install_pbx_received_page']) && empty($_GET['install_pbx_refused_page']) && empty($_GET['install_pbx_canceled_page'])) {
                        ?>
                        <p><?php _e('We have detected that Paybox return pages are not currently installed on your system<br/>Press the install button to prevent 404 from users whom transaction would have been received, canceled or refused.', 'woocommerce') ?></p>
                        <p>
                            <a class="button" target="_self" href="/wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Paybox<?php echo($install_url); ?>"><?php _e('Install return pages', 'woocommerce') ?></a>
                        </p>
                    <?php } else { ?>
                        <p><?php _e('Paybox return pages are installed : ', 'woocommerce') ?>
                            <a target="_self" href="/wp-admin/post.php?post=<?php echo(get_option('woocommerce_pbx_order_received_page_id')); ?>&action=edit">received</a>&nbsp;|&nbsp;
                            <a target="_self" href="/wp-admin/post.php?post=<?php echo(get_option('woocommerce_pbx_order_canceled_page_id')); ?>&action=edit">canceled</a>&nbsp;|&nbsp;
                            <a target="_self" href="/wp-admin/post.php?post=<?php echo(get_option('woocommerce_pbx_order_refused_page_id')); ?>&action=edit">refused</a>
                        </p>
                    <?php } ?>
                </div>
                <div><p><a class="button-primary" id="ob-paybox_show_help" href="#">Need help ?</a></p></div>
                <div id="ob-paybox_help_div">
                    <p>Press "send report" button and fill your email in order to post your <b>Paybox Gateway parameters</b> to OpenBoutique support team<br/>
                    Your email : <input type="text" name="email" value="your email" /><br/>
                    Your message :<br/><textarea name="help_text" rows="4" cols="80"></textarea>
                    			<input type="hidden" name="website" value="<?php echo($_SERVER['SERVER_NAME'])?>" />
                                        <input type="hidden" name="WCPBX_version" value="0.3.4" />
                    			<input type="hidden" name="woocommerce_pbx_order_received_page_id" value="<?php echo(get_option('woocommerce_pbx_order_received_page_id')) ?>" />
                    			<input type="hidden" name="woocommerce_pbx_order_refused_page_id" value="<?php echo(get_option('woocommerce_pbx_order_refused_page_id')) ?>" />
                    			<input type="hidden" name="woocommerce_pbx_order_canceled_page_id" value="<?php echo(get_option('woocommerce_pbx_order_canceled_page_id')) ?>" />
                        <a class="button" id="ob-paybox_send_report" href="#">Send report</a>
                    </p>
                    <iframe name="myOB_iframe" id="myOB_iframe" style="display: none"></iframe>
                </div>
            </div>

            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->
            
            <?php
            if (!empty($_GET['install_pbx_received_page'])) {
                // Page paiement refusé -> A venir short code pour interpretation du code retour
                $this->create_page(esc_sql(_x('order-pbx-received', 'page_slug', 'woocommerce')), 'woocommerce_pbx_order_received_page_id', __('Order PBX Received', 'woocommerce'), '[openboutique_thankyou]', woocommerce_get_page_id('checkout'));
            }
            if (!empty($_GET['install_pbx_refused_page'])) {
                // Page paiement refusé -> A venir short code pour interpretation du code retour
                $this->create_page(esc_sql(_x('order-pbx-refused', 'page_slug', 'woocommerce')), 'woocommerce_pbx_order_refused_page_id', __('Order PBX Refused', 'woocommerce'), 'Your order has been refused', woocommerce_get_page_id('checkout'));
            }
            if (!empty($_GET['install_pbx_canceled_page'])) {
                // Page paiement annulé par le client
                $this->create_page(esc_sql(_x('order-pbx-canceled', 'page_slug', 'woocommerce')), 'woocommerce_pbx_order_canceled_page_id', __('Order PBX Canceled', 'woocommerce'), 'Your order has been cancelled', woocommerce_get_page_id('checkout'));
            }
        }

        function create_page($slug, $option, $page_title = '', $page_content = '', $post_parent = 0) {
            global $wpdb;
            $option_value = get_option($option);
            if ($option_value > 0 && get_post($option_value))
                return;

            $page_found = $wpdb->get_var($wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s LIMIT 1;", $slug));
            if ($page_found) {
                if (!$option_value)
                    update_option($option, $page_found);
                return;
            }
            $page_data = array(
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'post_name' => $slug,
                'post_title' => $page_title,
                'post_content' => $page_content,
                'post_parent' => $post_parent,
                'comment_status' => 'closed'
            );
            $page_id = wp_insert_post($page_data);
            update_option($option, $page_id);
        }

        /*
         * Initialize Gateway Settings Form Fields.
         */

        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Paybox Payment', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => __('Paybox Payment', 'woocommerce')
                ),
                'description' => array(
                    'title' => __('Customer Message', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Let the customer know the payee and where they should be sending the Paybox to and that their order won\'t be shipping until you receive it.', 'woocommerce'),
                    'default' => __('Credit card payment by PayBox.', 'woocommerce')
                ),
                'paybox_site_id' => array(
                    'title' => __('Site ID Paybox', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter you ID Site provided by PayBox.', 'woocommerce'),
                    'default' => '1999888'
                ),
                'paybox_identifiant' => array(
                    'title' => __('Paybox ID', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter you Paybox ID provided by PayBox.', 'woocommerce'),
                    'default' => '2'
                ),
                'paybox_rang' => array(
                    'title' => __('Paybox Rank', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter Paybox Rank provided by PayBox.', 'woocommerce'),
                    'default' => '99'
                ),
                'paybox_wait_time' => array(
                    'title' => __('Paybox Checkout waiting time', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Time to wait before to redirect to Paybox gateway (in milliseconds).', 'woocommerce'),
                    'default' => '3000'
                ),
                'return_url' => array(
                    'title' => __('Paybox return URL', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter the autoreponse URL for PayBox.', 'woocommerce'),
                    'default' => '/paybox_autoresponse'
                ),
                'callback_success_url' => array(
                    'title' => __('Successful Return Link', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter callback link from PayBox when transaction succeed (where you need to put the [openboutique_thankyou] shortcode).', 'woocommerce'),
                    'default' => '/checkout/order-pbx-received/'
                ),
                'callback_refused_url' => array(
                    'title' => __('Failed Return Link', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter callback link from PayBox when transaction is refused by gateway.', 'woocommerce'),
                    'default' => '/checkout/order-pbx-refused/'
                ),
                'callback_cancel_url' => array(
                    'title' => __('Cancel Return Link', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter back link from PayBox when enduser cancel transaction.', 'woocommerce'),
                    'default' => '/checkout/order-pbx-canceled/'
                ),
                'paybox_url' => array(
                    'title' => __('Paybox URL', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter the posting URL for paybox Form <br/>For testing : https://preprod-tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi<br/>For production : https://tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi', 'woocommerce'),
                    'default' => 'https://preprod-tpeweb.paybox.com/cgi/MYpagepaiement.cgi'
                ),
                'prepost_message' => array(
                    'title' => __('Customer Message', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Message to the user before redirecting to PayBox.', 'woocommerce'),
                    'default' => __('You will be redirect to Paybox System payment gatway in a few seconds ... Please wait ...', 'woocommerce')
                ),
                'paybox_exe' => array(
                    'title' => __('Complete path to PayBox CGI', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Location for Paybox executable (http://www1.paybox.com/telechargement_focus.aspx?cat=3).', 'woocommerce'),
                    'default' => __('/the/path/to/paybox.cgi', 'woocommerce')
                )
            );
        }

        /**
         * Process the payment and return the result
         *
         * @access public
         * @param int $order_id
         * @return array
         */
        function process_payment($order_id) {
            //error_log('Call : process_payment');
            $order = new WC_Order($order_id);
            $paybox_form = $this->getParamPaybox($order);
            //error_log($paybox_form);
            $retour = '<p>' . $this->prepost_message . '</p>' . $paybox_form . "\r\n" . '
                <script>
                    function launchPaybox() {
                        document.PAYBOX.submit();
                    }
                    t=setTimeout("launchPaybox()",'.$this->paybox_wait_time.');
                </script>
                ';
            wp_die($retour);
        }

        function getParamPaybox(WC_Order $order) {
            $param = '';
            $param .= 'PBX_MODE=4'; //envoi en ligne de commande
            $param .= ' PBX_OUTPUT=B';

            $param .= ' PBX_SITE=' . $this->paybox_site_id;
            $param .= ' PBX_IDENTIFIANT=' . $this->paybox_identifiant;
            $param .= ' PBX_RANG=' . $this->paybox_rang;
            $param .= ' PBX_TOTAL=' . 100 * $order->get_total();
            $param .= ' PBX_CMD=' . $order->id;
            $param .= ' PBX_REPONDRE_A=http://' . str_replace('//', '/', $_SERVER['HTTP_HOST'] . '/' . $this->return_url);
            $param .= ' PBX_EFFECTUE=http://' . str_replace('//', '/', $_SERVER['HTTP_HOST'] . '/' . $this->callback_success_url);
            $param .= ' PBX_REFUSE=http://' . str_replace('//', '/', $_SERVER['HTTP_HOST'] . '/' . $this->callback_refused_url);
            $param .= ' PBX_ANNULE=http://' . str_replace('//', '/', $_SERVER['HTTP_HOST'] . '/' . $this->callback_cancel_url);
            $param .= ' PBX_DEVISE=978'; // Euro (à paramétriser)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $param .= ' PBX_RETOUR=order:R;erreur:E;carte:C;numauto:A;numtrans:S;numabo:B;montantbanque:M;sign:K';
            } else { //pour linux 
                $param .= ' PBX_RETOUR=order:R\\;erreur:E\\;carte:C\\;numauto:A\\;numtrans:S\\;numabo:B\\;montantbanque:M\\;sign:K';
            }
            $email_address = $order->billing_email;
            if (empty($email_address) || !is_email($email_address)) {
                $ids = $wpdb->get_result("SELECT wp_users.ID FROM wp_users WHERE (SELECT wp_usermeta.meta_value FROM wp_usermeta WHERE wp_usermeta.user_id = wp_users.ID AND wp_usermeta.meta_key = 'wp_capabilities') LIKE '%administrator%'");
                if ($ids) {
                    $current_user = get_user_by('id', $ids[0]);
                    $email_address = $current_user->user_mail;
                }
            }
            $param .= ' PBX_PORTEUR=' . $email_address; //. $order->customer_user;
            $exe = $this->paybox_exe;
            if (file_exists($exe)) {
                //error_log($exe . ' ' . $param);
                $retour = shell_exec($exe . ' ' . $param);
                if ($retour != '') {
                    $retour = str_replace('https://tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi', $this->paybox_url, $retour);
                    return $retour;
                } else {
                    return _('Permissions are not correctly set for file ' . $exe);
                }
            } else {
                return _('Paybox CGI module can not be found');
            }
        }

        static function getRealIpAddr() {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return $ip;
        }

        static function getErreurMsg($code_erreur) {
            $ErreurMsg = _('No Message');
            if ($code_erreur == '00000')
                $ErreurMsg = 'Opération réussie.';
            if ($code_erreur == '00011')
                $ErreurMsg = 'Montant incorrect.';
            if ($code_erreur == '00001')
                $ErreurMsg = 'La connexion au centre d\'autorisation a échoué. Vous pouvez dans ce cas là effectuer les redirections des internautes vers le FQDN tpeweb1.paybox.com.';
            if ($code_erreur == '00015')
                $ErreurMsg = 'Paiement déjà effectué.';
            if ($code_erreur == '001')
                $ErreurMsg = 'Paiement refusé par le centre d\'autorisation. En cas d\'autorisation de la transaction par le centre d\'autorisation de la banque, le code erreur \'00100\' sera en fait remplacé directement par \'00000\'.';
            if ($code_erreur == '00016')
                $ErreurMsg = 'Abonné déjà existant (inscription nouvel abonné). Valeur \'U\' de la variable PBX_RETOUR.';
            if ($code_erreur == '00003')
                $ErreurMsg = 'Erreur Paybox.';
            if ($code_erreur == '00021')
                $ErreurMsg = 'Carte non autorisée.';
            if ($code_erreur == '00004')
                $ErreurMsg = 'Numéro de porteur ou cryptogramme visuel invalide.';
            if ($code_erreur == '00029')
                $ErreurMsg = 'Carte non conforme. Code erreur renvoyé lors de la documentation de la variable « PBX_EMPREINTE ».';
            if ($code_erreur == '00006')
                $ErreurMsg = 'Accès refusé ou site/rang/identifiant incorrect.';
            if ($code_erreur == '00030')
                $ErreurMsg = 'Temps d\'attente > 15 mn par l\'internaute/acheteur au niveau de la page de paiements.';
            if ($code_erreur == '00008')
                $ErreurMsg = 'Date de fin de validité incorrecte.';
            if ($code_erreur == '00031')
                $ErreurMsg = 'Réservé';
            if ($code_erreur == '00009')
                $ErreurMsg = 'Erreur de création d\'un abonnement.';
            if ($code_erreur == '00032')
                $ErreurMsg = 'Réservé';
            if ($code_erreur == '00010')
                $ErreurMsg = 'Devise inconnue.';
            if ($code_erreur == '00033')
                $ErreurMsg = 'Code pays de l\'adresse IP du navigateur de l\'acheteur non autorisé.';
            return $ErreurMsg;
        }

    }

    // Fin de la classe
    /*
     * Ajout de la "gateway" Paybox à woocommerce
     */
    function add_paybox_commerce_gateway($methods) {
        $methods[] = 'WC_Paybox';
        return $methods;
    }

    include_once('shortcode-openboutique-thankyou.php');

    add_shortcode('openboutique_thankyou', 'get_openboutique_thankyou');
    add_filter('woocommerce_payment_gateways', 'add_paybox_commerce_gateway');
    add_action('init', 'woocommerce_paybox_check_response');
}

/**
 * Reponse Paybox (Pour le serveur Paybox)
 *
 * @access public
 * @return void
 */
function woocommerce_paybox_check_response() {
    if (isset($_GET['order']) && isset($_GET['sign'])) { // On a bien un retour ave une commande et une signature
        global $woocommerce;
        $order = new WC_Order((int) $_GET['order']); // On récupère la commande
        $pos_qs = strpos($_SERVER['REQUEST_URI'], '?');
        $pos_sign = strpos($_SERVER['REQUEST_URI'], '&sign=');
        $return_url = substr($_SERVER['REQUEST_URI'], 1, $pos_qs - 1);
        $data = substr($_SERVER['REQUEST_URI'], $pos_qs + 1, $pos_sign - $pos_qs - 1);
        $sign = substr($_SERVER['REQUEST_URI'], $pos_sign + 6);
        // Est-on en réception d'un retour PayBox
        $my_WC_Paybox = new WC_Paybox();
        if (str_replace('//', '/', '/' . $return_url) == str_replace('//', '/', $my_WC_Paybox->return_url)) {
            $std_msg = 'Paybox Return IP:' . WC_Paybox::getRealIpAddr() . '<br/>' . $data . '<br/><div style="word-wrap:break-word;">PBX Sign : ' . $sign . '<div>';
            @ob_clean();
            // Traitement du retour PayBox
            // PBX_RETOUR=order:R;erreur:E;carte:C;numauto:A;numtrans:S;numabo:B;montantbanque:M;sign:K
            if (isset($_GET['erreur'])) {
                switch ($_GET['erreur']) {
                    case '00000':
                        // OK Pas de pb
                        // On vérifie la clef
                        // recuperation de la cle publique
                        $fp = $filedata = $key = FALSE;
                        $fsize = filesize(dirname(__FILE__) . '/lib/pubkey.pem');
                        $fp = fopen(dirname(__FILE__) . '/lib/pubkey.pem', 'r');
                        $filedata = fread($fp, $fsize);
                        fclose($fp);
                        $key = openssl_pkey_get_public($filedata);
                        $decoded_sign = base64_decode(urldecode($sign));
                        $verif_sign = openssl_verify($data, $decoded_sign, $key);
                        if ($verif_sign == 1) { // La commande est bien signé par PayBox
                            // Si montant ok
                            if ((int) (100 * $order->get_total()) == (int) $_GET['montantbanque']) {
                                $order->add_order_note('<p style="color:green"><b>Paybox Return OK</b></p><br/>' . $std_msg);
                                $order->payment_complete();
                                unset($woocommerce->session->order_awaiting_payment);
                                wp_die('OK', '', array('response' => 200));
                                wp_die('OK');
                            } else {
                                $order->add_order_note('<p style="color:red"><b>ERROR</b></p> Order Amount<br/>' . $std_msg);
                                wp_die('KO Amount modified : ' . $_GET['montantbanque'] . ' / ' . (100 * $order->get_total()), '', array('response' => 406));
                            }
                        } else {
                            $order->add_order_note('<p style="color:red"><b>ERROR</b></p> Signature Rejected<br/>' . $std_msg);
                            wp_die('KO Signature', '', array('response' => 406));
                        }
                        break;
                    default:
                        $order->add_order_note('<p style="color:red"><b>PBX ERROR ' . $_GET['erreur'] . '</b> ' . WC_Paybox::getErreurMsg($_GET['erreur']) . '</p><br/>' . $std_msg);
                        wp_die('OK received', '', array('response' => 200));
                        break;
                }
            } else {
                wp_die('Test AutoResponse OK', '', array('response' => 200));
            }
        }
    }
}

