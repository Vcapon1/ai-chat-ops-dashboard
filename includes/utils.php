
<?php
if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone) {
        // Remove @s.whatsapp.net and any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', str_replace('@s.whatsapp.net', '', $phone));
        
        // Format: +55 (19) 9928 25282
        if (strlen($phone) >= 13) {
            return sprintf(
                '+%s (%s) %s %s',
                substr($phone, 0, 2),
                substr($phone, 2, 2),
                substr($phone, 4, 4),
                substr($phone, 8, 5)
            );
        }
        
        return $phone;
    }
}

// Helper to format phone number for CRM integrations
// Converts from 554888440307@s.whatsapp.net to 4888440307
if (!function_exists('formatPhoneForCRM')) {
    function formatPhoneForCRM($phone) {
        // Remove @s.whatsapp.net and any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', str_replace('@s.whatsapp.net', '', $phone));
        
        // Remove the first two digits (country code/DDI) if phone has at least 3 digits
        if (strlen($phone) > 2) {
            return substr($phone, 2);
        }
        
        return $phone;
    }
}
?>
