<?php

return [

    /*
    | Number of days an approved authorization stays valid, counted from the
    | day of approval. Default: one week, as observed on real documents.
    */
    'validity_days' => (int) env('AUTHORIZATION_VALIDITY_DAYS', 7),

];
