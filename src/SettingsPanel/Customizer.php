<?php
namespace Webilia\WP\SettingsPanel;

use \WP_Customize_Control;
use \WP_Customize_Manager;

class Customizer extends \WP_Customize_Control
{
    /** @var string */
    public $type = 'webilia_wp_settings_panel';
    /** @var array<mixed> */
    protected $field = [];
    /** @var string */
    protected $field_id = '';
    /** @var string */
    protected $option_name = '';
    /** @var string */
    protected $field_base_name = '';

    /**
     * @param mixed $manager
     * @param mixed $id
     * @param mixed $args
     */
    public function __construct($manager, $id, $args = [])
    {
        $this->field = $args['field'] ?? [];
        $this->field_id = $args['field_id'] ?? '';
        $this->option_name = $args['option_name'] ?? '';
        $this->field_base_name = $args['field_base_name'] ?? '';
        unset($args['field'], $args['field_id'], $args['option_name'], $args['field_base_name']);
        parent::__construct($manager, $id, $args);
    }

    /**
     * @return mixed
     */
    public function render_content()
    {
        $field = $this->field;
        if (!$field || !is_array($field)) return;

        $type  = strtolower(trim((string) ($field['type'] ?? '')));
        $title = ($type === 'info' || $type === 'divide' || $type === 'raw' || $type === 'content') ? '' : ($field['title'] ?? '');

        $field['id']    = $this->field_id ?: ($field['id'] ?? '');
        $field['value'] = $this->value();

        // This is the key: Customizer binding attribute
        $field['customizer_link_attr'] = $this->get_link();
        $field['customizer_setting_id'] = $this->id;

        echo '<div class="wui wui-sp-root"><div class="wui wui-sp-customizer-control wui-sp-customizer-control-type-' .
            esc_attr(sanitize_html_class($type)) . '">';

        if ($title) echo '<span class="customize-control-title">' . esc_html($title) . '</span>';

        if ($this->option_name !== '') echo Panel::render_customizer_field($field, $this->option_name);

        echo '</div></div>';
    }

    /**
     * @return mixed
     */
    public function json()
    {
        $json = parent::json();
        $json['field_base_name'] = $this->field_base_name;
        $json['field_type'] = $this->params['field_type'] ?? '';
        $json['field_id'] = $this->field_id;
        $json['option_name'] = $this->option_name;
        return $json;
    }
}
