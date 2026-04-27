<?php

/*
|--------------------------------------------------------------------------
| Country reference data
|--------------------------------------------------------------------------
|
| Standard VAT/GST rates are reference values for ERP defaults only.
| Some countries use regional sales taxes, sector-specific rates or no VAT.
| Verify official tax rules before generating fiscal documents.
|
| Fields: ISO2, country name, international calling code, standard VAT/GST %.
|
*/

$rows = <<<'CSV'
AF,Afghanistan,+93,10
AL,Albanie,+355,20
DZ,AlgÃƒÂ©rie,+213,19
AD,Andorre,+376,4.5
AO,Angola,+244,14
AG,Antigua-et-Barbuda,+1-268,15
AR,Argentine,+54,21
AM,ArmÃƒÂ©nie,+374,20
AU,Australie,+61,10
AT,Autriche,+43,20
AZ,AzerbaÃƒÂ¯djan,+994,18
BS,Bahamas,+1-242,10
BH,BahreÃƒÂ¯n,+973,10
BD,Bangladesh,+880,15
BB,Barbade,+1-246,17.5
BY,BiÃƒÂ©lorussie,+375,20
BE,Belgique,+32,21
BZ,Belize,+501,12.5
BJ,BÃƒÂ©nin,+229,18
BT,Bhoutan,+975,7
BO,Bolivie,+591,13
BA,Bosnie-HerzÃƒÂ©govine,+387,17
BW,Botswana,+267,14
BR,BrÃƒÂ©sil,+55,17
BN,Brunei,+673,0
BG,Bulgarie,+359,20
BF,Burkina Faso,+226,18
BI,Burundi,+257,18
KH,Cambodge,+855,10
CM,Cameroun,+237,19.25
CA,Canada,+1,5
CV,Cap-Vert,+238,15
CF,RÃƒÂ©publique centrafricaine,+236,19
TD,Tchad,+235,18
CL,Chili,+56,19
CN,Chine,+86,13
CO,Colombie,+57,19
KM,Comores,+269,10
CG,Congo,+242,18
CD,Congo (RDC),+243,16
CR,Costa Rica,+506,13
CI,CÃƒÂ´te d'Ivoire,+225,18
HR,Croatie,+385,25
CU,Cuba,+53,0
CY,Chypre,+357,19
CZ,TchÃƒÂ©quie,+420,21
DK,Danemark,+45,25
DJ,Djibouti,+253,10
DM,Dominique,+1-767,15
DO,RÃƒÂ©publique dominicaine,+1-809,18
EC,Ãƒâ€°quateur,+593,15
EG,Ãƒâ€°gypte,+20,14
SV,Salvador,+503,13
GQ,GuinÃƒÂ©e ÃƒÂ©quatoriale,+240,15
ER,Ãƒâ€°rythrÃƒÂ©e,+291,0
EE,Estonie,+372,22
SZ,Eswatini,+268,15
ET,Ãƒâ€°thiopie,+251,15
FJ,Fidji,+679,15
FI,Finlande,+358,25.5
FR,France,+33,20
GA,Gabon,+241,18
GM,Gambie,+220,15
GE,GÃƒÂ©orgie,+995,18
DE,Allemagne,+49,19
GH,Ghana,+233,15
GR,GrÃƒÂ¨ce,+30,24
GD,Grenade,+1-473,15
GT,Guatemala,+502,12
GN,GuinÃƒÂ©e,+224,18
GW,GuinÃƒÂ©e-Bissau,+245,19
GY,Guyana,+592,14
HT,HaÃƒÂ¯ti,+509,10
HN,Honduras,+504,15
HU,Hongrie,+36,27
IS,Islande,+354,24
IN,Inde,+91,18
ID,IndonÃƒÂ©sie,+62,12
IR,Iran,+98,10
IQ,Irak,+964,0
IE,Irlande,+353,23
IL,IsraÃƒÂ«l,+972,18
IT,Italie,+39,22
JM,JamaÃƒÂ¯que,+1-876,15
JP,Japon,+81,10
JO,Jordanie,+962,16
KZ,Kazakhstan,+7,12
KE,Kenya,+254,16
KI,Kiribati,+686,0
KP,CorÃƒÂ©e du Nord,+850,0
KR,CorÃƒÂ©e du Sud,+82,10
XK,Kosovo,+383,18
KW,KoweÃƒÂ¯t,+965,0
KG,Kirghizistan,+996,12
LA,Laos,+856,10
LV,Lettonie,+371,21
LB,Liban,+961,11
LS,Lesotho,+266,15
LR,Liberia,+231,10
LY,Libye,+218,0
LI,Liechtenstein,+423,8.1
LT,Lituanie,+370,21
LU,Luxembourg,+352,17
MG,Madagascar,+261,20
MW,Malawi,+265,16.5
MY,Malaisie,+60,8
MV,Maldives,+960,8
ML,Mali,+223,18
MT,Malte,+356,18
MH,ÃƒÅ½les Marshall,+692,0
MR,Mauritanie,+222,16
MU,Maurice,+230,15
MX,Mexique,+52,16
FM,MicronÃƒÂ©sie,+691,0
MD,Moldavie,+373,20
MC,Monaco,+377,20
MN,Mongolie,+976,10
ME,MontÃƒÂ©nÃƒÂ©gro,+382,21
MA,Maroc,+212,20
MZ,Mozambique,+258,16
MM,Myanmar,+95,5
NA,Namibie,+264,15
NR,Nauru,+674,0
NP,NÃƒÂ©pal,+977,13
NL,Pays-Bas,+31,21
NZ,Nouvelle-ZÃƒÂ©lande,+64,15
NI,Nicaragua,+505,15
NE,Niger,+227,19
NG,Nigeria,+234,7.5
MK,MacÃƒÂ©doine du Nord,+389,18
NO,NorvÃƒÂ¨ge,+47,25
OM,Oman,+968,5
PK,Pakistan,+92,18
PW,Palaos,+680,10
PS,Palestine,+970,16
PA,Panama,+507,7
PG,Papouasie-Nouvelle-GuinÃƒÂ©e,+675,10
PY,Paraguay,+595,10
PE,PÃƒÂ©rou,+51,18
PH,Philippines,+63,12
PL,Pologne,+48,23
PT,Portugal,+351,23
QA,Qatar,+974,0
RO,Roumanie,+40,19
RU,Russie,+7,20
RW,Rwanda,+250,18
KN,Saint-Christophe-et-NiÃƒÂ©vÃƒÂ¨s,+1-869,17
LC,Sainte-Lucie,+1-758,12.5
VC,Saint-Vincent-et-les-Grenadines,+1-784,16
WS,Samoa,+685,15
SM,Saint-Marin,+378,17
ST,Sao TomÃƒÂ©-et-Principe,+239,15
SA,Arabie saoudite,+966,15
SN,SÃƒÂ©nÃƒÂ©gal,+221,18
RS,Serbie,+381,20
SC,Seychelles,+248,15
SL,Sierra Leone,+232,15
SG,Singapour,+65,9
SK,Slovaquie,+421,23
SI,SlovÃƒÂ©nie,+386,22
SB,ÃƒÅ½les Salomon,+677,10
SO,Somalie,+252,0
ZA,Afrique du Sud,+27,15
SS,Soudan du Sud,+211,18
ES,Espagne,+34,21
LK,Sri Lanka,+94,18
SD,Soudan,+249,17
SR,Suriname,+597,10
SE,SuÃƒÂ¨de,+46,25
CH,Suisse,+41,8.1
SY,Syrie,+963,0
TW,TaÃƒÂ¯wan,+886,5
TJ,Tadjikistan,+992,14
TZ,Tanzanie,+255,18
TH,ThaÃƒÂ¯lande,+66,7
TL,Timor oriental,+670,0
TG,Togo,+228,18
TO,Tonga,+676,15
TT,TrinitÃƒÂ©-et-Tobago,+1-868,12.5
TN,Tunisie,+216,19
TR,Turquie,+90,20
TM,TurkmÃƒÂ©nistan,+993,15
TV,Tuvalu,+688,0
UG,Ouganda,+256,18
UA,Ukraine,+380,20
AE,Ãƒâ€°mirats arabes unis,+971,5
GB,Royaume-Uni,+44,20
US,Ãƒâ€°tats-Unis,+1,0
UY,Uruguay,+598,22
UZ,OuzbÃƒÂ©kistan,+998,12
VU,Vanuatu,+678,15
VA,Vatican,+379,0
VE,Venezuela,+58,16
VN,Vietnam,+84,10
YE,YÃƒÂ©men,+967,5
ZM,Zambie,+260,16
ZW,Zimbabwe,+263,15
CSV;

