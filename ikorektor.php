<?php
/**
 * @package iKorektor
 */
/*
 * Plugin Name: iKorektor
 * Plugin URI: https://ikorektor.pl/pluginy
 * Description: Plugin dodający przycisk autokorekty w polach tekstowych.
 * Version: 1.3.0
 * Author: iKorektor.pl
 * Author URI: https://ikorektor.pl
 * License: GPLv2
 * Text Domain: ikorektor
 */

defined('ABSPATH') or die('Error');

class iKorektor
{
    private $plugin;
    private $settings = [];
    private $sections = [];
    private $fields = [];
    private $jsURL;
    private $jsVersion;
    private $jsSRIhash;
    private $cssSRIhash;
    
    function __construct() {
        $this->plugin = plugin_basename(__FILE__);
        $this->jsVersion = '3.0.0';
        $this->jsURL = "https://cdn.jsdelivr.net/gh/ikorektor/plugin@$this->jsVersion/script.min.js";
        $this->jsSRIhash = 'sha384-gU4kGVa9/FEABO7gJ1SGhUHFBrCDA8GDdCjboymvxHGl0SvnEKMX+pCjjSv3CLmj';
        $this->cssSRIhash = 'sha384-HQNf3FgG91lgF9ZJC7opMeOXyNiKyarvdnqzK58M64sGKxXlt6sU47B+1fByPLrG';
    }
    
    function register() {
        add_filter("plugin_action_links_$this->plugin", [$this, 'settingsLink']);
        add_action('admin_menu', [$this, 'addAdminPages']);
        add_action('admin_init', [$this, 'registerCustomFields']);
        add_action('admin_enqueue_scripts', [$this, 'loadScripts']);
        
        $validUri = $this->checkUri();
        
        if (get_option('ik_wp_show', 1) == 1 && $validUri) {
            add_action('wp_footer', function() {
                $this->addPluginJS('wp');
            });
        }
        
        if (get_option('ik_adm_show', 1) == 1 && $validUri) {
            add_action('admin_footer', function() {
                $this->addPluginJS('adm');
            });
        }
    }
    
    function addPluginJS($w) {
        echo '<script>
            const iKorektorConf = Object.freeze({
                btn: {
                    type: "', get_option("ik_${w}_type", 'small'), '",
                    location: "', get_option("ik_${w}_location", 'bottom'), '",
                    color: "', get_option("ik_${w}_color", '#093'), '",
                    inputs: ', (get_option("ik_${w}_inputs", 1) == 1 ? 'true' : 'false'), '
                },
                corr: {
                    parags: ', get_option("ik_${w}_parags", 0), ',
                    profanity: ', get_option("ik_${w}_profanity", 0), ',
                    gateway: ', (get_option("ik_${w}_gateway", 1) == 1 ? 'true' : 'false'), '
                },
                txtbg: ', (get_option("ik_${w}_txtbg", 1) == 1 ? 'true' : 'false'), ',
                prompt: ', (get_option("ik_${w}_prompt") == 1 ? 'true' : 'false'), ',
                always: ', (get_option('ik_always') == 1 ? 'true' : 'false'), ',
                version: "', $this->jsVersion, '",
                csshash: "', $this->cssSRIhash, '"
            });
            </script>
            <script type="module" 
                src="', $this->jsURL, '"
                integrity="', $this->jsSRIhash, '"
                crossorigin="anonymous"></script>';
    }
    
    function checkUri() {
        $optUris = get_option('ik_uris');
        
        if ($optUris) {
            $uris = explode("\n", $optUris);
            
            foreach ($uris as $uri) {
                if (strpos($_SERVER['REQUEST_URI'], trim($uri)) !== false) {
                    return true;
                }
            }
            
            return false;
        }
        
        return true;
    }
    
    function addAdminPages() {
        add_menu_page('iKorektor', 'iKorektor', 'manage_options', 'ikorektor', [$this, 'settingsPage'], '', null);
    }
    
    function settingsPage() {
        require_once plugin_dir_path(__FILE__) . 'settings.php';
    }
    
    function settingsLink($links) {
        $links[] = '<a href="admin.php?page=ikorektor">Ustawienia</a>';
        return $links;
    }
    
    function optBtnShow() {
        echo '<p class="ik-opt"><input type="checkbox" name="ik_wp_show" id="ik_wp_show" value="1" ', checked(1, get_option('ik_wp_show', 1), true), '></p>
              <p class="ik-opt"><input type="checkbox" name="ik_adm_show" value="1" ', checked(1, get_option('ik_adm_show', 1), true), '></p>';
    }
    
