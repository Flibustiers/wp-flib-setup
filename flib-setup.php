<?php
/**
 * FLIB - Setup WordPress (tout-en-un)
 *
 * Met en place un nouveau site WordPress de l'agence Les Flibustiers en une seule fois :
 * URL temporaire N0C dans wp-config.php, nom du site, favicon + manifest, thème flib-base
 * (et thème enfant au nom du client), extensions de base, réglages, pages, menu, .htaccess,
 * nettoyage du contenu par défaut.
 *
 * UTILISATION
 *   1. Créer le site WordPress sur PlanetHoster (N0C).
 *   2. Déposer ce fichier à la racine du site, à côté de wp-config.php (gestionnaire de fichiers N0C).
 *   3. Ouvrir https://<url-temporaire>/flib-setup.php, se connecter avec le compte administrateur
 *      WordPress, remplir le formulaire, lancer.
 *   4. Le fichier se supprime à la fin et affiche le compte rendu.
 *
 * SÉCURITÉ
 *   - Utilisable uniquement dans les 2 heures qui suivent son dépôt sur le serveur, puis il se supprime.
 *   - Identifiant + mot de passe d'un administrateur WordPress obligatoires ; 5 essais au maximum.
 *   - Se supprime dès que le setup est terminé.
 *
 * MODIFIER LES LISTES D'EXTENSIONS : voir flib_setup_config() ci-dessous.
 *
 * Dépôt : https://github.com/Flibustiers/wp-flib-setup — basé sur FLIB - WP Setup 1.0 (Clément GUEZOU).
 */

const FLIB_SETUP_VERSION  = '2.2.0';
const FLIB_SETUP_VALIDITY = 7200; // secondes après le dépôt du fichier
const FLIB_SETUP_MAX_TRY  = 5;

// Jeton GitHub intégré : rempli automatiquement par la GitHub Action dans le fichier de la release
// (secret FLIB_GITHUB_TOKEN du dépôt). Ne jamais l'écrire ici à la main : il serait versionné dans Git.
const FLIB_SETUP_EMBEDDED_TOKEN = '';

/* ===========================================================================
 * CONFIGURATION — listes modifiables
 * ======================================================================== */

function flib_setup_config()
{
    return [

        // Thème parent de l'agence (release GitHub contenant flib-base.zip).
        'theme' => [
            'repo'       => 'Flibustiers/flib-base',
            'slug'       => 'flib-base',
            // Thème enfant de départ, copié depuis le dépôt (branche + dossier) et renommé au nom du site.
            'child_ref'  => 'main',
            'child_path' => 'theme/flib-enfant',
        ],

        // Pages créées (clé => titre, slug). Une page existante avec le même slug est réutilisée.
        'pages' => [
            'accueil'          => ['title' => 'Accueil', 'slug' => 'accueil'],
            'contact'          => ['title' => 'Contact', 'slug' => 'contact'],
            'mentions-legales' => ['title' => 'Mentions légales', 'slug' => 'mentions-legales'],
            'confidentialite'  => ['title' => 'Politique de confidentialité', 'slug' => 'politique-de-confidentialite'],
        ],

        // Pages du menu principal (clés de « pages »), dans l'ordre.
        'menu' => [
            'name'  => 'Menu principal',
            'pages' => ['accueil', 'contact'],
        ],

        'plugins' => [

            // Extensions maison : dépôts privés Flibustiers, release GitHub contenant un .zip.
            // Le jeton GitHub doit avoir accès au dépôt (Contents : lecture seule).
            'github' => [
                'flib-up' => [
                    'name'    => "Flib'Up (pop-ups)",
                    'repo'    => 'Flibustiers/wp-flib-up',
                    'default' => true,
                ],
                'flib-grilles' => [
                    'name'    => 'Flib Grilles (grilles de contenus filtrables)',
                    'repo'    => 'Flibustiers/wp-flib-grilles',
                    'default' => true,
                ],
                'flib-seo' => [
                    'name'    => "Flib'SEO (référencement, remplace Yoast)",
                    'repo'    => 'Flibustiers/wp-flib-seo',
                    'default' => true,
                ],
            ],

            // Extensions WordPress.org : slug = fin de l'URL https://wordpress.org/plugins/<slug>/
            // « default => false » : proposée mais décochée par défaut.
            'wporg' => [
                'svg-support'                                           => ['name' => 'SVG Support', 'default' => true],
                'auto-image-attributes-from-filename-with-bulk-updater' => ['name' => 'Auto Image Attributes From Filename (balises alt)', 'default' => true],
                'advanced-custom-fields'                                => ['name' => 'Advanced Custom Fields (ACF)', 'default' => true],
                'forminator'                                            => ['name' => 'Forminator', 'default' => true],
                'matomo'                                                => ['name' => 'Matomo Analytics', 'default' => true],
                'tarteaucitronjs'                                       => ['name' => 'tarteaucitron.io (bandeau cookies)', 'default' => true],
            ],
        ],

        // Mu-plugins maison : dépôts privés Flibustiers, release GitHub contenant un .zip.
        // Seul le fichier « file » est copié dans wp-content/mu-plugins/ (dossier créé si absent).
        'mu_plugins' => [
            'flib-images-webp' => [
                'name'    => 'Flib Images WebP (conversion WebP à l\'envoi des images)',
                'repo'    => 'Flibustiers/wp-flib-images-webp',
                'file'    => 'flib-images-webp.php',
                'default' => true,
            ],
        ],
    ];
}

/* ===========================================================================
 * Mu-plugin favicon (écrit dans wp-content/mu-plugins/flib-favicon.php)
 * ======================================================================== */

const FLIB_SETUP_MU_FAVICON = <<<'PHPCODE'
<?php
/**
 * Plugin Name: FLIB - Favicon
 * Description: Ajoute les balises favicon / manifest pour les fichiers présents à la racine du site. Installé par FLIB Setup (flib-setup.php).
 * Author:      Les Flibustiers
 */

if (!defined('ABSPATH')) {
    exit;
}

