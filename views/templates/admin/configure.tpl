{**
 * Oca e-Pak Module for Prestashop
 *
 * @author    Rinku Kazeno <development@kazeno.co>
 *
 * @copyright Copyright (c) 2012-2015, Rinku Kazeno
 * @license   This module is licensed to the user, upon purchase
 *  from either Prestashop Addons or directly from the author,
 *  for use on a single commercial Prestashop install, plus an
 *  optional separate non-commercial install (for development/testing
 *  purposes only). This license is non-assignable and non-transferable.
 *  To use in additional Prestashop installations an additional
 *  license of the module must be purchased for each one.
 *
 *  The user may modify the source of this module to suit their
 *  own business needs, as long as no distribution of either the
 *  original module or the user-modified version is made.
 *
 *  @file-version 1.3
 *}

{if $psver < 1.6}{literal}
    <style>
        .panel {
            border: 1px solid #CCCED7;
            padding: 6px;
        }
        .panel-heading {
            color: #585a69;
            text-shadow: 0 1px 0 #fff;
            font-weight: bold;
            font-size: 14px;
        }
        .icon-trash-o:before, .icon-trash:before, #content .process-icon-delete:before, #content .process-icon-uninstall:before {
            content: "\24cd";
        }
        [class^="process-icon-"] {
            display: inline-block;
            position: relative;
            bottom: 6px;
            left: 4px;
            margin: 0 auto;
            font-size: 14px;
            color: #585a69;
            float: right;
            border-left: 1px solid #CCCED7;
            border-bottom: 1px solid #CCCED7;
        }
        [class^="process-icon-"]:hover {
            background-color: #F8F8F8;
            color: #000;
        }
    </style>
{/literal}{else}{literal}
    <style>
        #form2 .chosen-container {
            width: 100% !important;
        }
    </style>
{/literal}{/if}
{literal}
    <script>
        $(document).ready(function() {
            {/literal}{if $psver < 1.6}{literal}
                var $form1 = $('#content>form').first().attr("id", "form1");
                var $form2 = $('#content>form').last().attr("id", "form2");
                var $table = $('#content>form').not($form1).not($form2).attr("id", 'ocae_operatives');
            {/literal}{else}{literal}
                var $form1 = $('#form1');
                var $form2 = $('#form2');
                var $table = $('form[id$="ocae_operatives"]');
            {/literal}{/if}{literal}
            function syncInputs(event) {
                event.data.target.find("[name='"+$(this).attr("name")+"']").val($(this).val());
                $table.find("[name='"+$(this).attr("name")+"']").val($(this).val());
            }
            $('#desc-ocae_operatives-refresh, #content>form a[href="javascript:location.reload();"]').hide();
            $('tr.filter').hide();
            $('#desc-ocae_operatives-new').bind('click', function() {
                $table.attr('action', $(this).attr('href')).submit();
                return !$table.length;
            });
            $form1.add($form2).find("input[type='hidden']").clone().appendTo($table);
            $form1.find("input[type='text']").bind('change', {target: $form2}, syncInputs);
            $form2.find("input[type='text']").bind('change', {target: $form1}, syncInputs);
        });
    </script>
{/literal}