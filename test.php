<?php

include 'vendor/autoload.php';

// generate new license data
$licenseData = MayMeow\License\License::new(
    name: "Technicke a zahradnicke sluzby Michalovce",
    app: "OneCloud",
    features: ['module_discloseure']
);

$l = new MayMeow\License\License();

// signe the license data
print_r($l->sign($licenseData));

// check if the license is valid
echo $l->isValid() ? 'valid' : 'invalid';

echo "\n";

echo $l->getId();

echo "\n";

echo $l->getLicensee();

echo "\n";

echo $l->hasFeature('f_loading_data') ? 'true' : 'false';