function flib_favicon_tags()
{
    static $html = null;

    if (null !== $html) {
        return $html;
    }

    $html  = '';
    $files = [
        'favicon.ico'          => '<link rel="icon" href="%s" sizes="any">',
        'favicon.svg'          => '<link rel="icon" href="%s" type="image/svg+xml">',
        'favicon-96x96.png'    => '<link rel="icon" href="%s" type="image/png" sizes="96x96">',
        'favicon-32x32.png'    => '<link rel="icon" href="%s" type="image/png" sizes="32x32">',
        'favicon-16x16.png'    => '<link rel="icon" href="%s" type="image/png" sizes="16x16">',
        'apple-touch-icon.png' => '<link rel="apple-touch-icon" href="%s" sizes="180x180">',
    ];

    foreach ($files as $file => $tag) {
        if (file_exists(ABSPATH . $file)) {
            $html .= sprintf($tag, esc_url(site_url('/' . $file))) . "\n";
        }
    }

    foreach (['site.webmanifest', 'manifest.webmanifest', 'manifest.json'] as $manifest) {
        if (file_exists(ABSPATH . $manifest)) {
            $html .= '<link rel="manifest" href="' . esc_url(site_url('/' . $manifest)) . '">' . "\n";
            break;
        }
    }

    if ('' !== $html) {
        $html .= '<meta name="apple-mobile-web-app-title" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    }

    return $html;
}

function flib_favicon_output()
{
    echo flib_favicon_tags(); // phpcs:ignore WordPress.Security.EscapeOutput -- URL et attributs échappés ci-dessus.
}

add_action('init', function () {
    if ('' === flib_favicon_tags()) {
        return;
    }
    // Nos fichiers remplacent l'icône de site native de WordPress.
    remove_action('wp_head', 'wp_site_icon', 99);
    remove_action('admin_head', 'wp_site_icon');
    remove_action('login_head', 'wp_site_icon', 99);

    add_action('wp_head', 'flib_favicon_output', 5);
    add_action('admin_head', 'flib_favicon_output');
    add_action('login_head', 'flib_favicon_output');
});
PHPCODE;

/* ===========================================================================
 * class-flib-logger.php
 * ======================================================================== */
/**
 * Compte rendu d'exécution : chaque étape ajoute des lignes ok / avertissement / erreur.
 */


class Flib_Setup_Logger
{
    /** @var array<int, array{section:string, status:string, message:string}> */
    private $entries = [];

    /** @var string */
    private $section = '';

    public function section($title)
    {
        $this->section = (string) $title;
    }

    public function ok($message)
    {
        $this->add('ok', $message);
    }

    public function warn($message)
    {
        $this->add('warn', $message);
    }

    public function error($message)
    {
        $this->add('error', $message);
    }

    /**
     * Enregistre une WP_Error (ou un message) comme erreur et renvoie false, pour écrire :
     * if (is_wp_error($r)) { return $log->wp_error($r, 'Contexte'); }
     */
    public function wp_error($error, $context = '')
    {
        $message = is_wp_error($error) ? $error->get_error_message() : (string) $error;
        $this->error(('' !== $context ? $context . ' : ' : '') . $message);

        return false;
    }

    public function has_errors()
    {
        foreach ($this->entries as $entry) {
            if ('error' === $entry['status']) {
                return true;
            }
        }

        return false;
    }

    public function entries()
    {
        return $this->entries;
    }

    private function add($status, $message)
    {
        $this->entries[] = [
            'section' => $this->section,
            'status'  => $status,
            'message' => html_entity_decode(wp_strip_all_tags((string) $message), ENT_QUOTES, 'UTF-8'),
        ];
    }
}

/* ===========================================================================
 * class-flib-wpconfig.php
 * ======================================================================== */
/**
 * Écriture d'un bloc de constantes dans wp-config.php, entre deux repères.
 *
 * - Le bloc est remplacé à chaque exécution (jamais dupliqué).
 * - Les define() de ces constantes écrits ailleurs dans le fichier sont commentés,
 *   pour éviter les avertissements « constant already defined ».
 * - Une sauvegarde horodatée du fichier est faite avant toute modification.
 *
 * Les repères BEGIN / END FLIB WP SETUP restent identiques d'une version à l'autre.
 */


class Flib_Setup_WPConfig
{
    const BEGIN = '// BEGIN FLIB WP SETUP';
    const END   = '// END FLIB WP SETUP';

    /**
     * Chemin de wp-config.php (racine du site, ou dossier parent comme le permet WordPress).
     *
     * @return string|false
     */
    public static function path()
    {
        if (file_exists(ABSPATH . 'wp-config.php')) {
            return ABSPATH . 'wp-config.php';
        }

        $parent = dirname(ABSPATH) . '/wp-config.php';
        if (file_exists($parent) && !file_exists(dirname(ABSPATH) . '/wp-settings.php')) {
            return $parent;
        }

        return false;
    }

    /**
     * Valeurs actuellement écrites dans notre bloc (pour conserver un jeton déjà en place).
     *
     * @return array<string, string>
     */
    public static function read_block_values($contents)
    {
        $values = [];
        $block  = self::extract_block($contents);

        if ('' !== $block && preg_match_all("/define\(\s*'([A-Z_]+)'\s*,\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/", $block, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $values[$match[1]] = stripslashes($match[2]);
            }
        }

        return $values;
    }

    /**
     * Écrit (ou remplace) le bloc avec les constantes données.
     *
     * @param array<string, string> $constants Nom => valeur.
     * @return true|WP_Error
     */
    public static function write(array $constants)
    {
        $path = self::path();
        if (!$path) {
            return new WP_Error('flib_wpconfig', 'wp-config.php introuvable.');
        }
        if (!is_writable($path)) {
            return new WP_Error('flib_wpconfig', 'wp-config.php n\'est pas modifiable (droits du fichier).');
        }

        $original = file_get_contents($path);
        if (false === $original || '' === $original) {
            return new WP_Error('flib_wpconfig', 'Lecture de wp-config.php impossible.');
        }

        $updated = self::apply($original, $constants);
        if ($updated === $original) {
            return true;
        }

        $backup = dirname($path) . '/wp-config-sauvegarde-flib-' . gmdate('Ymd-His') . '.php';
        if (!@copy($path, $backup)) {
            return new WP_Error('flib_wpconfig', 'Sauvegarde de wp-config.php impossible : aucune modification faite.');
        }

        // Écriture via un fichier temporaire puis renommage, pour ne jamais laisser un wp-config.php à moitié écrit.
        $tmp = $path . '.flib-tmp';
        if (false === file_put_contents($tmp, $updated, LOCK_EX) || !@rename($tmp, $path)) {
            @unlink($tmp);
            return new WP_Error('flib_wpconfig', 'Écriture de wp-config.php impossible.');
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }

        return true;
    }

    /**
     * Transformation pure du contenu (testable sans toucher au disque).
     */
    public static function apply($contents, array $constants)
    {
        $eol = (false !== strpos($contents, "\r\n")) ? "\r\n" : "\n";

        // 1. Retire notre ancien bloc.
        $pattern  = '/[ \t]*' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '[^\r\n]*(\r?\n){0,2}/s';
        $contents = preg_replace($pattern, '', $contents);

        // 2. Commente les autres define() des mêmes constantes.
        foreach (array_keys($constants) as $name) {
            $contents = preg_replace(
                '/^([ \t]*)(define\(\s*[\'"]' . preg_quote($name, '/') . '[\'"].*)$/m',
                '$1// [désactivé par FLIB Setup] $2',
                $contents
            );
        }

        // 3. Construit le bloc.
        $lines = [self::BEGIN . ' — généré automatiquement, ne pas modifier entre ces repères'];
        foreach ($constants as $name => $value) {
            $lines[] = "define( '" . $name . "', " . var_export((string) $value, true) . ' );';
        }
        $lines[] = self::END;
        $block   = implode($eol, $lines) . $eol . $eol;

        // 4. L'insère avant « That's all, stop editing » / « C’est tout » ou, à défaut, avant le chargement de WordPress.
        $anchors = [
            '/^[ \t]*\/\*\s*That\'s all, stop editing.*$/mi',
            '/^[ \t]*\/\*\s*C.{1,3}est tout, ne touchez pas.*$/miu',
            '/^[ \t]*if\s*\(\s*!\s*defined\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)/mi',
            '/^[ \t]*require_once\s*\(?\s*ABSPATH\s*\.\s*[\'"]wp-settings\.php[\'"]/mi',
        ];
        foreach ($anchors as $anchor) {
            if (preg_match($anchor, $contents, $m, PREG_OFFSET_CAPTURE)) {
                $offset = $m[0][1];
                return substr($contents, 0, $offset) . $block . substr($contents, $offset);
            }
        }

        // Aucun repère trouvé : juste après la balise d'ouverture PHP.
        return preg_replace('/^<\?php\s*/', '<?php' . $eol . $eol . $block, $contents, 1);
    }

    private static function extract_block($contents)
    {
        $start = strpos($contents, self::BEGIN);
        $end   = strpos($contents, self::END);

        if (false === $start || false === $end || $end < $start) {
            return '';
        }

        return substr($contents, $start, $end - $start);
    }
}

/* ===========================================================================
 * class-flib-github.php
 * ======================================================================== */
/**
 * Téléchargement depuis les dépôts privés GitHub de l'agence.
 *
 * Même logique que la mise à jour intégrée de flib-base et des extensions :
 * l'API renvoie une redirection vers une URL signée, à suivre SANS l'en-tête Authorization.
 */


class Flib_Setup_GitHub
{
    /** @var string */
    private $token;

    public function __construct($token)
    {
        $this->token = (string) $token;
    }

    public function has_token()
    {
        return '' !== $this->token;
    }

    /**
     * Télécharge le premier .zip de la dernière release publiée.
     *
     * @return array{file:string, version:string}|WP_Error
     */
    public function download_latest_release($repo)
    {
        $data = $this->api('/repos/' . $repo . '/releases/latest', $repo);
        if (is_wp_error($data)) {
            return $data;
        }

        foreach ((array) ($data['assets'] ?? []) as $asset) {
            if (isset($asset['id'], $asset['name']) && '.zip' === strtolower(substr($asset['name'], -4))) {
                $file = $this->download_redirect('/repos/' . $repo . '/releases/assets/' . (int) $asset['id'], 'application/octet-stream');
                if (is_wp_error($file)) {
                    return $file;
                }

                return [
                    'file'    => $file,
                    'version' => ltrim((string) ($data['tag_name'] ?? ''), 'vV'),
                ];
            }
        }

        return new WP_Error('flib_github', 'aucun fichier .zip dans la dernière release de ' . $repo);
    }

    /**
     * Télécharge l'archive complète d'une branche (utilisée pour copier le thème enfant de départ).
     *
     * @return string|WP_Error Chemin du fichier temporaire.
     */
    public function download_branch($repo, $ref)
    {
        return $this->download_redirect('/repos/' . $repo . '/zipball/' . rawurlencode($ref), 'application/vnd.github+json');
    }

    /**
     * @return array|WP_Error
     */
    private function api($path, $repo)
    {
        if (!$this->has_token()) {
            return new WP_Error('flib_github', 'jeton GitHub manquant (champ du formulaire ou FLIB_GITHUB_TOKEN dans wp-config.php)');
        }

        $response = wp_remote_get('https://api.github.com' . $path, [
            'timeout' => 20,
            'headers' => $this->headers('application/vnd.github+json'),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('flib_github', 'GitHub injoignable (' . $response->get_error_message() . ')');
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            return new WP_Error('flib_github', self::explain($code, $repo));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return is_array($data) ? $data : new WP_Error('flib_github', 'réponse GitHub illisible');
    }

    /**
     * @return string|WP_Error
     */
    private function download_redirect($path, $accept)
    {
        if (!$this->has_token()) {
            return new WP_Error('flib_github', 'jeton GitHub manquant');
        }

        $response = wp_remote_get('https://api.github.com' . $path, [
            'timeout'     => 30,
            'redirection' => 0,
            'headers'     => $this->headers($accept),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('flib_github', 'GitHub injoignable (' . $response->get_error_message() . ')');
        }

        $code     = (int) wp_remote_retrieve_response_code($response);
        $location = wp_remote_retrieve_header($response, 'location');

        if ($code >= 300 && $code < 400 && $location) {
            if (!function_exists('download_url')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            return download_url($location, 300);
        }

        return new WP_Error('flib_github', self::explain($code, $path));
    }

    private function headers($accept)
    {
        return [
            'Accept'               => $accept,
            'Authorization'        => 'Bearer ' . $this->token,
            'User-Agent'           => 'flib-wp-setup',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    private static function explain($code, $what)
    {
        switch ($code) {
            case 401:
                return 'jeton GitHub refusé (expiré ou mal copié)';
            case 403:
                return 'accès refusé par GitHub (droits du jeton ou limite de requêtes)';
            case 404:
                return $what . ' introuvable : vérifier le nom du dépôt, l\'existence d\'une release, et que le jeton a accès à ce dépôt';
            default:
                return 'réponse GitHub HTTP ' . $code . ' pour ' . $what;
        }
    }
}

/* ===========================================================================
 * class-flib-installer.php
 * ======================================================================== */
/**
 * Installation de thèmes et d'extensions : depuis un zip (GitHub) ou depuis WordPress.org.
 */


class Flib_Setup_Installer
{
    /**
     * Charge les fichiers d'administration nécessaires et initialise le système de fichiers.
     *
     * @return true|WP_Error
     */
    public static function boot()
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            return new WP_Error('flib_fs', 'DISALLOW_FILE_MODS est activé dans wp-config.php : installation impossible.');
        }

        if (!WP_Filesystem()) {
            return new WP_Error('flib_fs', 'accès direct aux fichiers impossible (FS_METHOD). Vérifier les droits du dossier wp-content.');
        }

        return true;
    }

    /**
     * Installe le contenu d'un zip dans wp-content/plugins/<slug> ou wp-content/themes/<slug>.
     * Le dossier existant est remplacé.
     *
     * @param string      $zip     Fichier zip temporaire (supprimé ensuite).
     * @param string      $type    'plugin' ou 'theme'.
     * @param string      $slug    Nom du dossier de destination.
     * @param string|null $subpath Sous-dossier à extraire (ex. « theme/flib-enfant » dans une archive de branche).
     * @return string|WP_Error Chemin installé.
     */
    public static function install_zip($zip, $type, $slug, $subpath = null)
    {
        global $wp_filesystem;

        $tmp = trailingslashit(get_temp_dir()) . 'flib-setup-' . wp_generate_password(8, false);
        $unzip = unzip_file($zip, $tmp);
        @unlink($zip);

        if (is_wp_error($unzip)) {
            $wp_filesystem->delete($tmp, true);
            return $unzip;
        }

        $source = self::single_root($tmp);
        if (null !== $subpath) {
            $source = trailingslashit($source) . trim($subpath, '/');
        }

        if (!is_dir($source)) {
            $wp_filesystem->delete($tmp, true);
            return new WP_Error('flib_install', 'dossier « ' . ($subpath ?: $slug) . ' » absent de l\'archive');
        }

        $root = 'theme' === $type ? get_theme_root() : WP_PLUGIN_DIR;
        $dest = trailingslashit($root) . $slug;

        if ($wp_filesystem->exists($dest)) {
            $wp_filesystem->delete($dest, true);
        }

        $wp_filesystem->mkdir($dest, FS_CHMOD_DIR);
        $copy = copy_dir($source, $dest);
        $wp_filesystem->delete($tmp, true);

        if (is_wp_error($copy)) {
            return $copy;
        }

        if ('plugin' === $type) {
            wp_clean_plugins_cache();
        } else {
            wp_clean_themes_cache();
        }

        return $dest;
    }

    /**
     * Installe (si besoin) une extension WordPress.org.
     *
     * @return string|WP_Error Fichier principal de l'extension (ex. « forminator/forminator.php »).
     */
    public static function install_wporg_plugin($slug)
    {
        $file = self::plugin_file($slug);
        if ($file) {
            return $file;
        }

        $api = plugins_api('plugin_information', [
            'slug'   => $slug,
            'fields' => ['sections' => false, 'short_description' => false],
        ]);

        if (is_wp_error($api)) {
            return new WP_Error('flib_install', 'introuvable sur WordPress.org (' . $api->get_error_message() . ')');
        }

        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $result   = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            $errors = $upgrader->skin->get_errors();
            return is_wp_error($errors) && $errors->has_errors() ? $errors : new WP_Error('flib_install', 'installation échouée');
        }

        wp_clean_plugins_cache();
        $file = self::plugin_file($slug);

        return $file ?: new WP_Error('flib_install', 'installée, mais fichier principal introuvable');
    }

    /**
     * Fichier principal d'une extension installée dans le dossier <slug>.
     *
     * @return string|false
     */
    public static function plugin_file($slug)
    {
        $plugins = get_plugins('/' . $slug);
        if (empty($plugins)) {
            return false;
        }

        // Préfère <slug>/<slug>.php, sinon le premier fichier d'en-tête trouvé.
        $keys = array_keys($plugins);
        foreach ($keys as $key) {
            if ($slug . '.php' === $key) {
                return $slug . '/' . $key;
            }
        }

        return $slug . '/' . $keys[0];
    }

    /**
     * Active une extension (déclenche son hook d'activation).
     *
     * @return true|WP_Error
     */
    public static function activate($plugin_file)
    {
        if (is_plugin_active($plugin_file)) {
            return true;
        }

        $result = activate_plugin($plugin_file, '', false, false);

        return is_wp_error($result) ? $result : true;
    }

    /**
     * Copie un seul fichier d'un zip dans wp-content/mu-plugins/ (dossier créé si absent).
     * Le fichier est cherché à la racine de l'archive, sinon dans ses sous-dossiers.
     *
     * @param string $zip  Fichier zip temporaire (supprimé ensuite).
     * @param string $file Nom du fichier à copier (ex. « flib-images-webp.php »).
     * @return string|WP_Error Chemin installé.
     */
    public static function install_mu_plugin_zip($zip, $file)
    {
        global $wp_filesystem;

        $file = wp_basename($file);
        if ('php' !== strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            @unlink($zip);
            return new WP_Error('flib_install', 'nom de fichier de mu-plugin invalide (' . $file . ')');
        }

        $tmp   = trailingslashit(get_temp_dir()) . 'flib-setup-' . wp_generate_password(8, false);
        $unzip = unzip_file($zip, $tmp);
        @unlink($zip);

        if (is_wp_error($unzip)) {
            $wp_filesystem->delete($tmp, true);
            return $unzip;
        }

        $root   = self::single_root($tmp);
        $source = is_file($root . '/' . $file) ? $root . '/' . $file : '';
        if ('' === $source) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $item) {
                if ($item->isFile() && $file === $item->getFilename() && false === strpos($item->getPathname(), '__MACOSX')) {
                    $source = $item->getPathname();
                    break;
                }
            }
        }

        // Contrôle minimal : un fichier PHP avec un en-tête d'extension WordPress.
        $head = '' !== $source ? (string) file_get_contents($source, false, null, 0, 8192) : '';
        if ('' === $source || 0 !== strpos(ltrim($head), '<?php') || false === stripos($head, 'Plugin Name:')) {
            $wp_filesystem->delete($tmp, true);
            return new WP_Error('flib_install', '« ' . $file . ' » absent de l\'archive ou sans en-tête d\'extension');
        }

        $dir = WPMU_PLUGIN_DIR;
        if (!is_dir($dir) && !wp_mkdir_p($dir)) {
            $wp_filesystem->delete($tmp, true);
            return new WP_Error('flib_install', 'création du dossier wp-content/mu-plugins impossible');
        }

        $dest = trailingslashit($dir) . $file;
        $copy = @copy($source, $dest);
        $wp_filesystem->delete($tmp, true);

        if (!$copy) {
            return new WP_Error('flib_install', 'copie dans wp-content/mu-plugins impossible');
        }
        @chmod($dest, FS_CHMOD_FILE);

        return $dest;
    }

    /**
     * Une archive GitHub contient un seul dossier racine : on le renvoie.
     */
    private static function single_root($dir)
    {
        $items = array_values(array_diff((array) scandir($dir), ['.', '..', '__MACOSX']));

        if (1 === count($items) && is_dir($dir . '/' . $items[0])) {
            return $dir . '/' . $items[0];
        }

        return $dir;
    }
}

/* ===========================================================================
 * class-flib-favicon.php
 * ======================================================================== */
/**
 * Favicon + manifest : fichiers déposés à la racine du site, balises <link> ajoutées par un mu-plugin.
 *
 * Accepte un zip (ex. export RealFaviconGenerator) ou les fichiers d'un dossier.
 * Seules les extensions d'icônes / de manifest sont copiées ; les sous-dossiers sont aplatis.
 */


class Flib_Setup_Favicon
{
    const ALLOWED = ['ico', 'png', 'svg', 'webmanifest', 'json', 'xml'];

    const MU_PLUGIN = 'flib-favicon.php';

    /**
     * @param array|null $zip_upload   Entrée $_FILES d'un zip.
     * @param array|null $files_upload Entrée $_FILES d'un champ multiple (dossier).
     * @param string     $site_name    Nom du site, reporté dans le manifest.
     * @return string[]|WP_Error Fichiers copiés.
     */
    public static function import($zip_upload, $files_upload, $site_name)
    {
        $copied = [];

        if (self::has_upload($zip_upload)) {
            $result = self::import_zip($zip_upload);
            if (is_wp_error($result)) {
                return $result;
            }
            $copied = array_merge($copied, $result);
        }

        if (self::has_upload($files_upload)) {
            $count = count((array) $files_upload['name']);
            for ($i = 0; $i < $count; $i++) {
                if (UPLOAD_ERR_OK !== (int) $files_upload['error'][$i]) {
                    continue;
                }
                $name = self::copy_file($files_upload['tmp_name'][$i], $files_upload['name'][$i]);
                if ($name) {
                    $copied[] = $name;
                }
            }
        }

        $copied = array_values(array_unique($copied));
        if (empty($copied)) {
            return new WP_Error('flib_favicon', 'aucun fichier d\'icône reconnu (' . implode(', ', self::ALLOWED) . ')');
        }

        self::update_manifest($site_name);

        $mu = self::install_mu_plugin();
        if (is_wp_error($mu)) {
            return $mu;
        }

        return $copied;
    }

    public static function has_upload($upload)
    {
        if (empty($upload) || !isset($upload['error'])) {
            return false;
        }
        if (is_array($upload['error'])) {
            return in_array(UPLOAD_ERR_OK, array_map('intval', $upload['error']), true);
        }

        return UPLOAD_ERR_OK === (int) $upload['error'];
    }

    /**
     * @return string[]|WP_Error
     */
    private static function import_zip($upload)
    {
        global $wp_filesystem;

        if ('zip' !== strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION))) {
            return new WP_Error('flib_favicon', 'le fichier envoyé n\'est pas un .zip');
        }

        $tmp   = trailingslashit(get_temp_dir()) . 'flib-favicon-' . wp_generate_password(8, false);
        $unzip = unzip_file($upload['tmp_name'], $tmp);
        if (is_wp_error($unzip)) {
            return $unzip;
        }

        $copied   = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && false === strpos($file->getPathname(), '__MACOSX')) {
                $name = self::copy_file($file->getPathname(), $file->getFilename());
                if ($name) {
                    $copied[] = $name;
                }
            }
        }

        $wp_filesystem->delete($tmp, true);

        return $copied;
    }

    /**
     * Copie un fichier autorisé à la racine du site.
     *
     * @return string|false Nom du fichier copié.
     */
    private static function copy_file($source, $original_name)
    {
        $name = sanitize_file_name(wp_basename((string) $original_name));
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ('' === $name || '.' === $name[0] || !in_array($ext, self::ALLOWED, true)) {
            return false;
        }

        // Les .json / .xml ne sont acceptés que s'il s'agit de manifest / browserconfig.
        if (in_array($ext, ['json', 'xml'], true) && !preg_match('/^(manifest|site|browserconfig)[\w.-]*\.(json|xml)$/i', $name)) {
            return false;
        }

        // Un SVG ne doit pas contenir de script.
        if ('svg' === $ext) {
            $svg = (string) file_get_contents($source);
            if (preg_match('/<script|on\w+\s*=|javascript:/i', $svg)) {
                return false;
            }
        }

        return @copy($source, ABSPATH . $name) ? $name : false;
    }

    /**
     * Reporte le nom du site dans le manifest s'il porte encore un nom générique.
     */
    private static function update_manifest($site_name)
    {
        foreach (['site.webmanifest', 'manifest.webmanifest', 'manifest.json'] as $file) {
            $path = ABSPATH . $file;
            if (!file_exists($path)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($path), true);
            if (!is_array($data)) {
                continue;
            }

            $generic = ['', 'MyWebSite', 'MySite', 'My Web Site', 'Site'];
            foreach (['name', 'short_name'] as $key) {
                if (!isset($data[$key]) || in_array(trim((string) $data[$key]), $generic, true)) {
                    $data[$key] = $site_name;
                }
            }

            file_put_contents($path, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Installe le mu-plugin qui ajoute les balises <link> (il détecte lui-même les fichiers présents).
     *
     * @return true|WP_Error
     */
    private static function install_mu_plugin()
    {
        $dir = WPMU_PLUGIN_DIR;
        if (!is_dir($dir) && !wp_mkdir_p($dir)) {
            return new WP_Error('flib_favicon', 'création du dossier mu-plugins impossible');
        }

        if (false === file_put_contents(trailingslashit($dir) . self::MU_PLUGIN, FLIB_SETUP_MU_FAVICON)) {
            return new WP_Error('flib_favicon', 'copie du mu-plugin flib-favicon.php impossible');
        }

        return true;
    }
}

/* ===========================================================================
 * class-flib-steps.php
 * ======================================================================== */
/**
 * Étapes du setup. Chaque méthode publique est une étape indépendante qui écrit son compte rendu.
 */


class Flib_Setup_Steps
{
    /** @var Flib_Setup_Logger */
    private $log;

    /** @var array */
    private $config;

    /** @var array Options saisies dans le formulaire (voir flib_setup_read_options()). */
    private $opts;

    /** @var array<string, int> Pages créées ou retrouvées (clé de config => ID). */
    private $pages = [];

    public function __construct(Flib_Setup_Logger $log, array $config, array $opts)
    {
        $this->log    = $log;
        $this->config = $config;
        $this->opts   = $opts;
    }

    /* -----------------------------------------------------------------------
     * 1. wp-config.php : URL temporaire et jeton GitHub
     * -------------------------------------------------------------------- */

    public function wp_config()
    {
        $this->log->section('wp-config.php');

        $path = Flib_Setup_WPConfig::path();
        if (!$path) {
            $this->log->error('wp-config.php introuvable.');
            return;
        }

        // On repart des valeurs déjà présentes dans notre bloc, pour ne rien perdre.
        $constants = Flib_Setup_WPConfig::read_block_values((string) file_get_contents($path));
        $changes   = [];

        if ($this->opts['write_urls']) {
            if ('' === $this->opts['temp_url']) {
                $this->log->error('URL temporaire invalide : WP_HOME / WP_SITEURL non écrits.');
            } else {
                $constants['WP_HOME']    = $this->opts['temp_url'];
                $constants['WP_SITEURL'] = $this->opts['temp_url'];
                $changes[] = 'WP_HOME et WP_SITEURL = ' . $this->opts['temp_url'];
            }
        }

        if ($this->opts['save_token'] && '' !== $this->opts['token_to_save']
            && ($constants['FLIB_GITHUB_TOKEN'] ?? '') !== $this->opts['token_to_save']) {
            $constants['FLIB_GITHUB_TOKEN'] = $this->opts['token_to_save'];
            $changes[] = 'FLIB_GITHUB_TOKEN (mises à jour du thème et des extensions maison)';
        }

        if (empty($changes)) {
            $this->log->ok('Rien à modifier.');
            return;
        }

        $result = Flib_Setup_WPConfig::write($constants);
        if (is_wp_error($result)) {
            $this->log->wp_error($result);
            return;
        }

        foreach ($changes as $change) {
            $this->log->ok('Écrit : ' . $change . '.');
        }
        $this->log->ok('Sauvegarde de l\'ancien fichier : wp-config-sauvegarde-flib-*.php (à supprimer une fois le site validé).');
    }

    /* -----------------------------------------------------------------------
     * 2. Identité
     * -------------------------------------------------------------------- */

    public function identity()
    {
        $this->log->section('Identité');

        update_option('blogname', $this->opts['site_name']);
        update_option('blogdescription', '');

        $this->log->ok('Nom du site : « ' . $this->opts['site_name'] . ' » (slogan vidé).');
    }

    /* -----------------------------------------------------------------------
     * 3. Langue
     * -------------------------------------------------------------------- */

    public function language()
    {
        $this->log->section('Langue');

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        if (!in_array('fr_FR', get_available_languages(), true)) {
            $installed = wp_download_language_pack('fr_FR');
            if (!$installed) {
                $this->log->warn('Téléchargement des traductions françaises impossible : à faire dans Réglages › Général.');
                return;
            }
        }

        update_option('WPLANG', 'fr_FR');
        $this->log->ok('WordPress en français (fr_FR).');
    }

    /* -----------------------------------------------------------------------
     * 4. Réglages WordPress
     * -------------------------------------------------------------------- */

    public function settings()
    {
        $this->log->section('Réglages');

        $options = [
            // Date et heure.
            'timezone_string'             => 'Europe/Paris',
            'date_format'                 => 'j F Y',
            'time_format'                 => 'G\hi',
            'start_of_week'               => 1,

            // Commentaires et pings fermés.
            'default_comment_status'      => 'closed',
            'default_ping_status'         => 'closed',
            'default_pingback_flag'       => 0,
            'comment_registration'        => 0,
            'require_name_email'          => 1,
            'thread_comments'             => 0,
            'page_comments'               => 0,
            'comments_notify'             => 0,
            'moderation_notify'           => 0,
            'comment_moderation'          => 1,
            'comment_previously_approved' => 1,
            'show_avatars'                => 0,

            // Lecture.
            'posts_per_page'              => 9,
            'posts_per_rss'               => 9,
            'rss_use_excerpt'             => 1,

            // Inscriptions fermées.
            'users_can_register'          => 0,
            'default_role'                => 'subscriber',

            // Médias rangés par mois.
            'uploads_use_yearmonth_folders' => 1,
        ];

        foreach ($options as $name => $value) {
            update_option($name, $value);
        }

        // Moteurs de recherche : bloqués pendant le développement, ouverts à la mise en ligne.
        update_option('blog_public', $this->opts['noindex'] ? 0 : 1);

        // Permaliens /%postname%/ (les règles sont écrites dans .htaccess à l'étape dédiée).
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');

        $this->log->ok('Fuseau Europe/Paris, formats de date, commentaires et pings fermés, 9 articles par page, inscriptions fermées.');
        $this->log->ok('Permaliens : /%postname%/.');
        if ($this->opts['noindex']) {
            $this->log->warn('Indexation bloquée (site en développement) : penser à la rouvrir à la mise en ligne, dans Réglages › Lecture.');
        } else {
            $this->log->ok('Indexation par les moteurs de recherche autorisée.');
        }
    }

    /* -----------------------------------------------------------------------
     * 5. Contenu par défaut de WordPress
     * -------------------------------------------------------------------- */

    /**
     * Supprime uniquement le contenu de démonstration créé à l'installation, et seulement s'il n'a
     * jamais été modifié : « Bonjour tout le monde », « Page d'exemple », brouillon de politique de
     * confidentialité et commentaire de démo. Le reste du site n'est jamais touché.
     */
    public function cleanup()
    {
        $this->log->section('Contenu par défaut');

        $defaults = [
            ['id' => 1, 'type' => 'post', 'label' => 'article « Bonjour tout le monde »'],
            ['id' => 2, 'type' => 'page', 'label' => 'page d\'exemple'],
            ['id' => 3, 'type' => 'page', 'label' => 'brouillon de politique de confidentialité', 'status' => 'draft'],
        ];

        $deleted = 0;
        foreach ($defaults as $default) {
            $post = get_post($default['id']);
            if (!$post || $post->post_type !== $default['type']) {
                continue;
            }
            if (isset($default['status']) && $post->post_status !== $default['status']) {
                continue;
            }
            if ($post->post_modified_gmt !== $post->post_date_gmt) {
                $this->log->warn('Conservé (modifié depuis l\'installation) : ' . $default['label'] . '.');
                continue;
            }
            if (wp_delete_post($post->ID, true)) {
                $this->log->ok('Supprimé : ' . $default['label'] . '.');
                $deleted++;
            }
        }

        // Commentaire de démonstration (auteur « Un commentateur WordPress »).
        $comment = get_comment(1);
        if ($comment && false !== strpos((string) $comment->comment_author_email, 'wordpress.example')) {
            wp_delete_comment($comment->comment_ID, true);
            $this->log->ok('Supprimé : commentaire de démonstration.');
            $deleted++;
        }

        if (0 === $deleted) {
            $this->log->ok('Aucun contenu par défaut à supprimer.');
        }
    }

    /* -----------------------------------------------------------------------
     * 6. Pages
     * -------------------------------------------------------------------- */

    public function pages()
    {
        $this->log->section('Pages');

        foreach ($this->config['pages'] as $key => $page) {
            $existing = get_page_by_path($page['slug'], OBJECT, 'page');
            if ($existing) {
                $this->pages[$key] = (int) $existing->ID;
                if ('publish' !== $existing->post_status) {
                    wp_update_post(['ID' => $existing->ID, 'post_status' => 'publish']);
                }
                $this->log->ok('Déjà présente : ' . $page['title'] . '.');
                continue;
            }

            $id = wp_insert_post([
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'post_title'     => $page['title'],
                'post_name'      => $page['slug'],
                'post_content'   => '',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ], true);

            if (is_wp_error($id)) {
                $this->log->wp_error($id, $page['title']);
                continue;
            }

            $this->pages[$key] = (int) $id;
            $this->log->ok('Créée : ' . $page['title'] . '.');
        }

        if (!empty($this->pages['accueil'])) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $this->pages['accueil']);
            update_option('page_for_posts', 0);
            $this->log->ok('Page d\'accueil statique : Accueil.');
        }

        if (!empty($this->pages['confidentialite'])) {
            update_option('wp_page_for_privacy_policy', $this->pages['confidentialite']);
            $this->log->ok('Page de confidentialité WordPress : Politique de confidentialité.');
        }
    }

    /* -----------------------------------------------------------------------
     * 7. Thème
     * -------------------------------------------------------------------- */

    public function theme(Flib_Setup_GitHub $github)
    {
        $this->log->section('Thème');

        $theme = $this->config['theme'];

        // Thème parent flib-base (dernière release).
        $release = $github->download_latest_release($theme['repo']);
        if (is_wp_error($release)) {
            $this->log->wp_error($release, 'flib-base');
            return;
        }

        $installed = Flib_Setup_Installer::install_zip($release['file'], 'theme', $theme['slug']);
        if (is_wp_error($installed)) {
            $this->log->wp_error($installed, 'flib-base');
            return;
        }
        $this->log->ok('flib-base ' . $release['version'] . ' installé.');

        $active = $theme['slug'];

        // Thème enfant au nom du site, copié depuis le thème enfant de départ du dépôt.
        if ($this->opts['child_theme']) {
            $child = $this->child_theme($github);
            if ($child) {
                $active = $child;
            }
        }

        $wp_theme = wp_get_theme($active);
        if (!$wp_theme->exists() || $wp_theme->errors()) {
            $this->log->error('Thème « ' . $active . ' » inutilisable : activation annulée.');
            return;
        }

        switch_theme($active);
        $this->log->ok('Thème activé : ' . $wp_theme->get('Name') . '.');

        if ($this->opts['delete_themes']) {
            $this->delete_other_themes();
        }
    }

    /**
     * @return string|false Slug du thème enfant créé.
     */
    private function child_theme(Flib_Setup_GitHub $github)
    {
        $theme = $this->config['theme'];
        $slug  = sanitize_title($this->opts['site_name']);
        if ('' === $slug || $slug === $theme['slug']) {
            $slug = ('' === $slug ? 'flib-client' : $slug . '-enfant');
        }

        if (wp_get_theme($slug)->exists()) {
            $this->log->ok('Thème enfant « ' . $slug . ' » déjà présent : conservé tel quel.');
            return $slug;
        }

        $zip = $github->download_branch($theme['repo'], $theme['child_ref']);
        if (is_wp_error($zip)) {
            $this->log->wp_error($zip, 'Thème enfant');
            return false;
        }

        $dest = Flib_Setup_Installer::install_zip($zip, 'theme', $slug, $theme['child_path']);
        if (is_wp_error($dest)) {
            $this->log->wp_error($dest, 'Thème enfant');
            return false;
        }

        // Renomme le thème dans l'en-tête de style.css.
        $style = $dest . '/style.css';
        if (file_exists($style)) {
            $css = (string) file_get_contents($style);
            $css = preg_replace('/^(\s*Theme Name:).*$/mi', '$1 ' . $this->opts['site_name'], $css, 1);
            $css = preg_replace('/^(\s*Text Domain:).*$/mi', '$1 ' . $slug, $css, 1);
            $css = preg_replace('/^(\s*Description:).*$/mi', '$1 Thème enfant du site ' . $this->opts['site_name'] . ' (charte : theme.json).', $css, 1);
            $css = preg_replace('/^(\s*Version:).*$/mi', '$1 1.0.0', $css, 1);
            file_put_contents($style, $css);
        }

        wp_clean_themes_cache();
        $this->log->ok('Thème enfant « ' . $this->opts['site_name'] . ' » créé (dossier ' . $slug . ').');

        return $slug;
    }

    private function delete_other_themes()
    {
        // Ne supprime que si flib-base est bien le thème actif (parent ou enfant) : il reste toujours un thème valide.
        if (get_template() !== $this->config['theme']['slug']) {
            $this->log->warn('Autres thèmes conservés : flib-base n\'est pas actif.');
            return;
        }

        $keep = [get_stylesheet(), get_template()];
        foreach (wp_get_themes(['errors' => null]) as $stylesheet => $theme) {
            if (in_array($stylesheet, $keep, true)) {
                continue;
            }
            $result = delete_theme($stylesheet);
            if (is_wp_error($result) || !$result) {
                $this->log->warn('Suppression impossible : ' . $theme->get('Name') . '.');
            } else {
                $this->log->ok('Thème supprimé : ' . $theme->get('Name') . '.');
            }
        }
    }

    /* -----------------------------------------------------------------------
     * 8. Extensions
     * -------------------------------------------------------------------- */

    public function plugins(Flib_Setup_GitHub $github)
    {
        $this->log->section('Extensions');

        // Extensions livrées avec WordPress, inutiles sur nos sites.
        $this->delete_bundled_plugins();

        $plugins = $this->config['plugins'];

        foreach ($this->opts['plugins_github'] as $slug) {
            if (empty($plugins['github'][$slug])) {
                continue;
            }
            $plugin = $plugins['github'][$slug];

            $release = $github->download_latest_release($plugin['repo']);
            if (is_wp_error($release)) {
                $this->log->wp_error($release, $plugin['name']);
                continue;
            }

            $installed = Flib_Setup_Installer::install_zip($release['file'], 'plugin', $slug);
            if (is_wp_error($installed)) {
                $this->log->wp_error($installed, $plugin['name']);
                continue;
            }

            $this->activate($slug, $plugin['name'] . ' ' . $release['version']);
        }

        foreach ($this->opts['plugins_wporg'] as $slug) {
            if (empty($plugins['wporg'][$slug])) {
                continue;
            }
            $plugin = $plugins['wporg'][$slug];

            $file = Flib_Setup_Installer::install_wporg_plugin($slug);
            if (is_wp_error($file)) {
                $this->log->wp_error($file, $plugin['name']);
                continue;
            }

            $this->activate($slug, $plugin['name']);
        }

        $this->language_packs();
    }

    /* -----------------------------------------------------------------------
     * 8 bis. Mu-plugins maison (wp-content/mu-plugins)
     * -------------------------------------------------------------------- */

    public function mu_plugins(Flib_Setup_GitHub $github)
    {
        $this->log->section('Mu-plugins');

        $list = $this->config['mu_plugins'] ?? [];

        foreach ($this->opts['mu_plugins'] as $slug) {
            if (empty($list[$slug])) {
                continue;
            }
            $mu = $list[$slug];

            $release = $github->download_latest_release($mu['repo']);
            if (is_wp_error($release)) {
                $this->log->wp_error($release, $mu['name']);
                continue;
            }

            $installed = Flib_Setup_Installer::install_mu_plugin_zip($release['file'], $mu['file']);
            if (is_wp_error($installed)) {
                $this->log->wp_error($installed, $mu['name']);
                continue;
            }

            $this->log->ok($mu['name'] . ' ' . $release['version'] . ' : ' . $mu['file'] . ' copié dans wp-content/mu-plugins (toujours actif, pas de mise à jour automatique).');

            // Même extension installée aussi en classique : signalée (les deux cohabitent sans conflit).
            if (Flib_Setup_Installer::plugin_file($slug)) {
                $this->log->warn($mu['name'] . ' est aussi présente dans wp-content/plugins : une seule des deux suffit.');
            }
        }
    }

    private function activate($slug, $label)
    {
        $file = Flib_Setup_Installer::plugin_file($slug);
        if (!$file) {
            $this->log->error($label . ' : fichier principal introuvable après installation.');
            return;
        }

        $result = Flib_Setup_Installer::activate($file);
        if (is_wp_error($result)) {
            $this->log->wp_error($result, $label . ' installée mais non activée');
            return;
        }

        $this->log->ok($label . ' installée et activée.');
    }

    private function delete_bundled_plugins()
    {
        $bundled = [];
        $names   = [];
        $known   = ['hello.php' => 'Hello Dolly', 'hello-dolly/hello.php' => 'Hello Dolly', 'akismet/akismet.php' => 'Akismet'];
        foreach ($known as $file => $name) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $file) && !is_plugin_active($file)) {
                $bundled[] = $file;
                $names[]   = $name;
            }
        }

        if (empty($bundled)) {
            return;
        }

        $result = delete_plugins($bundled);
        if (is_wp_error($result) || !$result) {
            $this->log->warn('Suppression de Hello Dolly / Akismet impossible.');
        } else {
            $this->log->ok('Supprimées : ' . implode(', ', array_unique($names)) . '.');
        }
    }

    /**
     * Traductions françaises des extensions et thèmes installés.
     */
    private function language_packs()
    {
        if ('fr_FR' !== get_option('WPLANG')) {
            return;
        }

        wp_clean_update_cache();
        wp_update_plugins();
        wp_update_themes();

        $upgrader = new Language_Pack_Upgrader(new Automatic_Upgrader_Skin());
        $result   = $upgrader->bulk_upgrade();

        if (is_array($result) && !empty($result)) {
            $this->log->ok('Traductions françaises des extensions et du thème installées.');
        }
    }

    /* -----------------------------------------------------------------------
     * 9. Menu principal
     * -------------------------------------------------------------------- */

    public function menu()
    {
        $this->log->section('Menu');

        $menu  = $this->config['menu'];
        $items = [];
        foreach ($menu['pages'] as $key) {
            $id = $this->pages[$key] ?? 0;
            if (!$id) {
                $page = get_page_by_path($this->config['pages'][$key]['slug'] ?? '', OBJECT, 'page');
                $id   = $page ? (int) $page->ID : 0;
            }
            if ($id) {
                $items[] = $id;
            }
        }

        if (wp_get_theme()->is_block_theme()) {
            $this->block_navigation($menu['name'], $items);
        } else {
            $this->classic_menu($menu['name'], $items);
        }
    }

    /**
     * Thème en blocs (flib-base) : menu de navigation « wp_navigation », utilisé par le bloc Navigation de l'en-tête.
     */
    private function block_navigation($name, array $page_ids)
    {
        $content = '';
        foreach ($page_ids as $id) {
            $attrs = [
                'label' => get_the_title($id),
                'type'  => 'page',
                'id'    => $id,
                'url'   => get_permalink($id),
                'kind'  => 'post-type',
            ];
            $content .= '<!-- wp:navigation-link ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ' /-->' . "\n";
        }

        $existing = get_posts([
            'post_type'   => 'wp_navigation',
            'title'       => $name,
            'post_status' => 'any',
            'numberposts' => 1,
        ]);

        $data = [
            'post_type'    => 'wp_navigation',
            'post_status'  => 'publish',
            'post_title'   => $name,
            'post_content' => $content,
        ];

        if ($existing) {
            $data['ID'] = $existing[0]->ID;
            $id = wp_update_post(wp_slash($data), true);
        } else {
            $id = wp_insert_post(wp_slash($data), true);
        }

        if (is_wp_error($id)) {
            $this->log->wp_error($id, 'Menu');
            return;
        }

        $this->log->ok('Menu « ' . $name . ' » (' . count($page_ids) . ' liens) prêt pour le bloc Navigation de l\'en-tête.');
    }

    /**
     * Thème classique : menu WordPress rattaché au premier emplacement du thème.
     */
    private function classic_menu($name, array $page_ids)
    {
        $menu    = wp_get_nav_menu_object($name);
        $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($name);

        if (is_wp_error($menu_id)) {
            $this->log->wp_error($menu_id, 'Menu');
            return;
        }

        if (!$menu) {
            foreach ($page_ids as $id) {
                wp_update_nav_menu_item($menu_id, 0, [
                    'menu-item-object-id' => $id,
                    'menu-item-object'    => 'page',
                    'menu-item-type'      => 'post_type',
                    'menu-item-status'    => 'publish',
                ]);
            }
        }

        $locations = get_registered_nav_menus();
        if ($locations) {
            $assigned = get_theme_mod('nav_menu_locations', []);
            $first    = array_key_first($locations);
            if (empty($assigned[$first])) {
                $assigned[$first] = $menu_id;
                set_theme_mod('nav_menu_locations', $assigned);
            }
        }

        $this->log->ok('Menu « ' . $name . ' » créé.');
    }

    /* -----------------------------------------------------------------------
     * 10. .htaccess : règles de réécriture + en-têtes de sécurité
     * -------------------------------------------------------------------- */

    public function htaccess()
    {
        $this->log->section('.htaccess');

        // Écriture « dure » : génère le bloc WordPress dans .htaccess (crée le fichier si besoin).
        flush_rewrite_rules(true);

        $file = get_home_path() . '.htaccess';

        // Certains serveurs ne sont pas détectés comme Apache par WordPress (got_mod_rewrite) : on écrit alors le bloc nous-mêmes.
        global $wp_rewrite;
        if ($wp_rewrite->using_mod_rewrite_permalinks()
            && (!file_exists($file) || false === strpos((string) file_get_contents($file), '# BEGIN WordPress'))) {
            insert_with_markers($file, 'WordPress', explode("\n", $wp_rewrite->mod_rewrite_rules()));
        }

        if (!file_exists($file) || false === strpos((string) file_get_contents($file), '# BEGIN WordPress')) {
            $this->log->warn('Règles de réécriture absentes de .htaccess : enregistrer une fois Réglages › Permaliens.');
        } else {
            $this->log->ok('Règles de réécriture WordPress écrites.');
        }

        if (!$this->opts['security_headers']) {
            return;
        }

        $rules = [
            '<IfModule mod_headers.c>',
            '    Header always set Strict-Transport-Security "max-age=31536000" env=HTTPS',
            '    Header always set X-Content-Type-Options "nosniff"',
            '    Header always set X-Frame-Options "SAMEORIGIN"',
            '    Header always set Referrer-Policy "strict-origin-when-cross-origin"',
            '    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"',
            '    Header always set Content-Security-Policy "upgrade-insecure-requests"',
            '</IfModule>',
        ];

        // Retire le bloc écrit par la v1 (anciens en-têtes, remplacés ci-dessous).
        if (file_exists($file) && is_writable($file)) {
            $contents = (string) file_get_contents($file);
            $cleaned  = preg_replace('/\r?\n?# BEGIN flib_security_headers.*?# END flib_security_headers\r?\n?/s', "\n", $contents);
            if (null !== $cleaned && $cleaned !== $contents) {
                file_put_contents($file, $cleaned);
            }
        }

        if (insert_with_markers($file, 'FLIB en-tetes de securite', $rules)) {
            $this->log->ok('En-têtes de sécurité ajoutés (HSTS, nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy).');
        } else {
            $this->log->warn('.htaccess non modifiable : en-têtes de sécurité non ajoutés.');
        }
    }

    /* -----------------------------------------------------------------------
     * 11. Favicon
     * -------------------------------------------------------------------- */

    public function favicon($zip_upload, $files_upload)
    {
        $this->log->section('Favicon');

        $result = Flib_Setup_Favicon::import($zip_upload, $files_upload, $this->opts['site_name']);
        if (is_wp_error($result)) {
            $this->log->wp_error($result);
            return;
        }

        $this->log->ok('Fichiers copiés à la racine du site : ' . implode(', ', $result) . '.');
        $this->log->ok('Balises ajoutées par le mu-plugin « FLIB - Favicon ».');
    }
}