$englishNames = [
    'CD' => 'Congo (DRC)',
    'CG' => 'Congo (Republic)',
    'CI' => 'Cote d\'Ivoire',
    'CV' => 'Cape Verde',
    'CZ' => 'Czechia',
    'GQ' => 'Equatorial Guinea',
    'GW' => 'Guinea-Bissau',
    'KP' => 'North Korea',
    'KR' => 'South Korea',
    'MK' => 'North Macedonia',
    'PS' => 'Palestine',
    'ST' => 'Sao Tome and Principe',
    'SZ' => 'Eswatini',
    'TL' => 'Timor-Leste',
    'US' => 'United States',
    'VA' => 'Vatican City',
    'XK' => 'Kosovo',
];

$countries = [];

foreach (explode("\n", trim($rows)) as $row) {
    [$iso, $nameFr, $phoneCode, $vatRate] = str_getcsv($row);
    $nameEn = $englishNames[$iso] ?? Locale::getDisplayRegion('-'.$iso, 'en') ?: $nameFr;

    $countries[$iso] = [
        'iso' => $iso,
        'name' => $nameFr,
        'name_fr' => $nameFr,
        'name_en' => $nameEn,
        'phone_code' => $phoneCode,
        'vat_rate' => (float) $vatRate,
    ];
}

uasort($countries, fn (array $first, array $second): int => strnatcasecmp($first['name_fr'], $second['name_fr']));

return $countries;