    function optBtnLocation() {
        $optWP = get_option('ik_wp_location', 'bottom');
        $optAdm = get_option('ik_adm_location', 'bottom');
        echo 
        '<p class="ik-opt">
           <label><input type="radio" name="ik_wp_location" value="top" ', checked('top', $optWP, true), '> góra</label><br>
           <label><input type="radio" name="ik_wp_location" value="bottom" ', checked('bottom', $optWP, true), '> dół</label>
         </p>
         <p class="ik-opt">
           <label><input type="radio" name="ik_adm_location" value="top" ', checked('top', $optAdm, true), '> góra</label><br>
           <label><input type="radio" name="ik_adm_location" value="bottom" ', checked('bottom', $optAdm, true), '> dół</label>
         </p>';
    }
    
    function optBtnType() {
        $optWP = get_option('ik_wp_type', 'small');
        $optAdm = get_option('ik_adm_type', 'small');
        echo 
        '<p class="ik-opt">
           <label><input type="radio" name="ik_wp_type" value="small" ', checked('small', $optWP, true), '> mały</label><br>
           <label><input type="radio" name="ik_wp_type" value="big" ', checked('big', $optWP, true), '> duży</label>
         </p>
         <p class="ik-opt">
           <label><input type="radio" name="ik_adm_type" value="small" ', checked('small', $optAdm, true), '> mały</label><br>
           <label><input type="radio" name="ik_adm_type" value="big" ', checked('big', $optAdm, true), '> duży</label>
         </p>';
    }

    function optBtnColor() {
        echo '<p class="ik-opt"><input type="color" name="ik_wp_color" id="ik_wp_color" value="', esc_attr(get_option('ik_wp_color', '#009933')), '"></p>
              <p class="ik-opt"><input type="color" name="ik_adm_color" value="', esc_attr(get_option('ik_adm_color', '#009933')), '"></p>';
    }
    
    function optBtnInputs() {
        echo '<p class="ik-opt"><input type="checkbox" name="ik_wp_inputs" id="ik_wp_inputs" value="1" ', checked(1, get_option('ik_wp_inputs', 1), true), '></p>
              <p class="ik-opt"><input type="checkbox" name="ik_adm_inputs" value="1" ', checked(1, get_option('ik_adm_inputs', 1), true), '></p>';
    }

    function optCorrGateway() {
        echo '<p class="ik-opt"><input type="checkbox" name="ik_wp_gateway" id="ik_wp_gateway" value="1" ', checked(1, get_option('ik_wp_gateway', 1), true), '></p>
              <p class="ik-opt"><input type="checkbox" name="ik_adm_gateway" value="1" ', checked(1, get_option('ik_adm_gateway', 1), true), '></p>';
    }
    
    function optCorrParags() {
        echo '<p class="ik-opt"><input type="number" min="0" max="10" name="ik_wp_parags" id="ik_wp_parags" value="', esc_attr(get_option('ik_wp_parags', 0)), '"></p>
              <p class="ik-opt"><input type="number" min="0" max="10" name="ik_adm_parags" value="', esc_attr(get_option('ik_adm_parags', 0)), '"></p>';
    }
    
    function optCorrProfanity() {
        echo '<p class="ik-opt"><input type="number" min="0" max="6" name="ik_wp_profanity" id="ik_wp_profanity" value="', esc_attr(get_option('ik_wp_profanity', 0)), '"></p>
              <p class="ik-opt"><input type="number" min="0" max="6" name="ik_adm_profanity" value="', esc_attr(get_option('ik_adm_profanity', 0)), '"></p>';
    }
    
    function optTxtbg() {
        echo '<p class="ik-opt"><input type="checkbox" name="ik_wp_txtbg" id="ik_wp_inputs" value="1" ', checked(1, get_option('ik_wp_txtbg', 1), true), '></p>
              <p class="ik-opt"><input type="checkbox" name="ik_adm_txtbg" value="1" ', checked(1, get_option('ik_adm_txtbg', 1), true), '></p>';
    }
    
    function optPrompt() {
        echo '<p class="ik-opt"><input type="checkbox" name="ik_wp_prompt" id="ik_wp_prompt" value="1" ', checked(1, get_option('ik_wp_prompt'), true), '></p>
              <p class="ik-opt"><input type="checkbox" name="ik_adm_prompt" value="1" ', checked(1, get_option('ik_adm_prompt'), true), '></p>';
    }
    
    function optAlways() {
        echo '<p class="ik-opt"><input type="checkbox" name="ik_always" id="ik_always" value="1" ', checked(1, get_option('ik_always'), false), '></p>
              <p class="ik-opt"></p>';
    }
    
