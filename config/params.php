<?php

return [
    'adminEmail' => 'admin@stockms.com',
    'senderEmail' => 'noreply@stockms.com',
    'senderName' => 'Stock Management System',
    'supportEmail' => 'support@stockms.com',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    
    // JWT settings
    'jwt.secret' => 'your-secret-key-change-this-in-production',
    'jwt.expire' => 3600, // 1 hour
    'jwt.refresh_expire' => 86400, // 24 hours
    
    // Company settings (can be overridden in settings table)
    'company.name' => 'Your Company Name',
    'company.address' => 'Your Company Address',
    'company.phone' => '+1234567890',
    'company.email' => 'info@company.com',
    'company.tax_no' => 'TAX123456',
    'company.currency' => 'USD',
    'company.currency_symbol' => '$',
    'company.tax_rate' => 10.0,
    
    // Print settings
    'print.pos_width' => 58, // mm
    'print.a4_margin' => 15, // mm
    
    // Pagination
    'pagination.pageSize' => 20,
    'pagination.maxPageSize' => 100,
];
