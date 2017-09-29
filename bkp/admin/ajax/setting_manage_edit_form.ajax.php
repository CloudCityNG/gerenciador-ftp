<?php

// includes and security
include_once('../_local_auth.inc.php');

$gConfigId   = (int) $_REQUEST['gConfigId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

// load existing configuration information
$config = $db->getRow("SELECT config_key, config_description, config_value, availableValues, config_type, config_group FROM site_config WHERE id = ".(int)$gConfigId);
if(!$config)
{
    $result['error'] = false;
    $result['msg'] = 'There was a problem loading the configuration for editing, please try again later.';
}

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to update the configuration item.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="updateConfigurationForm" class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Description:</label>
                        <div class="input formRow"><div class="info" style="margin-top: 6px; font-weight: bold;">
                            '.$config['config_description'].'
                        </div></div>
                    </div>';

$element = $config['config_value'];
switch($config['config_type'])
{
    case 'integer':
        $element = '<input type="text" value="'.adminFunctions::makeSafe($config['config_value']).'" class="custom[integer]" id="configValueElement"/>';
        break;
    case 'select':
        $selectItems = array();
        $availableValues = $config['availableValues'];
        if(substr($availableValues, 0, 6) == 'SELECT')
        {
            $items = $db->getRows($availableValues);
            if($items)
            {
                foreach($items AS $item)
                {
                    $selectItems[] = $item['itemValue'];
                }
            }
        }
        else
        {
            $selectItems = json_decode($availableValues, true);
            if(COUNT($selectItems) == 0)
            {
                $selectItems = array('Error: Failed loading options');
            }
        }
 
        $element = '<select id="configValueElement">';
        foreach($selectItems AS $selectItem)
        {
            $element .= '<option value="'.adminFunctions::makeSafe($selectItem).'"';
            if($selectItem == $config['config_value'])
            {
                $element .= ' SELECTED';
            }
            $element .= '>'.adminFunctions::makeSafe($selectItem).'</option>';
        }
        $element .= '</select>';
        break;
    case 'string':
        $element = '<input type="text" value="'.adminFunctions::makeSafe($config['config_value']).'" class="large" id="configValueElement"/>';
        break;
    case 'textarea':
    default:
        $element = '<textarea class="xxlarge" id="configValueElement">'.adminFunctions::makeSafe($config['config_value']).'</textarea>';
        break;
}
$result['html'] .= '<div class="clearfix">
                        <label>Value:</label>
                        <div class="input">
                            '.$element.'
                        </div>
                    </div>';
$result['html'] .= '<input id="configIdElement" name="configIdElement" value="'.$gConfigId.'" type="hidden"/>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
