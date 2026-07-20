<?php
defined('ABSPATH') || exit;
// Unsubscribe handled in confirm.php via zcn_action=unsubscribe
// This file reserved for additional unsubscribe utilities

function zcn_unsubscribe_url($token) {
    return add_query_arg(['zcn_action' => 'unsubscribe', 'token' => $token], home_url('/'));
}
