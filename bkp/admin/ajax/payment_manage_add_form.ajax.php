<?php

// includes and security
include_once('../_local_auth.inc.php');

// get initial data
$users = $db->getRows('SELECT id, username, email FROM users ORDER BY username');
$paymentMethods = array('PayPal', 'Cheque', 'Cash', 'Bank Transfer', 'SMS', 'Other');

// default values
$payment_date = coreFunctions::formatDate(time(), SITE_CONFIG_DATE_TIME_FORMAT);
$description = 'Payment for account upgrade';

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to add an entry into the payments log. Note: This will not upgrade users, you\'ll need to manually do this via the edit user page.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addTranslationForm" class="form">';
$result['html'] .= '<div class="clearfix">
                        <label for="user_id">User:</label>
                        <div class="input">
                            <select name="user_id" id="user_id" class="xxlarge">
                                ';
                                $result['html'] .= '<option value="">- select -</option>';
                                foreach($users AS $user)
                                {
                                    $result['html'] .= '<option value="'.$user['id'].'">'.$user['username'].' ('.$user['email'].')</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Payment Date/Time:</label>
                        <div class="input">
                            <input name="payment_date" id="payment_date" type="text" value="'.$payment_date.'" class="medium" placeholder="'.$payment_date.'"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>Payment Amount:</label>
                        <div class="input">
                            '.SITE_CONFIG_COST_CURRENCY_SYMBOL.'&nbsp;<input name="payment_amount" id="payment_amount" type="text" class="small" placeholder="0.00"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Description:</label>
                        <div class="input">
                            <input name="description" id="description" type="text" value="'.$description.'" class="xxlarge"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label for="user_id">Payment Method:</label>
                        <div class="input">
                            <select name="payment_method" id="payment_method" class="medium">
                                ';
                                foreach($paymentMethods AS $paymentMethod)
                                {
                                    $result['html'] .= '<option value="'.validation::safeOutputToScreen($paymentMethod).'">'.validation::safeOutputToScreen($paymentMethod).'</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Additional Notes:</label>
                        <div class="input">
                            <textarea name="notes" id="notes" class="xxlarge"></textarea>
                        </div>
                    </div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