/* ===========================================================================
 * Contrôleur : contrôles, connexion, formulaire, exécution
 * ======================================================================== */

flib_setup_main();

function flib_setup_main()
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, private');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');

    $root = __DIR__;

    if (!file_exists($root . '/wp-config.php') || !file_exists($root . '/wp-load.php')) {
        // Pas à la racine d'un site WordPress installé : on ne se supprime pas.
        flib_setup_page('Mauvais emplacement', '<p>Ce fichier doit être déposé à la racine d\'un site WordPress installé, à côté de <code>wp-config.php</code>.</p>');
    }

    // filectime = date d'arrivée du fichier sur le serveur (non conservée par un transfert FTP, contrairement à filemtime).
    $age = time() - (int) filectime(__FILE__);
    if ($age > FLIB_SETUP_VALIDITY) {
        flib_setup_self_destruct();
        flib_setup_page('Fichier expiré', '<p>Ce fichier a été déposé il y a plus de 2 heures : il n\'est plus utilisable et vient d\'être supprimé. Le déposer à nouveau si besoin.</p>');
    }

    $url = flib_setup_request_url();
    if ('' === $url) {
        flib_setup_page('Adresse invalide', '<p>Adresse du site non reconnue.</p>');
    }

    // URL lue dans le formulaire (POST) ou adresse courante : WordPress est chargé avec, pour que tout
    // (liens du menu, URL des fichiers) utilise déjà l'URL temporaire. Seulement si wp-config.php ne la définit pas déjà.
    $post_url = isset($_POST['temp_url']) ? flib_setup_clean_url((string) $_POST['temp_url']) : '';
    $boot_url = '' !== $post_url ? $post_url : $url;
    $config_src = (string) file_get_contents($root . '/wp-config.php');
    if (!preg_match('/^\s*define\(\s*[\'"]WP_HOME[\'"]/m', $config_src)) {
        define('WP_HOME', $boot_url);
    }
    if (!preg_match('/^\s*define\(\s*[\'"]WP_SITEURL[\'"]/m', $config_src)) {
        define('WP_SITEURL', $boot_url);
    }

    // Chargement de WordPress en contexte d'administration.
    define('WP_ADMIN', true);
    require_once $root . '/wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/admin.php';

    if (!is_blog_installed()) {
        flib_setup_page('WordPress non installé', '<p>Terminer d\'abord l\'installation de WordPress, puis recharger cette page.</p>');
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    if ('POST' !== $method) {
        flib_setup_page('Setup WordPress', flib_setup_form($url, $age));
    }

    // --- Authentification ---------------------------------------------------
    $tries = (int) get_transient('flib_setup_tries');
    if ($tries >= FLIB_SETUP_MAX_TRY) {
        flib_setup_self_destruct();
        flib_setup_page('Trop d\'essais', '<p>Trop d\'essais de connexion : le fichier a été supprimé par sécurité.</p>');
    }

    $login    = sanitize_user(wp_unslash($_POST['log'] ?? ''));
    $password = (string) wp_unslash($_POST['pwd'] ?? '');
    $user     = wp_authenticate($login, $password);

    if (is_wp_error($user) || !user_can($user, 'manage_options') || !user_can($user, 'install_plugins')) {
        set_transient('flib_setup_tries', $tries + 1, HOUR_IN_SECONDS);
        $left = FLIB_SETUP_MAX_TRY - $tries - 1;
        flib_setup_page('Setup WordPress', '<div class="err">Identifiant ou mot de passe incorrect, ou compte non administrateur. Essais restants : ' . (int) $left . '.</div>' . flib_setup_form($url, $age));
    }

    delete_transient('flib_setup_tries');
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, false, is_ssl());

    // --- Exécution ----------------------------------------------------------
    @set_time_limit(600);
    ignore_user_abort(true);

    $config = flib_setup_config();
    $opts   = flib_setup_read_options($config);
    $log    = new Flib_Setup_Logger();
    $steps  = new Flib_Setup_Steps($log, $config, $opts);
    $github = new Flib_Setup_GitHub($opts['token']);

    $boot        = Flib_Setup_Installer::boot();
    $can_install = !is_wp_error($boot);
    if (!$can_install) {
        $log->section('Préparation');
        $log->wp_error($boot);
    }
    if (($opts['theme'] || !empty($opts['plugins_github']) || !empty($opts['mu_plugins'])) && !$github->has_token()) {
        $log->section('Préparation');
        $log->error('Aucun jeton GitHub : flib-base, les extensions et mu-plugins maison ne seront pas installés.');
    }

    // L'ordre compte : thème avant menu (thème en blocs), extensions avant traductions, .htaccess après permaliens.
    $steps->wp_config();
    $steps->identity();
    if ($opts['settings']) {
        $steps->language();
        $steps->settings();
    } elseif ($opts['noindex']) {
        update_option('blog_public', 0);
    }
    if ($opts['cleanup']) {
        $steps->cleanup();
    }
    if ($opts['pages']) {
        $steps->pages();
    }
    if ($can_install && $opts['theme'] && $github->has_token()) {
        $steps->theme($github);
    }
    if ($can_install && (!empty($opts['plugins_github']) || !empty($opts['plugins_wporg']))) {
        $steps->plugins($github);
    }
    if ($can_install && !empty($opts['mu_plugins']) && $github->has_token()) {
        $steps->mu_plugins($github);
    }
    if ($opts['menu']) {
        $steps->menu();
    }
    if ($can_install && ($opts['settings'] || $opts['security_headers'])) {
        $steps->htaccess();
    }
    if ($can_install && (Flib_Setup_Favicon::has_upload($_FILES['favicon_zip'] ?? null) || Flib_Setup_Favicon::has_upload($_FILES['favicon_files'] ?? null))) {
        $steps->favicon($_FILES['favicon_zip'] ?? null, $_FILES['favicon_files'] ?? null);
    }

    update_option('flib_setup_last_run', current_time('mysql') . ' (flib-setup.php ' . FLIB_SETUP_VERSION . ')', false);

    $deleted = flib_setup_self_destruct();
    $log->section('Fin');
    if ($deleted) {
        $log->ok('flib-setup.php supprimé du serveur.');
    } else {
        $log->error('flib-setup.php n\'a pas pu être supprimé : le supprimer à la main dans le gestionnaire de fichiers.');
    }

    flib_setup_page('Setup terminé', flib_setup_report($log, $opts));
}

