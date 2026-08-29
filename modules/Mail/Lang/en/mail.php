<?php

declare(strict_types=1);

return [
    'section_mail' => 'Email',
    'title' => 'Email settings',
    'saved' => 'The email settings have been saved.',
    'save' => 'Save',

    'block_delivery' => 'Delivery',
    'block_delivery_hint' => 'How this installation sends email. Nothing is sent while delivery is off.',
    'enabled' => 'Send email',
    'enabled_hint' => 'With this off, no message leaves the application — verification, password reset and notices are all silently dropped.',
    'transport' => 'Transport',
    'transport_log' => 'Log — write messages to the application log',
    'transport_smtp' => 'SMTP server',

    'block_sender' => 'Sender',
    'block_sender_hint' => 'What recipients see in the From and Reply-To headers.',
    'from_address' => 'From address',
    'from_address_hint' => 'Left empty, messages are sent from noreply@ the site host.',
    'from_name' => 'From name',
    'from_name_hint' => 'Left empty, the site name is used.',
    'reply_to' => 'Reply-To address',
    'admin_address' => 'Administrator address',
    'admin_address_hint' => 'Where notices about accounts awaiting approval are sent. Left empty, they are not sent.',

    'block_smtp' => 'SMTP server',
    'smtp_host' => 'Host',
    'smtp_port' => 'Port',
    'smtp_encryption' => 'Encryption',
    'smtp_encryption_none' => 'None',
    'smtp_encryption_tls' => 'STARTTLS',
    'smtp_encryption_ssl' => 'Implicit TLS (SMTPS)',
    'smtp_username' => 'Username',
    'smtp_password' => 'Password',
    'smtp_password_set' => 'A password is stored. Leave empty to keep it.',
    'smtp_timeout' => 'Timeout (seconds)',

    'block_test' => 'Test message',
    'block_test_hint' => 'Sends immediately, without the queue, and reports what the server answered.',
    'test_email' => 'Send a test message to',
    'test_send' => 'Send test message',
    'test_subject' => ':app test message',
    'test_body' => 'This is a test message from :app. If you are reading it, delivery works.',
    'test_sent' => 'A test message has been sent to :email.',
    'test_failed' => 'Sending failed: :error',
    'not_deliverable' => 'Email delivery is off or the transport is not configured, so nothing is sent.',
];