    function optUris() {
        echo '<textarea name="ik_uris" id="ik_uris">', esc_attr(get_option('ik_uris')), '</textarea>
              <p class="description">Wprowadź względne adresy URL, każdy w nowej linii. Przykłady:<br>
              /2019/02/06/tytul-wpisu/<br>
              /wp-admin/admin.php?page=ikorektor</p>';
    }
    
    function setSettings() {
        $this->settings = [
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_show'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_show'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_uris'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_type'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_type'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_inputs'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_inputs'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_txtbg'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_txtbg'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_location'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_location'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_color'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_color'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_prompt'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_prompt'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_parags'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_parags'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_profanity'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_profanity'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_wp_gateway'
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_adm_gateway',
            ],
            [
                'option_group' => 'ikorektor_options_group',
                'option_name' => 'ik_always'
            ]
        ];
    }
    
    function setSections() {
        $this->sections = [
            [
                'id' => 'ikorektor_settings',
                'title' => '',
                'page' => 'ikorektor'
            ]
        ];
    }
    
    function setFields() {
        $this->fields = [
            [
                'id' => 'ik_show',
                'title' => 'Wyświetlanie przycisku autokorekty na stronie',
                'callback' => [$this, 'optBtnShow'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_show']
            ],
            [
                'id' => 'ik_uris',
                'title' => 'Ograniczenie wyświetlania przycisku tylko do wybranych podstron',
                'callback' => [$this, 'optUris'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_uris']
            ],
            [
                'id' => 'ik_type',
                'title' => 'Rodzaj przycisku',
                'callback' => [$this, 'optBtnType'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_type']
            ],
            [
                'id' => 'ik_location',
                'title' => 'Lokalizacja przycisku w polu tekstowym',
                'callback' => [$this, 'optBtnLocation'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings'
            ],
            [
                'id' => 'ik_color',
                'title' => 'Kolor przycisku',
                'callback' => [$this, 'optBtnColor'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings'
            ],
            [
                'id' => 'ik_inputs',
                'title' => 'Wyświetlanie przycisku w jednoliniowych polach tekstowych',
                'callback' => [$this, 'optBtnInputs'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_inputs']
            ],
            [
                'id' => 'ik_parags',
                'title' => 'Dzielenie tekstów na akapity – liczba zdań na akapit',
                'callback' => [$this, 'optCorrParags'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_parags']
            ],
            [
                'id' => 'ik_profanity',
                'title' => 'Ukrywanie wulgaryzmów<br>– liczba gwiazdek w słowie',
                'callback' => [$this, 'optCorrProfanity'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_profanity']
            ],
            [
                'id' => 'ik_gateway',
                'title' => 'Ignorowanie wyrazów poprzedzonych znakiem „\”',
                'callback' => [$this, 'optCorrGateway'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_gateway']
            ],
            [
                'id' => 'ik_txtbg',
                'title' => 'Wyświetlanie tła „w jedną linię” w oknie z tekstem poprawionym',
                'callback' => [$this, 'optTxtbg'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_txtbg']
            ],
            [
                'id' => 'ik_prompt',
                'title' => 'Wyświetlanie propozycji sprawdzenia tekstu przy wysyłaniu formularza',
                'callback' => [$this, 'optPrompt'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_wp_prompt']
            ],
            [
                'id' => 'ik_always',
                'title' => 'Aktywacja wtyczki na każdej podstronie',
                'callback' => [$this, 'optAlways'],
                'page' => 'ikorektor',
                'section' => 'ikorektor_settings',
                'args' => ['label_for' => 'ik_always']
            ]
        ];
    }
    
    function registerCustomFields() {
        $this->setSettings();
        $this->setSections();
        $this->setFields();
        
        foreach($this->settings as $setting) {
            register_setting($setting['option_group'], $setting['option_name'], $setting['callback'] ?? '');
        }
        
        foreach($this->sections as $section) {
            add_settings_section($section['id'], $section['title'], $section['callback'] ?? '', $section['page']);
        }
        
        foreach ($this->fields as $field) {
            add_settings_field($field['id'], $field['title'], $field['callback'] ?? '', $field['page'], $field['section'], $field['args'] ?? '');
        }
    }
    
    function loadScripts($hook) {
        if ($hook === 'toplevel_page_ikorektor') {
            wp_enqueue_style('ikorektor_style', plugins_url('/css/admin.css', __FILE__));
        }
    }
}

$ikorektor = new iKorektor();
$ikorektor->register();