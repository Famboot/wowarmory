<?php

error_reporting(E_ALL);
set_exception_handler(function (Throwable $exception) {
    echo "<pre>";
    echo "Uncaught exception: ", $exception->getMessage(), "\n";
    echo $exception->getTraceAsString();
    echo "</pre>";
});

require 'shell.php';

composerRequire([
    'illuminate/collections',
    'chrome-php/chrome',
    'gregwar/image',
]);

use Gregwar\Image\Image;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Dom\Node;

$filePath = 'armory.png';
$listedThreshold = 500;
$rareThreshold = 540;
$epicThreshold = 600;
$legendaryThreshold = 650;
$fontPath = 'fonts/InriaSans-Bold.ttf';

$data = loadData($listedThreshold);
////echo json_encode($data);
////exit();
//$data = json_decode(
//    '[{"name":"Tschubax","class":"Mage","itemLevel":"606"},{"name":"Pfl\u00f6nch","class":"Monk","itemLevel":"605"},{"name":"Kurnous","class":"Priest","itemLevel":"603"},{"name":"Droideka","class":"Druid","itemLevel":"603"},{"name":"Krustenk\u00e4se","class":"Warrior","itemLevel":"600"},{"name":"Sorunia","class":"Death Knight","itemLevel":"599"},{"name":"Fr\u00f6stelts","class":"Death Knight","itemLevel":"593"},{"name":"Krustenkase","class":"Warlock","itemLevel":"592"},{"name":"Alele","class":"Priest","itemLevel":"587"},{"name":"P\u00fampernickel","class":"Shaman","itemLevel":"579"},{"name":"Keinmut","class":"Druid","itemLevel":"577"},{"name":"Kodeqs","class":"Hunter","itemLevel":"569"},{"name":"Silador","class":"Paladin","itemLevel":"568"},{"name":"Vertun","class":"Warrior","itemLevel":"539"},{"name":"Fireprism","class":"Monk","itemLevel":"527"},{"name":"Bobbini","class":"Priest","itemLevel":"519"},{"name":"Pflonkk","class":"Demon Hunter","itemLevel":"500"},{"name":"Gradon","class":"Evoker","itemLevel":"500"}]',
//    true
//);
writeImage($filePath, $data, $rareThreshold, $epicThreshold, $legendaryThreshold, $fontPath);
//outputImage($filePath);

/**
 * @return array<array{name: string, class: string, itemLevel: string}>
 */
function loadData(int $listedThreshold): array
{
    $data = [];

    $browserFactory = new BrowserFactory('chromium');
    $browserFactory->setOptions([
        'sendSyncDefaultTimeout' => 10000, // defaults to 5000
        'userAgent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9',
        'debugLogger' => 'php://stdout',
        'headless' => true,
        'noSandbox' => true,
    ]);
    $browser = $browserFactory->createBrowser();

    try {
        $page = $browser->createPage();
        $page
            ->navigate('https://worldofwarcraft.blizzard.com/de-de/guild/eu/arygos/famboot?page=1&view=item-level&sort-column=5&sort-descending=false')//->waitForNavigation(Page::FIRST_MEANINGFUL_PAINT, 30000)
        ;
        // Anyhow, waitForNavigation does not work consistently
        sleep(5);

        $rows = $page->dom()
            ->querySelector('.GuildProfileRoster-table')
            ->querySelector('.ControlledTable-body')
            ->querySelectorAll('.ControlledTable-row');
        if (count($rows) === 0) {
            throw new RuntimeException("Could not fetch any rows");
        }

        foreach ($rows as $row) {
            /** @var Node $row */

            $name = $row->querySelector('.Character-name')->getText();
            $classes = explode(' ', $row->querySelector('.Character')->getAttributes()->get('class'));
            $class = match (true) {
                in_array('Character--DEATHKNIGHT', $classes) => 'Death Knight',
                in_array('Character--DEMONHUNTER', $classes) => 'Demon Hunter',
                in_array('Character--DRUID', $classes) => 'Druid',
                in_array('Character--EVOKER', $classes) => 'Evoker',
                in_array('Character--HUNTER', $classes) => 'Hunter',
                in_array('Character--MAGE', $classes) => 'Mage',
                in_array('Character--MONK', $classes) => 'Monk',
                in_array('Character--PALADIN', $classes) => 'Paladin',
                in_array('Character--PRIEST', $classes) => 'Priest',
                in_array('Character--ROGUE', $classes) => 'Rogue',
                in_array('Character--SHAMAN', $classes) => 'Shaman',
                in_array('Character--WARLOCK', $classes) => 'Warlock',
                in_array('Character--WARRIOR', $classes) => 'Warrior',
                default => 'undefined',
            };
            $itemLevel = $row->querySelector('.ControlledTable-data:last-child')->getText();

            if ($itemLevel >= $listedThreshold) {
                $data[] = [
                    'name' => $name,
                    'class' => $class,
                    'itemLevel' => $itemLevel
                ];
            }
        }
    } finally {
        $browser->close();
    }

    return $data;
}

/**
 * @param  array<array{name: string, class: string, itemLevel: string}>  $data
 */
function writeImage(string $filePath, array $data, $rareThreshold, $epicThreshold, int $legendaryThreshold, string $fontPath): void
{
    $height = (1 + count($data)) * 20;

    $image = Image::create(150, $height)->fill(0);
    $icon = Image::open('icon.png')->scaleResize(width: 30);
    $image->merge($icon, 114, 339);

    foreach ($data as $i => $d) {
        $y = 25 + $i * 20;
        $itemRarityColor = match (true) {
            $d['itemLevel'] >= $legendaryThreshold => 0xff8000,
            $d['itemLevel'] >= $epicThreshold => 0xa335ee,
            $d['itemLevel'] >= $rareThreshold => 0x0070dd,
            default => 0x1eff00,
        };
        $image->write($fontPath, $d['itemLevel'], 10, $y, size: 10, color: $itemRarityColor);
        $image = $image->write($fontPath, "- {$d['name']}", 35, $y, size: 10, color: getClassColor($d['class']));
    }

    $image->save($filePath);
}

function outputImage(string $filePath): void
{
    header('Content-Type', 'image/png');
    echo file_get_contents($filePath);
}

function getClassColor($class): int
{
    return match ($class) {
        'Death Knight' => 0xC41E3A,
        'Demon Hunter' => 0xA330C9,
        'Druid' => 0xFF7C0A,
        'Evoker' => 0x33937F,
        'Hunter' => 0xAAD372,
        'Mage' => 0x3FC7EB,
        'Monk' => 0x00FF98,
        'Paladin' => 0xF48CBA,
        //'Priest' => 0x0, // normally: 0xFFFFFF, but does not render too well on a white bg
        'Priest' => 0xFFFFFF,
        'Rogue' => 0xFFF468,
        'Shaman' => 0x0070DD,
        'Warlock' => 0x8788EE,
        'Warrior' => 0xC69B6D,
        default => 0x000000,
    };
}
