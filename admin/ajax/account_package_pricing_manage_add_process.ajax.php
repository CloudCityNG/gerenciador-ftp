<?php

// includes and security
include_once('../_local_auth.inc.php');

$existing_pricing_id = (int) $_REQUEST['existing_pricing_id'];
$pricing_label       = trim($_REQUEST['pricing_label']);
$package_pricing_type = trim($_REQUEST['package_pricing_type']);
$period              = trim($_REQUEST['period']);
$download_allowance  = trim($_REQUEST['download_allowance']);
$user_level_id       = (int) $_REQUEST['user_level_id'];
$price               = trim($_REQUEST['price']);
$price_gbp           = trim($_REQUEST['price_gbp']);
$price_eur           = trim($_REQUEST['price_eur']);

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if (strlen($pricing_label) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("account_level_label_invalid", "Please specify the label.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}

if($result['error'] == false)
{
	if($package_pricing_type == 'bandwidth')
	{
		if (strlen($download_allowance) == 0)
		{
			$result['error'] = true;
			$result['msg']   = adminFunctions::t("download_allowance_invalid", "Please specify the download allowance.");
		}
	}
}

if (strlen($result['msg']) == 0)
{
    $row = $db->getRow('SELECT id FROM user_level_pricing WHERE label = ' . $db->quote($pricing_label) . ' AND user_level_id != ' . $user_level_id . ' AND id != ' . $existing_pricing_id);
    if (is_array($row))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("account_level_label_already_in_use", "That label has already been used, please choose another.");
    }
    else
    {
        if ($existing_pricing_id > 0)
        {
            // update the existing record
            $dbUpdate                = new DBObject("user_level_pricing", array("pricing_label", "period", "user_level_id", "package_pricing_type", "download_allowance", "price"), 'id');
            $dbUpdate->pricing_label = $pricing_label;
			$dbUpdate->package_pricing_type = $package_pricing_type;
            $dbUpdate->period        = $period;
			$dbUpdate->download_allowance = $download_allowance;
            $dbUpdate->user_level_id = $user_level_id;
            $dbUpdate->price     = $price;


            $dbUpdate->id = $existing_pricing_id;
            $dbUpdate->update();

            $result['error'] = false;
            $result['msg']   = 'Package pricing \'' . $pricing_label . '\' updated.';
        }
        else
        {
            // add the file server
            $dbInsert                = new DBObject("user_level_pricing", array("pricing_label", "period", "user_level_id", "package_pricing_type", "download_allowance", "price"));
            $dbInsert->pricing_label = $pricing_label;
			$dbUpdate->package_pricing_type = $package_pricing_type;
            $dbInsert->period        = $period;
			$dbUpdate->download_allowance = $download_allowance;
            $dbInsert->user_level_id = $user_level_id;
            $dbInsert->price     = $price;
            if (!$dbInsert->insert())
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("user_level_pricing_error_problem_record", "There was a problem adding the pricing, please try again.");
            }
            else
            {
                $result['error'] = false;
                $result['msg']   = 'Package pricing \'' . $pricing_label . '\' has been added.';
            }
        }
    }
}

echo json_encode($result);
exit;
