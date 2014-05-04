<?php

class HelperForm
{
    public $currentIndex;
    public $table = 'configuration';
    public $identifier;
    public $token;
    public $toolbar_btn;
    public $ps_help_context;
    public $show_toolbar = true;
    public $context;
    public $toolbar_scroll = false;

    /**
     * @var Module
     */
    public $module;

    /** @var string Helper tpl folder */
    public $base_folder;

    /** @var string Controller tpl folder */
    public $override_folder;

    /**
     * @var smartyTemplate base template object
     */
    protected $tpl;

    /**
     * @var string base template name
     */
    public $base_tpl = 'content.tpl';

    public $tpl_vars = array();

    public $id;
    public $first_call = true;

    /** @var array of forms fields */
    protected $fields_form = array();

    /** @var array values of form fields */
    public $fields_value = array();
    public $name_controller = '';

    /** @var string if not null, a title will be added on that list */
    public $title = null;

    /** @var string Used to override default 'submitAdd' parameter in form action attribute */
    public $submit_action;

    public $languages = null;
    public $default_form_language = null;
    public $allow_employee_form_lang = null;

    public function __construct()
    {
        $this->base_folder = __DIR__.'/';
        $this->base_tpl = 'form.tpl';
        $this->context = Context::getContext();
    }

    public function generateForm($fields_form)
    {
        $this->fields_form = $fields_form;
        return $this->generate();
    }

    public function generate()
    {
        $this->tpl = $this->context->smarty->createTemplate($this->base_folder.$this->base_tpl, $this->context->smarty);
        if (is_null($this->submit_action))
            $this->submit_action = 'submitAdd'.$this->table;

        $this->tpl->assign(array(
            'title' => $this->title,
            'toolbar_btn' => $this->toolbar_btn,
            'show_toolbar' => $this->show_toolbar,
            'toolbar_scroll' => $this->toolbar_scroll,
            'submit_action' => $this->submit_action,
            'firstCall' => $this->first_call,
            'current' => $this->currentIndex,
            'token' => $this->token,
            'table' => $this->table,
            'identifier' => $this->identifier,
            'name_controller' => $this->name_controller,
            'languages' => $this->languages,
            'defaultFormLanguage' => $this->default_form_language,
            'allowEmployeeFormLang' => $this->allow_employee_form_lang,
            'form_id' => $this->id,
            'fields' => $this->fields_form,
            'fields_value' => $this->fields_value,
            'required_fields' => $this->getFieldsRequired(),
            'vat_number' => file_exists(_PS_MODULE_DIR_.'vatnumber/ajax.php'),
            'module_dir' => _MODULE_DIR_,
            'contains_states' => (isset($this->fields_value['id_country']) && isset($this->fields_value['id_state'])) ? Country::containsStates($this->fields_value['id_country']) : null,
        ));
        $this->tpl->assign($this->tpl_vars);
        return $this->tpl->fetch();
    }

    /**
     * Return true if there are required fields
     */
    public function getFieldsRequired()
    {
        foreach ($this->fields_form as $fieldset)
            if (isset($fieldset['form']['input']))
                foreach ($fieldset['form']['input'] as $input)
                    if (array_key_exists('required', $input) && $input['required'] && $input['type'] != 'radio')
                        return true;

        return false;
    }
} 