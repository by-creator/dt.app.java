<?php

return [
    'recipients' => array_values(array_filter(array_map(
        static fn (string $email) => trim($email),
        explode(',', env('DEMAT_RECIPIENTS', 'sn004-proforma@dakar-terminal.com,sn004-facturation@dakar-terminal.com'))
    ))),

    'director_email' => env('DEMAT_DIRECTOR_EMAIL', 'sn004-remise.facturation@dakar-terminal.com'),
];
