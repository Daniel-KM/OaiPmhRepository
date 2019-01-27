<?php
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @package OaiPmhRepository
 */

define(
    'OAI_PMH_BASE_URL',
    WEB_ROOT . '/' . (($baseUrl = get_option('oaipmh_repository_base_url'))
        ? $baseUrl
        : 'oai-pmh-repository/request')
);
define('OAI_PMH_REPOSITORY_PLUGIN_DIRECTORY', dirname(__FILE__));
define('OAI_PMH_REPOSITORY_METADATA_DIRECTORY', OAI_PMH_REPOSITORY_PLUGIN_DIRECTORY . '/metadata');

/**
 * OaiPmhRepository plugin class
 *
 * @package OaiPmhRepository
 */
class OaiPmhRepositoryPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'upgrade',
        'uninstall',
        'config_form',
        'config',
        'define_routes',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        'admin_dashboard_panels',
        'oai_pmh_repository_metadata_formats',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'oaipmh_repository_base_url' => 'oai-pmh-repository/request',
        'oaipmh_repository_name' => 'omeka',
        'oaipmh_repository_namespace_id' => 'default.must.change',
        'oaipmh_repository_expose_set' => 'itemset',
        'oaipmh_repository_expose_files' => true,
        'oaipmh_repository_expose_empty_collections' => true,
        'oaipmh_repository_expose_item_type' => false,
        'oaipmh_repository_expose_thumbnail' => false,
        'oaipmh_repository_identifier_itemset' => 'itemset_id',
        'oaipmh_repository_identifier_itemtype' => 'itemtype_id',
        'oaipmh_repository_custom_oai_dc' => false,
        'oaipmh_repository_custom_default_language' => '',
        'oaipmh_repository_add_human_stylesheet' => true,
    );

    /**
     * OaiPmhRepository install hook.
     */
    public function hookInstall()
    {
        $this->_options['oaipmh_repository_name'] = get_option('site_title');
        $this->_options['oaipmh_repository_namespace_id'] = $this->_getServerName();
        $this->_installOptions();

        $db = get_db();
        /* Table: Stores currently active resumptionTokens

           id: primary key (also the value of the token)
           verb: Verb of original request
           metadata_prefix: metadataPrefix of original request
           cursor: Position of cursor within result set
           from: Optional from argument of original request
           until: Optional until argument of original request
           set: Optional set argument of original request
           expiration: Datestamp after which token is expired
        */
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->prefix}oai_pmh_repository_tokens` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `verb` ENUM('ListIdentifiers', 'ListRecords', 'ListSets') COLLATE utf8_unicode_ci NOT NULL,
            `metadata_prefix` TEXT COLLATE utf8_unicode_ci NOT NULL,
            `cursor` INT(10) UNSIGNED NOT NULL,
            `from` DATETIME DEFAULT NULL,
            `until` DATETIME DEFAULT NULL,
            `set` VARCHAR(190) NULL,
            `expiration` DATETIME NOT NULL,
            PRIMARY KEY  (`id`),
            INDEX(`expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ";
        $db->query($sql);
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $db = $this->_db;

        if (version_compare($oldVersion, '2.3.0', '<')) {
            $sql = "ALTER TABLE `{$db->prefix}oai_pmh_repository_tokens` CHANGE `set` `set` VARCHAR(190) NULL AFTER `until`;";
            $db->query($sql);
            $sql = "UPDATE `{$db->prefix}oai_pmh_repository_tokens` SET `set` = CONCAT('itemset_', `set`);";
            $db->query($sql);
        }
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        // Remove potential leftover options moved to config file
        delete_option('oaipmh_repository_record_limit');
        delete_option('oaipmh_repository_expiration_time');

        $this->_uninstallOptions();

        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}oai_pmh_repository_tokens`;";
        $db->query($sql);
    }

    /**
     * Display the config form.
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial(
            'plugins/oai-pmh-repository-config-form.php'
        );
    }

    /**
     * Handle the config form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        $post = array_intersect_key($post, $this->_options);
        foreach ($post as $key => $value) {
           set_option($key, $value);
        }
    }

    /**
     * Define routes.
     *
     * @param Zend_Controller_Router_Rewrite $router
     */
    public function hookDefineRoutes($args)
    {
        if (is_admin_theme()) {
            return;
        }

        // If base url is not set, use the default module/controller/action.
        $route = get_option('oaipmh_repository_base_url');
        if (empty($route) || $route == 'oai-pmh-repository/request') {
            return;
        }

        $args['router']->addRoute(
            'oai-pmh-repository',
            new Zend_Controller_Router_Route(
                $route,
                array(
                    'module' => 'oai-pmh-repository',
                    'controller' => 'request',
                    'action' => 'index',
                )
            )
        );
    }

    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Filter to add a dashboard panel.
     *
     * @param array $panels
     * @return array
     */
    public function filterAdminDashboardPanels($panels)
    {
        $html = '<h2>' . __('OAI-PMH Repository') . '</h2>';
        $html .= '<p>' . __('Harvester can access metadata from this site: %s.', sprintf('<a href="%s">%s</a>', OAI_PMH_BASE_URL, OAI_PMH_BASE_URL)) . '</p>';
        $panels[] = $html;
        return $panels;
    }

    /**
     * Filter to customize OAI-PMH repository metadata formats.
     *
     * @param array $formats
     * @return array
     */
    public function filterOaiPmhRepositoryMetadataFormats($formats)
    {
        if (get_option('oaipmh_repository_custom_oai_dc')) {
            $formats['oai_dc']['class'] = 'OaiPmhRepository_Metadata_OaiDcCustom';
        }
        return $formats;
    }

    private function _getServerName()
    {
        $serverName = preg_replace('~(?:\w+://)?([^:]+)(?::\d*)?$~', '$1', $_SERVER['SERVER_NAME']);

        $name = preg_replace('/[^a-z0-9\-\.]/i', '', $serverName);
        if (empty($name) || $name === 'localhost') {
            $name = 'default.must.change';
        }

        return $name;
    }
}