/* ---------------------------------------------------------------------------
 * Formulaire
 * ------------------------------------------------------------------------ */

function flib_setup_form($url, $age)
{
    $config  = flib_setup_config();
    $minutes = max(0, (int) floor((FLIB_SETUP_VALIDITY - $age) / 60));
    $token   = defined('FLIB_GITHUB_TOKEN') && FLIB_GITHUB_TOKEN;
    $e       = 'flib_setup_esc';

    ob_start();
    ?>
    <p class="small">Fichier utilisable encore <?php echo (int) $minutes; ?> min, puis il se supprime. Il se supprime aussi à la fin du setup.</p>
    <form method="post" enctype="multipart/form-data" autocomplete="off" onsubmit="var b=this.querySelector('button');b.disabled=true;b.textContent='Setup en cours… (jusqu\'à 2 minutes, ne pas fermer la page)';">

        <h2>Connexion administrateur</h2>
        <div class="row"><label for="log">Identifiant WordPress</label><input id="log" name="log" type="text" required autocomplete="username"></div>
        <div class="row"><label for="pwd">Mot de passe</label><input id="pwd" name="pwd" type="password" required autocomplete="current-password"></div>

        <h2>Site</h2>
        <div class="row"><label for="site_name">Nom du site</label><input id="site_name" name="site_name" type="text" required value="<?php echo $e(get_option('blogname')); ?>">
            <span class="small">Sert aussi à nommer le thème enfant et le manifest.</span></div>
        <div class="row"><label for="temp_url">URL temporaire</label><input id="temp_url" name="temp_url" type="url" value="<?php echo $e($url); ?>">
            <label class="cb"><input type="checkbox" name="write_urls" value="1" checked> Écrire <code>WP_HOME</code> et <code>WP_SITEURL</code> dans wp-config.php (sauvegarde faite avant)</label></div>
        <?php if ('' !== FLIB_SETUP_EMBEDDED_TOKEN) : ?>
        <div class="row"><label>Jeton GitHub</label>
            <span>Intégré au fichier ✔ (aucune saisie nécessaire).</span>
            <label class="cb"><input type="checkbox" name="save_token" value="1" checked> L'enregistrer dans wp-config.php (<code>FLIB_GITHUB_TOKEN</code>, pour les mises à jour du thème et des extensions maison)</label>
            <details><summary class="small">Utiliser un autre jeton</summary><input id="github_token" name="github_token" type="password" autocomplete="off" placeholder="github_pat_…"></details></div>
        <?php else : ?>
        <div class="row"><label for="github_token">Jeton GitHub</label><input id="github_token" name="github_token" type="password" autocomplete="off" placeholder="<?php echo $token ? 'Déjà défini dans wp-config.php' : 'github_pat_…'; ?>">
            <label class="cb"><input type="checkbox" name="save_token" value="1" checked> L'enregistrer dans wp-config.php (<code>FLIB_GITHUB_TOKEN</code>, pour les mises à jour du thème et des extensions maison)</label>
            <span class="small">Jeton <em>fine-grained</em> Flibustiers, <em>Contents : Read-only</em>, avec accès à flib-base et aux extensions maison.<?php echo $token ? ' Laisser vide pour garder le jeton actuel.' : ' Astuce : télécharger le fichier depuis la release GitHub, le jeton y est intégré.'; ?></span></div>
        <?php endif; ?>
        <div class="row"><label>Favicon et manifest</label>
            <span>Archive .zip : <input type="file" name="favicon_zip" accept=".zip,application/zip"></span>
            <span>ou dossier : <input type="file" name="favicon_files[]" webkitdirectory directory multiple></span>
            <span class="small">Export RealFaviconGenerator par exemple (.ico, .png, .svg, .webmanifest). Copiés à la racine, balises ajoutées automatiquement.</span></div>

        <h2>Thème</h2>
        <label class="cb"><input type="checkbox" name="theme" value="1" checked> Installer flib-base depuis GitHub (dernière release)</label>
        <label class="cb"><input type="checkbox" name="child_theme" value="1" checked> Créer et activer un thème enfant au nom du site</label>
        <label class="cb"><input type="checkbox" name="delete_themes" value="1" checked> Supprimer les autres thèmes (Twenty Twenty-…)</label>

        <h2>Extensions maison (GitHub)</h2>
        <?php foreach ($config['plugins']['github'] as $slug => $p) : ?>
            <label class="cb"><input type="checkbox" name="plugins_github[]" value="<?php echo $e($slug); ?>" <?php echo (!isset($p['default']) || $p['default']) ? 'checked' : ''; ?>> <?php echo $e($p['name']); ?> <code><?php echo $e($p['repo']); ?></code></label>
        <?php endforeach; ?>

        <?php if (!empty($config['mu_plugins'])) : ?>
        <h2>Mu-plugins maison (GitHub)</h2>
        <?php foreach ($config['mu_plugins'] as $slug => $p) : ?>
            <label class="cb"><input type="checkbox" name="mu_plugins[]" value="<?php echo $e($slug); ?>" <?php echo (!isset($p['default']) || $p['default']) ? 'checked' : ''; ?>> <?php echo $e($p['name']); ?> <code><?php echo $e($p['repo']); ?></code></label>
        <?php endforeach; ?>
        <span class="small">Copiés dans <code>wp-content/mu-plugins/</code> (dossier créé si absent) : toujours actifs, sans mise à jour automatique.</span>
        <?php endif; ?>

        <h2>Extensions WordPress.org</h2>
        <?php foreach ($config['plugins']['wporg'] as $slug => $p) : ?>
            <label class="cb"><input type="checkbox" name="plugins_wporg[]" value="<?php echo $e($slug); ?>" <?php echo (!isset($p['default']) || $p['default']) ? 'checked' : ''; ?>> <?php echo $e($p['name']); ?> <code><?php echo $e($slug); ?></code></label>
        <?php endforeach; ?>
        <span class="small">Hello Dolly et Akismet sont supprimés.</span>

        <h2>Réglages et contenu</h2>
        <label class="cb"><input type="checkbox" name="settings" value="1" checked> Réglages de base (français, fuseau Paris, formats de date, commentaires fermés, permaliens /%postname%/…)</label>
        <label class="cb"><input type="checkbox" name="noindex" value="1" checked> Bloquer l'indexation par les moteurs de recherche (site en développement)</label>
        <label class="cb"><input type="checkbox" name="cleanup" value="1" checked> Supprimer le contenu par défaut (Bonjour tout le monde, page d'exemple, commentaire de démo)</label>
        <label class="cb"><input type="checkbox" name="pages" value="1" checked> Créer les pages : <?php echo $e(implode(', ', array_column($config['pages'], 'title'))); ?></label>
        <label class="cb"><input type="checkbox" name="menu" value="1" checked> Créer le menu principal</label>
        <label class="cb"><input type="checkbox" name="security_headers" value="1" checked> Ajouter les en-têtes de sécurité dans .htaccess</label>

        <p><button type="submit">Lancer le setup</button></p>
    </form>
    <?php
    return (string) ob_get_clean();
}

function flib_setup_report(Flib_Setup_Logger $log, array $opts)
{
    $icons   = ['ok' => '✔', 'warn' => '⚠', 'error' => '✖'];
    $section = null;
    $html    = $log->has_errors()
        ? '<div class="err">Setup terminé avec des erreurs : voir les lignes ✖ ci-dessous.</div>'
        : '<div class="okbox">Setup terminé sans erreur.</div>';

    foreach ($log->entries() as $entry) {
        if ($entry['section'] !== $section) {
            $section = $entry['section'];
            $html   .= '<h2>' . flib_setup_esc($section) . '</h2>';
        }
        $html .= '<div class="line ' . flib_setup_esc($entry['status']) . '"><b>' . ($icons[$entry['status']] ?? '•') . '</b> ' . flib_setup_esc($entry['message']) . '</div>';
    }

    $admin = admin_url();
    $html .= '<p style="margin-top:24px"><a class="btn" href="' . flib_setup_esc($admin) . '">Aller à l\'administration</a></p>';
    $html .= '<p class="small">Conserver ce compte rendu si besoin (capture ou impression) : il n\'est plus accessible une fois la page fermée.</p>';

    return $html;
}

/* ---------------------------------------------------------------------------
 * Utilitaires
 * ------------------------------------------------------------------------ */

function flib_setup_read_options(array $config)
{
    $post = wp_unslash($_POST);

    $site_name = sanitize_text_field($post['site_name'] ?? '');
    if ('' === $site_name) {
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    }

    $token_input = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($post['github_token'] ?? ''));
    // Priorité : jeton saisi > jeton intégré au fichier > jeton déjà présent dans wp-config.php.
    $token_to_save = '' !== $token_input ? $token_input : FLIB_SETUP_EMBEDDED_TOKEN;
    $token         = '' !== $token_to_save ? $token_to_save : (defined('FLIB_GITHUB_TOKEN') ? (string) FLIB_GITHUB_TOKEN : '');

    $flag = static function ($key) use ($post) {
        return !empty($post[$key]);
    };
    $list = static function ($key, array $allowed) use ($post) {
        $values = array_map('sanitize_key', (array) ($post[$key] ?? []));
        return array_values(array_intersect($values, array_keys($allowed)));
    };

    return [
        'site_name'        => $site_name,
        'temp_url'         => flib_setup_clean_url($post['temp_url'] ?? ''),
        'write_urls'       => $flag('write_urls'),
        'token_to_save'    => $token_to_save,
        'token'            => $token,
        'save_token'       => $flag('save_token'),
        'theme'            => $flag('theme'),
        'child_theme'      => $flag('child_theme'),
        'delete_themes'    => $flag('delete_themes'),
        'plugins_github'   => $list('plugins_github', $config['plugins']['github']),
        'plugins_wporg'    => $list('plugins_wporg', $config['plugins']['wporg']),
        'mu_plugins'       => $list('mu_plugins', $config['mu_plugins'] ?? []),
        'settings'         => $flag('settings'),
        'noindex'          => $flag('noindex'),
        'cleanup'          => $flag('cleanup'),
        'pages'            => $flag('pages'),
        'menu'             => $flag('menu'),
        'security_headers' => $flag('security_headers'),
    ];
}

