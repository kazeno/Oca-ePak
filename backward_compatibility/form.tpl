{*
 * @author: Rinku Kazeno <development@kazeno.co>
 * Based on the Prestashop 1.5.6.0 form.tpl
*}

{if isset($fields.title)}<h2>{$fields.title}</h2>{/if}
{block name="defaultForm"}
<form id="{if isset($fields.form.form.id_form)}{$fields.form.form.id_form|escape:'htmlall':'UTF-8'}{else}{if $table == null}configuration_form{else}{$table}_form{/if}{/if}" class="defaultForm {$name_controller}" action="{$current}&{if !empty($submit_action)}{$submit_action}=1{/if}&token={$token}" method="post" enctype="multipart/form-data" {if isset($style)}style="{$style}"{/if}>
    {if $form_id}
        <input type="hidden" name="{$identifier}" id="{$identifier}" value="{$form_id}" />
    {/if}
    {foreach $fields as $f => $fieldset}
        <fieldset id="fieldset_{$f}">
            {foreach $fieldset.form as $key => $field}
                {if $key == 'legend'}
                    <legend>
                        {if isset($field.image)}<img src="{$field.image}" alt="{$field.title|escape:'htmlall':'UTF-8'}" />{/if}
                        {$field.title}
                    </legend>
                {elseif $key == 'description' && $field}
                    <p class="description">{$field}</p>
                {elseif $key == 'input'}
                    {foreach $field as $input}
                        {if $input.type == 'hidden'}
                            <input type="hidden" name="{$input.name}" id="{$input.name}" value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
                        {else}
                            {block name="label"}
                                {if isset($input.label)}<label>{$input.label} </label>{/if}
                            {/block}
                            {block name="field"}
                                <div class="margin-form">
                                {block name="input"}
                                {if $input.type == 'text'}
                                    {assign var='value_text' value=$fields_value[$input.name]}
                                    <input type="text"
                                           name="{$input.name}"
                                           id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
                                           value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'htmlall':'UTF-8'}{else}{$value_text|escape:'htmlall':'UTF-8'}{/if}"
                                           class="{if $input.type == 'tags'}tagify {/if}{if isset($input.class)}{$input.class}{/if}"
                                           {if isset($input.size)}size="{$input.size}"{/if}
                                            {if isset($input.maxlength)}maxlength="{$input.maxlength}"{/if}
                                            {if isset($input.class)}class="{$input.class}"{/if}
                                            {if isset($input.readonly) && $input.readonly}readonly="readonly"{/if}
                                            {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
                                            {if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
                                    {if isset($input.suffix)}{$input.suffix}{/if}
                                    {if !empty($input.hint)}<span class="hint" name="help_box">{$input.hint}<span class="hint-pointer">&nbsp;</span></span>{/if}
                                {elseif $input.type == 'select'}
                                    {if isset($input.options.query) && !$input.options.query && isset($input.empty_message)}
                                        {$input.empty_message}
                                        {$input.required = false}
                                        {$input.desc = null}
                                    {else}
                                        <select name="{$input.name}" class="{if isset($input.class)}{$input.class}{/if}"
                                                id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
                                                {if isset($input.multiple)}multiple="multiple" {/if}
                                                {if isset($input.size)}size="{$input.size}"{/if}
                                                {if isset($input.onchange)}onchange="{$input.onchange}"{/if}>
                                            {if isset($input.options.default)}
                                                <option value="{$input.options.default.value}">{$input.options.default.label}</option>
                                            {/if}
                                            {if isset($input.options.optiongroup)}
                                                {foreach $input.options.optiongroup.query AS $optiongroup}
                                                    <optgroup label="{$optiongroup[$input.options.optiongroup.label]}">
                                                        {foreach $optiongroup[$input.options.options.query] as $option}
                                                            <option value="{$option[$input.options.options.id]}"
                                                                    {if isset($input.multiple)}
                                                                        {foreach $fields_value[$input.name] as $field_value}
                                                                            {if $field_value == $option[$input.options.options.id]}selected="selected"{/if}
                                                                        {/foreach}
                                                                    {else}
                                                                        {if $fields_value[$input.name] == $option[$input.options.options.id]}selected="selected"{/if}
                                                                    {/if}
                                                                    >{$option[$input.options.options.name]}</option>
                                                        {/foreach}
                                                    </optgroup>
                                                {/foreach}
                                            {else}
                                                {foreach $input.options.query AS $option}
                                                    {if is_object($option)}
                                                        <option value="{$option->$input.options.id}"
                                                                {if isset($input.multiple)}
                                                                    {foreach $fields_value[$input.name] as $field_value}
                                                                        {if $field_value == $option->$input.options.id}
                                                                            selected="selected"
                                                                        {/if}
                                                                    {/foreach}
                                                                {else}
                                                                    {if $fields_value[$input.name] == $option->$input.options.id}
                                                                        selected="selected"
                                                                    {/if}
                                                                {/if}
                                                                >{$option->$input.options.name}</option>
                                                    {elseif $option == "-"}
                                                        <option value="">--</option>
                                                    {else}
                                                        <option value="{$option[$input.options.id]}"
                                                                {if isset($input.multiple)}
                                                                    {foreach $fields_value[$input.name] as $field_value}
                                                                        {if $field_value == $option[$input.options.id]}
                                                                            selected="selected"
                                                                        {/if}
                                                                    {/foreach}
                                                                {else}
                                                                    {if $fields_value[$input.name] == $option[$input.options.id]}
                                                                        selected="selected"
                                                                    {/if}
                                                                {/if}
                                                                >{$option[$input.options.name]}</option>

                                                    {/if}
                                                {/foreach}
                                            {/if}
                                        </select>
                                        {if !empty($input.hint)}<span class="hint" name="help_box">{$input.hint}<span class="hint-pointer">&nbsp;</span></span>{/if}
                                    {/if}
                                {elseif $input.type == 'radio'}
                                    {foreach $input.values as $value}
                                        <input type="radio" name="{$input.name}" id="{$value.id}" value="{$value.value|escape:'htmlall':'UTF-8'}"
                                               {if $fields_value[$input.name] == $value.value}checked="checked"{/if}
                                                {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if} />
                                        <label {if isset($input.class)}class="{$input.class}"{/if} for="{$value.id}">
                                            {if isset($input.is_bool) && $input.is_bool == true}
                                                {if $value.value == 1}
                                                    <img src="../img/admin/enabled.gif" alt="{$value.label}" title="{$value.label}" />
                                                {else}
                                                    <img src="../img/admin/disabled.gif" alt="{$value.label}" title="{$value.label}" />
                                                {/if}
                                            {else}
                                                {$value.label}
                                            {/if}
                                        </label>
                                        {if isset($input.br) && $input.br}<br />{/if}
                                        {if isset($value.p) && $value.p}<p>{$value.p}</p>{/if}
                                    {/foreach}
                                {elseif $input.type == 'textarea'}
                                        <textarea name="{$input.name}" id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}" cols="{$input.cols}" rows="{$input.rows}" {if isset($input.autoload_rte) && $input.autoload_rte}class="rte autoload_rte {if isset($input.class)}{$input.class}{/if}"{/if}>{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}</textarea>
                                {elseif $input.type == 'checkbox'}
                                    {foreach $input.values.query as $value}
                                        {assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]}
                                        <input type="checkbox"
                                               name="{$id_checkbox}"
                                               id="{$id_checkbox}"
                                               class="{if isset($input.class)}{$input.class}{/if}"
                                               {if isset($value.val)}value="{$value.val|escape:'htmlall':'UTF-8'}"{/if}
                                                {if isset($fields_value[$id_checkbox]) && $fields_value[$id_checkbox]}checked="checked"{/if} />
                                        <label for="{$id_checkbox}" class="t"><strong>{$value[$input.values.name]}</strong></label><br />
                                    {/foreach}
                                {elseif $input.type == 'password'}
                                    <input type="password"
                                           name="{$input.name}"
                                           size="{$input.size}"
                                           class="{if isset($input.class)}{$input.class}{/if}"
                                           value=""
                                           {if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
                                {elseif $input.type == 'color'}
                                    <input type="color"
                                           size="{$input.size}"
                                           data-hex="true"
                                           {if isset($input.class)}class="{$input.class}"
                                           {else}class="color mColorPickerInput"{/if}
                                           name="{$input.name}"
                                           value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
                                    <script src="../js/jquery/jquery-colorpicker.js"></script>
                                {elseif $input.type == 'date'}
                                    <input type="text"
                                           size="{$input.size}"
                                           data-hex="true"
                                           {if isset($input.class)}class="{$input.class}"
                                           {else}class="datepicker"{/if}
                                           name="{$input.name}"
                                           value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
                                {elseif $input.type == 'free'}
                                    {$fields_value[$input.name]}
                                {/if}
                                    {if isset($input.required) && $input.required && $input.type != 'radio'} <sup>*</sup>{/if}
                                {/block}{* end block input *}
                                    {block name="description"}
                                        {if isset($input.desc) && !empty($input.desc)}
                                            <p class="preference_description">
                                                {if is_array($input.desc)}
                                                    {foreach $input.desc as $p}
                                                        {if is_array($p)}
                                                            <span id="{$p.id}">{$p.text}</span><br />
                                                        {else}
                                                            {$p}<br />
                                                        {/if}
                                                    {/foreach}
                                                {else}
                                                    {$input.desc}
                                                {/if}
                                            </p>
                                        {/if}
                                    {/block}
                                    {if isset($input.lang) && isset($languages)}<div class="clear"></div>{/if}
                                </div>
                                <div class="clear"></div>
                            {/block}{* end block field *}
                        {/if}
                    {/foreach}
                {elseif $key == 'submit'}
                    <div class="margin-form">
                        <input type="submit"
                               id="{if isset($field.id)}{$field.id}{else}{$table}_form_submit_btn{/if}"
                               value="{$field.title}"
                               name="{if isset($field.name)}{$field.name}{else}{$submit_action}{/if}{if isset($field.stay) && $field.stay}AndStay{/if}"
                               {if isset($field.class)}class="{$field.class}"{/if} />
                    </div>
                {elseif $key == 'desc'}
                    <p class="clear">
                        {if is_array($field)}
                            {foreach $field as $k => $p}
                                {if is_array($p)}
                                    <span id="{$p.id}">{$p.text}</span><br />
                                {else}
                                    {$p}
                                    {if isset($field[$k+1])}<br />{/if}
                                {/if}
                            {/foreach}
                        {else}
                            {$field}
                        {/if}
                    </p>
                {/if}
                {block name="other_input"}{/block}
            {/foreach}
            {if $required_fields}
                <div class="small"><sup>*</sup></div>
            {/if}
        </fieldset>
        {block name="other_fieldsets"}{/block}
        {if isset($fields[$f+1])}<br />{/if}
    {/foreach}
test debg
</form>
{/block}
{block name="after"}{/block}
