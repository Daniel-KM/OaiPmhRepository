<?php
/**
 * Config form include
 *
 * Included in the configuration page for the plugin to change settings.
 *
 * @package OaiPmhRepository
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009-2014 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @var Omeka_View $this
 */
?>

<fieldset id="fieldset-oaipmhrepository-identification"><legend><?php echo __('Identification'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_base_url',
            __('Repository base url')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Base URL for this OAI-PMH repository.');
            echo ' ' . __('Default is "oai-pmh-repository/request" (always available), but it can be "oai/request" or simply "oai-pmh".'); ?>
        </p>
        <p class="explanation">
            <?php echo __('Currently, harvesters can access metadata from this url: %s.', sprintf('<a href="%s">%s</a>', OAI_PMH_BASE_URL, OAI_PMH_BASE_URL)); ?>
        </p>
        <?php echo $this->formText('oaipmh_repository_base_url', get_option('oaipmh_repository_base_url')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_name',
            __('Repository name')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Name for this OAI-PMH repository.'); ?>
        </p>
        <?php echo $this->formText('oaipmh_repository_name', get_option('oaipmh_repository_name')); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_namespace_id',
            __('Namespace identifier')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('This will be used to form globally unique IDs for the exposed metadata items.');
            echo ' ' . __('This value is required to be a domain name you have registered.');
            echo ' ' . __('Using other values will generate invalid identifiers.'); ?>
        </p>
        <?php echo $this->formText('oaipmh_repository_namespace_id', get_option('oaipmh_repository_namespace_id')); ?>
    </div>
</div>
</fieldset>

<fieldset id="fieldset-oaipmhrepository-expose"><legend><?php echo __('Exposition'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_expose_set',
            __('Expose sets')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo $this->formRadio('oaipmh_repository_expose_set',
            get_option('oaipmh_repository_expose_set'),
            null,
            array(
                'none' => __('None'),
                'itemset' => __('Collections'),
                'itemtype' => __('Item types'),
                'dctype' => __('Dublin Core types'),
                'itemset_itemtype' => __('Collections and Item types'),
                'itemset_dctype' => __('Collections and Dublin Core types'),
            )); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_expose_files',
            _('Expose files')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Whether the plugin should include identifiers for the files associated with items.');
            echo __('This provides harvesters with direct access to files.'); ?>
        </p>
        <?php echo $this->formCheckbox(
            'oaipmh_repository_expose_files',
            true,
            array('checked' => (bool) get_option('oaipmh_repository_expose_files'))
        ); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_expose_empty_collections',
            __('Expose empty collections')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Whether the plugin should expose empty public collections.'); ?>
        </p>
        <?php echo $this->formCheckbox(
            'oaipmh_repository_expose_empty_collections',
            true,
            array('checked' => (bool) get_option('oaipmh_repository_expose_empty_collections'))
        ); ?>
     </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_expose_item_type',
            __('Expose item type')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Whether the plugin should expose the item type as Dublin Core Type.'); ?>
        </p>
        <?php echo $this->formCheckbox(
            'oaipmh_repository_expose_item_type',
            true,
            array('checked' => (bool) get_option('oaipmh_repository_expose_item_type'))
        ); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_expose_thumbnail',
            __('Expose thumbnail')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('The thumbnail is exposed as dc:relation.'); ?>
        </p>
        <?php echo $this->formCheckbox(
            'oaipmh_repository_expose_thumbnail',
            true,
            array('checked' => (bool) get_option('oaipmh_repository_expose_thumbnail'))
        ); ?>
    </div>
</div>
</fieldset>

<fieldset id="fieldset-oaipmhrepository-identifiers"><legend><?php echo __('Set identifiers'); ?></legend>
<p class="explanation">
    <?php echo __('The oai sets are identified with a unique identifier, that must be different between different types of sets.'); ?>
    <?php echo __('So, check if they are no duplicate between names.'); ?>
    <?php echo __('Furthermore, only some characters are allowed. Forbidden names will be skipped.'); ?>
    <?php echo __('Diacritics are removed in the names, but they can be used if they are managed automatically by the database and php.'); ?>
</p>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_identifier_format',
            __('Format of the set identifiers')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('When the format is hierarchic, all oai set identifiers are prefixed with the type (item set, item type or dctype).'); ?>
            <?php echo __('When the format is flat, all oai set identifiers are simply listed and mixed.'); ?>
        </p>
        <?php echo $this->formRadio('oaipmh_repository_identifier_format',
            get_option('oaipmh_repository_identifier_format'),
            null,
            array(
                'tree' => __('Hierachic'),
                'flat' => __('Flat'),
        )); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_identifier_itemset',
            __('Set identifier from collection')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo $this->formRadio('oaipmh_repository_identifier_itemset',
            get_option('oaipmh_repository_identifier_itemset'),
            null,
            array(
                'itemset_id' => __('itemset_id'),
                'itemset_identifier' => __('First Dublin Core identifier'),
                'itemset_title' => __('First Dublin Core title'),
        )); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_identifier_itemtype',
            __('Set identifier from item type')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <?php echo $this->formRadio('oaipmh_repository_identifier_itemtype',
            get_option('oaipmh_repository_identifier_itemtype'),
            null,
            array(
                'itemtype_id' => __('itemtype_id'),
                'itemtype_name' => __('Item type name'),
        )); ?>
    </div>
</div>
</fieldset>

<fieldset id="fieldset-oaipmhrepository-custom"><legend><?php echo __('Custom'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_custom_oai_dc',
            __('Output custom metadata for oai_dc')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Apply the custom oai_dc output.'); ?>
            <?php echo __('By default, it follows the recommandations of the %sEuropeana digital library%s and the %sBibliothèque nationale de France%s.',
                '<a href="https://pro.europeana.eu/resources/apis/oai-pmh-service">', '</a>',
                '<a href="http://www.BnF.fr/documents/Guide_oaipmh.pdf">', '</a>'
            ); ?>
            <?php echo __('The files "data/oaidc_custom.php" may need to be customized according to your data.'); ?>
        </p>
        <?php echo $this->formCheckbox(
            'oaipmh_repository_custom_oai_dc',
            true,
            array('checked' => (bool) get_option('oaipmh_repository_custom_oai_dc'))
        ); ?>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_custom_default_language',
            __('Default language for custom metadata')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('This three letters language (ISO 639-2b) allows to define the default language of metadata in order to translate them.'); ?>
            <?php echo __('This option is used only to normalize the custom metadata.'); ?>
        </p>
        <?php echo $this->formText('oaipmh_repository_custom_default_language', get_option('oaipmh_repository_custom_default_language')); ?>
    </div>
</div>
</fieldset>

<fieldset id="fieldset-oaipmhrepository-inteface"><legend><?php echo __('Interface'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel(
            'oaipmh_repository_add_human_stylesheet',
            __('Human display')
        ); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __(
                'If checked, a stylesheet will be added to the output, so humans will be able to browse the repository through a themable %sBootstrap%s responsive interface.',
                '<a href="https://getbootstrap.com/">',
                '</a>'
            ); ?>
        </p>
        <?php echo $this->formCheckbox(
            'oaipmh_repository_add_human_stylesheet',
            true,
            array('checked' => (bool) get_option('oaipmh_repository_add_human_stylesheet'))
        ); ?>
    </div>
</div>
</fieldset>