/** URL de site valide (http/https, sans slash final) ou chaîne vide. Utilisable avant le chargement de WordPress. */
function flib_setup_clean_url($url)
{
    $parts = parse_url(trim((string) $url));
    if (empty($parts['scheme']) || empty($parts['host'])
        || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        || !preg_match('/^[a-z0-9.-]+$/i', $parts['host'])) {
        return '';
    }

    $clean = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
    if (!empty($parts['port'])) {
        $clean .= ':' . (int) $parts['port'];
    }
    if (!empty($parts['path']) && '/' !== $parts['path']) {
        $path = trim($parts['path'], '/');
        if (preg_match('#^[a-z0-9._~/-]+$#i', $path)) {
            $clean .= '/' . $path;
        }
    }

    return $clean;
}

/** Adresse du site telle qu'elle est consultée en ce moment (dossier de ce fichier). */
function flib_setup_request_url()
{
    $https = (!empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']))
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']))
        || (isset($_SERVER['SERVER_PORT']) && 443 === (int) $_SERVER['SERVER_PORT']);
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $path = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

    if (!preg_match('/^[a-z0-9.-]+(:\d+)?$/', $host)) {
        return '';
    }

    return flib_setup_clean_url(($https ? 'https://' : 'http://') . $host . $path);
}

function flib_setup_self_destruct()
{
    return @unlink(__FILE__) || !file_exists(__FILE__);
}

function flib_setup_esc($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function flib_setup_page($title, $body)
{
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow"><title>' . flib_setup_esc($title) . ' — FLIB Setup</title><style>'
        . 'body{font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;background:#f0f0f1;margin:0;padding:32px 16px;color:#1d2327}'
        . 'main{max-width:760px;margin:auto;background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:24px 32px}'
        . 'h1{font-size:22px;margin:0 0 4px}h1 small{font-size:13px;color:#646970;font-weight:400}h2{font-size:16px;margin:24px 0 8px;padding-top:12px;border-top:1px solid #f0f0f1}'
        . '.row{display:flex;flex-direction:column;gap:4px;margin:10px 0}.row>label:first-child{font-weight:600}input[type=text],input[type=url],input[type=password]{font-size:15px;padding:7px 10px;border:1px solid #8c8f94;border-radius:4px;max-width:480px}'
        . '.cb{display:block;margin:6px 0}code{background:#f0f0f1;padding:1px 5px;border-radius:3px;font-size:13px}.small{color:#646970;font-size:13px}'
        . 'button,.btn{display:inline-block;background:#2271b1;color:#fff;border:0;border-radius:4px;padding:11px 20px;font-size:15px;text-decoration:none;cursor:pointer}button:disabled{background:#8c8f94}'
        . '.err{background:#fcf0f1;border-left:4px solid #d63638;padding:10px 14px;margin:12px 0}.okbox{background:#edfaef;border-left:4px solid #00a32a;padding:10px 14px;margin:12px 0}'
        . '.line{margin:3px 0}.line.ok b{color:#00a32a}.line.warn b{color:#dba617}.line.error b{color:#d63638}'
        . '</style></head><body><main><h1>' . flib_setup_esc($title) . ' <small>FLIB Setup ' . FLIB_SETUP_VERSION . '</small></h1>' . $body . '</main></body></html>';
    exit;
}
